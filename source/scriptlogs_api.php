<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';
require_once '/usr/local/emhttp/plugins/scriptlogs/scriptlogs_common.php';

function scriptlogs_tail_log($path, $max_lines = 100, $max_bytes = 262144, $remove_empty_lines = true)
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
    if ($position === false) {
        @fclose($handle);
        return $result;
    }

    $remaining = (int)$position;
    $buffer = '';
    $read_bytes = 0;
    $chunk_size = 4096;

    while ($remaining > 0 && $read_bytes < $max_bytes) {
        $bytes_to_read = min($chunk_size, $remaining, $max_bytes - $read_bytes);
        $remaining -= $bytes_to_read;

        if (@fseek($handle, $remaining, SEEK_SET) !== 0) {
            break;
        }

        $chunk = @fread($handle, $bytes_to_read);
        if (!is_string($chunk) || $chunk === '') {
            break;
        }

        $buffer = $chunk . $buffer;
        $read_bytes += strlen($chunk);

        if (substr_count($buffer, "\n") >= ($max_lines + 1) && $read_bytes >= 16384) {
            break;
        }
    }

    @fclose($handle);

    $result['truncated'] = $remaining > 0;
    $result['ok'] = true;

    if ($buffer === '') {
        return $result;
    }

    $drop_first_line = $result['truncated'] && $buffer[0] !== "\n" && $buffer[0] !== "\r";
    $lines = preg_split('/\r\n|\r|\n/', $buffer);
    if (!is_array($lines)) {
        $lines = [];
    }

    if ($remove_empty_lines) {
        $filtered = [];
        foreach ($lines as $line) {
            if ($line !== '') {
                $filtered[] = $line;
            }
        }
    } else {
        $filtered = $lines;
    }

    if ($drop_first_line && count($filtered) > 0) {
        $filtered = array_slice($filtered, 1);
    }

    if (count($filtered) > $max_lines) {
        $filtered = array_slice($filtered, -$max_lines);
        $result['truncated'] = true;
    }

    $result['text'] = implode("\n", $filtered);
    return $result;
}

function scriptlogs_format_tail_text($tail)
{
    $text = (string)($tail['text'] ?? '');
    if (($tail['truncated'] ?? false) && $text !== '') {
        return "... log truncated ...\n" . $text;
    }

    return $text;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Only GET requests allowed');
}

if (!isset($_GET['action']) || $_GET['action'] !== 'get_script_states') {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid action'], scriptlogs_json_options());
    exit;
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$cfg = scriptlogs_read_cfg('scriptlogs');

$enabled_scripts = scriptlogs_parse_enabled_scripts($cfg['ENABLED_SCRIPTS'] ?? '');
$show_idle_logs = ($cfg['SHOW_IDLE_LOGS'] ?? '0') === '1';
$remove_empty_log_lines = ($cfg['REMOVE_EMPTY_LOG_LINES'] ?? '1') === '1';
$tail_limits = scriptlogs_tail_limits();

$ps_output = @shell_exec('ps -ef 2>&1');
if (!is_string($ps_output)) {
    $ps_output = '';
}

$response_data = [];

foreach ($enabled_scripts as $script_name_raw) {
    $script_name = basename(trim((string)$script_name_raw));
    if ($script_name === '') {
        continue;
    }

    $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => '', 'truncated' => false];

    $is_running_foreground = scriptlogs_is_foreground_running($ps_output, $script_name);
    $status_file_background = "/tmp/user.scripts/running/{$script_name}";
    $is_running_background = @file_exists($status_file_background);
    $log_file = "/tmp/user.scripts/tmpScripts/{$script_name}/log.txt";

    if ($is_running_foreground) {
        $script_data['status'] = 'running';
        $script_data['log'] = scriptlogs_t("Script is running in the foreground.\nView its live log in the 'User Scripts' plugin window.");
    } elseif ($is_running_background) {
        $script_data['status'] = 'running';
        if (@file_exists($log_file) && @is_readable($log_file)) {
            $tail = scriptlogs_tail_log($log_file, $tail_limits['lines'], $tail_limits['bytes'], $remove_empty_log_lines);
            if ($tail['ok']) {
                $script_data['truncated'] = (bool)$tail['truncated'];
                if ($tail['text'] !== '') {
                    $script_data['log'] = scriptlogs_format_tail_text($tail);
                } else {
                    $script_data['log'] = scriptlogs_t('Script is running, but has not produced any output yet.');
                }
            } else {
                $script_data['log'] = scriptlogs_t('Script is running, but log file cannot be read.');
            }
        } else {
            $script_data['log'] = scriptlogs_t('Script is running, but its log file has not been created yet.');
        }
    } else {
        if ($show_idle_logs) {
            if (@file_exists($log_file) && @is_readable($log_file)) {
                $tail = scriptlogs_tail_log($log_file, $tail_limits['lines'], $tail_limits['bytes'], $remove_empty_log_lines);
                if ($tail['ok']) {
                    $script_data['truncated'] = (bool)$tail['truncated'];
                    if ($tail['text'] !== '') {
                        $tail_text = scriptlogs_format_tail_text($tail);
                        $script_data['log'] = scriptlogs_t("Script is not running. Last log:\n\n") . $tail_text;
                    } else {
                        $script_data['log'] = scriptlogs_t('Script is not running. No previous log found (or it was last run in the foreground).');
                    }
                } else {
                    $script_data['log'] = scriptlogs_t('Script is not running. Log file cannot be read.');
                }
            } else {
                $script_data['log'] = scriptlogs_t('Script is not running. No previous log found (or it was last run in the foreground).');
            }
        } else {
            $script_data['log'] = scriptlogs_t('Script is not running.');
        }
    }

    $response_data[] = $script_data;
}

$json = json_encode($response_data, scriptlogs_json_options());
echo $json !== false ? $json : '[]';
