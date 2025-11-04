<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

// Security: ensure the request originates from the WebGUI via AJAX
$headers = function_exists('getallheaders') ? getallheaders() : [];
$requestedWith = $headers['X-Requested-With'] ?? $headers['x-requested-with'] ?? ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null);
if ($requestedWith !== 'XMLHttpRequest') {
    header('HTTP/1.1 403 Forbidden');
    exit('Direct access not allowed');
}

// Enforce read-only access
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Only GET requests allowed');
}

if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    $cfg = parse_plugin_cfg('scriptlogs', true);
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    $show_idle_logs = $cfg['SHOW_IDLE_LOGS'] ?? '0';

    $response_data = [];

    foreach ($enabled_scripts as $script_name_raw) {
        $script_name = basename(trim($script_name_raw));
        if ($script_name === '') {
            continue;
        }

        $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => ''];

        // Hybrid status check (foreground first, then background)
        $fg_search_pattern = "startScript.sh /tmp/user.scripts/tmpScripts/{$script_name}/script";
        $command_fg = "ps -ef 2>&1 | grep " . escapeshellarg($fg_search_pattern) . " | grep -v 'grep'";
        $process_output_fg = @shell_exec($command_fg);
        $is_running_fg = !empty($process_output_fg);

        $status_file_bg = "/tmp/user.scripts/running/{$script_name}";
        $is_running_bg = @file_exists($status_file_bg);

        $log_file = "/tmp/user.scripts/tmpScripts/{$script_name}/log.txt";

        if ($is_running_fg) {
            $script_data['status'] = 'running';
            $script_data['log'] = "Script is running in the foreground.\nView its live log in the 'User Scripts' plugin window.";
        } elseif ($is_running_bg) {
            $script_data['status'] = 'running';
            if (@file_exists($log_file) && @is_readable($log_file)) {
                $file_lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($file_lines !== false) {
                    $lines = array_slice($file_lines, -100);
                    $log_content = implode("\n", $lines);
                    $script_data['log'] = !empty($log_content)
                        ? htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8')
                        : "Script is running, but has not produced any output yet.";
                } else {
                    $script_data['log'] = "Script is running, but log file cannot be read.";
                }
            } else {
                $script_data['log'] = "Script is running, but its log file has not been created yet.";
            }
        } else {
            if ($show_idle_logs === '1') {
                if (@file_exists($log_file) && @is_readable($log_file)) {
                    $file_lines = @file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if ($file_lines !== false) {
                        $lines = array_slice($file_lines, -100);
                        $script_data['log'] = "Script is not running. Last log:\n\n" .
                            htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
                    } else {
                        $script_data['log'] = "Script is not running. Log file cannot be read.";
                    }
                } else {
                    $script_data['log'] = "Script is not running. No previous log found (or it was last run in the foreground).";
                }
            } else {
                $script_data['log'] = "Script is not running.";
            }
        }

        $response_data[] = $script_data;
    }

    echo json_encode($response_data);
    exit;
}

header('HTTP/1.1 400 Bad Request');
echo json_encode(['error' => 'Invalid action']);
?>