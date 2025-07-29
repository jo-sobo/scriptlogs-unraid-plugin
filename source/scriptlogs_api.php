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
        
        // --- FINAL, CORRECTED LOGIC BASED ON YOUR SYSTEM'S FILE STRUCTURE ---

        // 1. Determine script status by checking for a file in the 'running' directory.
        $status_file = "/tmp/user.scripts/running/{$script_name}";
        $is_running = file_exists($status_file);

        // 2. The log file is always in the same place.
        $log_file = "/tmp/user.scripts/tmpScripts/{$script_name}/log.txt";

        if ($is_running) {
            $script_data['status'] = 'running';
            
            // For a running script, we always show its live log.
            if (file_exists($log_file)) {
                 $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $log_content = implode("\n", $lines);
                 $script_data['log'] = !empty($log_content) ? htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8') : "Script is running, but has not produced any output yet.";
            } else {
                 $script_data['log'] = "Script is running, but its log file has not been created yet.";
            }
        } else { // Script is idle
            $script_data['status'] = 'idle';
            
            // Show last known log only if the setting is enabled.
            if ($show_idle_logs === '1') {
                if (file_exists($log_file)) {
                    $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                    $script_data['log'] = "Script is not running. Last log:\n\n" . htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
                } else {
                    $script_data['log'] = "Script is not running. No previous log found.";
                }
            } else {
                // If the setting is disabled, show the simple message.
                $script_data['log'] = "Script is not running.";
            }
        }
        $response_data[] = $script_data;
    }
    
    echo json_encode($response_data);
}
?>