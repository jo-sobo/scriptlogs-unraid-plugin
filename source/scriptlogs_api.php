<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    header('Content-Type: application/json');

    $cfg = parse_plugin_cfg('scriptlogs', true);
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    
    $response_data = [];

    foreach ($enabled_scripts as $script_name) {
        $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => ''];
        
        $search_pattern = "/tmp/user.scripts/tmpScripts/{$script_name}/script";
        $command = "ps -ef | grep " . escapeshellarg($search_pattern) . " | grep -v 'grep'";
        $process_output = shell_exec($command);
        $is_running = !empty($process_output);
        
        if ($is_running) {
            $script_data['status'] = 'running';
            
            // Differentiate between foreground (startScript.sh) and background runs ---
            if (strpos($process_output, 'startScript.sh') !== false) {
                // Script is running in the foreground
                $script_data['log'] = "Script is running in the foreground.\nView its live log in the 'User Scripts' plugin window.";
            } else {
                // Script is running in the background, find its log.txt
                $live_log_file = "/tmp/user.scripts/tmpScripts/{$script_name}/log.txt";
                if (file_exists($live_log_file)) {
                     $lines = array_slice(file($live_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                     $log_content = implode("\n", $lines);
                     $script_data['log'] = !empty($log_content) ? htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8') : "Script is running, but has not produced any output yet.";
                } else {
                     $script_data['log'] = "Script is running, but its log file could not be found.";
                }
            }
        } else {
            // Not running, check for the finished log file
            $finished_log_file = "/tmp/user.scripts/logs/{$script_name}";
            if (file_exists($finished_log_file)) {
                $lines = array_slice(file($finished_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                $script_data['log'] = "Script is not running. Last log:\n\n" . htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
            } else {
                $script_data['log'] = "Script is not running. No log file found.";
            }
        }
        $response_data[] = $script_data;
    }
    
    echo json_encode($response_data);
}
?>