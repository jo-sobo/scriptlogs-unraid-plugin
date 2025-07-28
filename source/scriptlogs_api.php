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
            
            // --- FINAL FIX: Look for the correct live log file based on the ps output ---
            $live_log_file = "/tmp/user.scripts/tmpScripts/{$script_name}/log.txt";

            if (file_exists($live_log_file)) {
                 $lines = array_slice(file($live_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $log_content = implode("\n", $lines);
                 // If the log is empty, provide a more helpful message
                 $script_data['log'] = !empty($log_content) ? htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8') : "Script is running, but has not produced any output yet.";
            } else {
                 $script_data['log'] = "Script is running, but its log file could not be found at {$live_log_file}.";
            }
        } else {
            // Not running, check for the finished log file in its final destination
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