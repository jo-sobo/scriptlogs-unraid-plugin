<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    header('Content-Type: application/json');

    $cfg = parse_plugin_cfg('scriptlogs', true);
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    $show_idle_logs = $cfg['SHOW_IDLE_LOGS'] ?? '0';
    
    $response_data = [];

    foreach ($enabled_scripts as $script_name) {
        $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => ''];

        // --- FINAL HYBRID STATUS CHECK ---

        // 1. Check for a foreground process first.
        $fg_search_pattern = "startScript.sh /tmp/user.scripts/tmpScripts/{$script_name}/script";
        $command_fg = "ps -ef | grep " . escapeshellarg($fg_search_pattern) . " | grep -v 'grep'";
        $process_output_fg = shell_exec($command_fg);
        $is_running_fg = !empty($process_output_fg);

        // 2. If not running in foreground, check for a background process.
        $status_file_bg = "/tmp/user.scripts/running/{$script_name}";
        $is_running_bg = file_exists($status_file_bg);
        
        // This is the one and only log file path for this script.
        $log_file = "/tmp/user.scripts/tmpScripts/{$script_name}/log.txt";

        if ($is_running_fg) {
            $script_data['status'] = 'running';
            $script_data['log'] = "Script is running in the foreground.\nView its live log in the 'User Scripts' plugin window.";
        
        } else if ($is_running_bg) {
            $script_data['status'] = 'running';
            // It's a background script, so we expect a live log.
            if (file_exists($log_file)) {
                 $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $log_content = implode("\n", $lines);
                 $script_data['log'] = !empty($log_content) ? htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8') : "Script is running, but has not produced any output yet.";
            } else {
                 $script_data['log'] = "Script is running, but its log file has not been created yet.";
            }

        } else { // Script is idle
            $script_data['status'] = 'idle';
            
            if ($show_idle_logs === '1') {
                // We check for the persistent log file, which only exists if the script was last run in the background.
                if (file_exists($log_file)) {
                    $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                    $script_data['log'] = "Script is not running. Last log:\n\n" . htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
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
}
?>