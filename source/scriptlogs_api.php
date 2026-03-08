<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

function scriptlogs_normalize_script_list($items)
{
    if (!is_array($items)) {
        return [];
    }

    $normalized = [];
    $seen = [];
    foreach ($items as $item) {
        $name = trim((string)$item);
        if ($name === '' || isset($seen[$name])) {
            continue;
        }
        $seen[$name] = true;
        $normalized[] = $name;
    }

    return $normalized;
}

function scriptlogs_parse_enabled_scripts($rawValue)
{
    $rawValue = is_string($rawValue) ? trim($rawValue) : '';
    if ($rawValue === '') {
        return [];
    }

    if (strpos($rawValue, 'json:') === 0) {
        $encoded = substr($rawValue, 5);
        if ($encoded !== '') {
            $decodedJson = base64_decode($encoded, true);
            if (is_string($decodedJson) && $decodedJson !== '') {
                $decoded = json_decode($decodedJson, true);
                if (is_array($decoded)) {
                    return scriptlogs_normalize_script_list($decoded);
                }
            }
        }
    }

    return scriptlogs_normalize_script_list(explode(',', $rawValue));
}

function scriptlogs_tail_log($path, $maxLines = 100, $maxBytes = 262144)
{
    $result = ['ok' => false, 'text' => '', 'truncated' => false];

    $handle = @fopen($path, 'rb');
    if (!$handle) {
        return $result;
    }

    if (@fseek($handle, 0, SEEK_END) !== 0) {
        @fclose($handle);
        return $result;
    }

    $position = @ftell($handle);
    if (!is_int($position) && !is_float($position)) {
        @fclose($handle);
        return $result;
    }

    $remaining = (int)$position;
    $buffer = '';
    $readBytes = 0;
    $chunkSize = 4096;

    while ($remaining > 0 && $readBytes < $maxBytes) {
        $bytesToRead = min($chunkSize, $remaining, $maxBytes - $readBytes);
        $remaining -= $bytesToRead;

        if (@fseek($handle, $remaining, SEEK_SET) !== 0) {
            break;
        }

        $chunk = @fread($handle, $bytesToRead);
        if (!is_string($chunk) || $chunk === '') {
            break;
        }

        $buffer = $chunk . $buffer;
        $readBytes += strlen($chunk);

        if (substr_count($buffer, "\n") >= ($maxLines + 1) && $readBytes >= 16384) {
            break;
        }
    }

    @fclose($handle);

    $result['truncated'] = $remaining > 0;
    $result['ok'] = true;

    if ($buffer === '') {
        return $result;
    }

    $lines = preg_split('/\r\n|\r|\n/', $buffer);
    if (!is_array($lines)) {
        $lines = [];
    }

    $filtered = [];
    foreach ($lines as $line) {
        if ($line !== '') {
            $filtered[] = $line;
        }
    }

    if (count($filtered) > $maxLines) {
        $filtered = array_slice($filtered, -$maxLines);
        $result['truncated'] = true;
    }

    $result['text'] = implode("\n", $filtered);
    return $result;
}

function scriptlogs_limit_output($text, $maxBytes = 65536)
{
    $text = (string)$text;
    if (strlen($text) <= $maxBytes) {
        return $text;
    }

    return rtrim(substr($text, 0, $maxBytes)) . "\n\n[output truncated]";
}

function scriptlogs_add_truncation_note($text)
{
    if ($text === '') {
        return '[output truncated]';
    }

    return rtrim($text) . "\n\n[output truncated]";
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$requestedWith = $headers['X-Requested-With'] ?? $headers['x-requested-with'] ?? ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null);
if ($requestedWith !== 'XMLHttpRequest') {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Only GET requests allowed');
}

if (!isset($_GET['action']) || $_GET['action'] !== 'get_script_states') {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$cfg = parse_plugin_cfg('scriptlogs', true);
if (!is_array($cfg)) {
    $cfg = [];
}

$enabledScripts = scriptlogs_parse_enabled_scripts($cfg['ENABLED_SCRIPTS'] ?? '');
$showIdleLogs = ($cfg['SHOW_IDLE_LOGS'] ?? '0') === '1';

$psOutput = @shell_exec('ps -ef 2>&1');
if (!is_string($psOutput)) {
    $psOutput = '';
}

$responseData = [];

foreach ($enabledScripts as $scriptNameRaw) {
    $scriptName = basename(trim((string)$scriptNameRaw));
    if ($scriptName === '') {
        continue;
    }

    $scriptData = ['name' => $scriptName, 'status' => 'idle', 'log' => ''];

    $foregroundPattern = "startScript.sh /tmp/user.scripts/tmpScripts/{$scriptName}/script";
    $isRunningForeground = $psOutput !== '' && strpos($psOutput, $foregroundPattern) !== false;
    $statusFileBackground = "/tmp/user.scripts/running/{$scriptName}";
    $isRunningBackground = @file_exists($statusFileBackground);
    $logFile = "/tmp/user.scripts/tmpScripts/{$scriptName}/log.txt";

    if ($isRunningForeground) {
        $scriptData['status'] = 'running';
        $scriptData['log'] = "Script is running in the foreground.\nView its live log in the 'User Scripts' plugin window.";
    } elseif ($isRunningBackground) {
        $scriptData['status'] = 'running';
        if (@file_exists($logFile) && @is_readable($logFile)) {
            $tail = scriptlogs_tail_log($logFile);
            if ($tail['ok']) {
                if ($tail['text'] !== '') {
                    $scriptData['log'] = $tail['truncated']
                        ? scriptlogs_add_truncation_note($tail['text'])
                        : $tail['text'];
                } else {
                    $scriptData['log'] = 'Script is running, but has not produced any output yet.';
                }
            } else {
                $scriptData['log'] = 'Script is running, but log file cannot be read.';
            }
        } else {
            $scriptData['log'] = 'Script is running, but its log file has not been created yet.';
        }
    } else {
        if ($showIdleLogs) {
            if (@file_exists($logFile) && @is_readable($logFile)) {
                $tail = scriptlogs_tail_log($logFile);
                if ($tail['ok']) {
                    if ($tail['text'] !== '') {
                        $idleLog = $tail['truncated']
                            ? scriptlogs_add_truncation_note($tail['text'])
                            : $tail['text'];
                        $scriptData['log'] = "Script is not running. Last log:\n\n{$idleLog}";
                    } else {
                        $scriptData['log'] = 'Script is not running. No previous log found (or it was last run in the foreground).';
                    }
                } else {
                    $scriptData['log'] = 'Script is not running. Log file cannot be read.';
                }
            } else {
                $scriptData['log'] = 'Script is not running. No previous log found (or it was last run in the foreground).';
            }
        } else {
            $scriptData['log'] = 'Script is not running.';
        }
    }

    $scriptData['log'] = scriptlogs_limit_output($scriptData['log']);
    $responseData[] = $scriptData;
}

$jsonOptions = 0;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
}

$json = json_encode($responseData, $jsonOptions);
echo $json !== false ? $json : '[]';
?>
