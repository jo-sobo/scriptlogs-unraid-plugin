<?php
// This is the final version of the API endpoint.
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    // Set the content type header to signal a JSON response at the beginning.
    header('Content-Type: application/json');

    $cfg = parse_plugin_cfg('scriptlogs', true);
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    
    $response_data = [];

    foreach ($enabled_scripts as $script_name) {
        $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => ''];
        
        // --- FINAL, ROBUST METHOD: Search for the unique script path in the process list ---
        $search_pattern = "/tmp/user.scripts/tmpScripts/{$script_name}/script";
        // escapeshellarg wraps the pattern in single quotes to handle spaces and special chars safely.
        $command = "ps -ef | grep " . escapeshellarg($search_pattern) . " | grep -v 'grep'";
        $process_output = shell_exec($command);
        $is_running = !empty($process_output);
        // --- END OF FINAL METHOD ---
        
        $log_file_finished = "/tmp/user.scripts/logs/{$script_name}";
        $log_file_inprogress = '/tmp/user.scripts/logs/in_progress';

        if ($is_running) {
            $script_data['status'] = 'running';
            // If running, the log is in 'in_progress'.
            if (file_exists($log_file_inprogress)) {
                 $lines = array_slice(file($log_file_inprogress, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $script_data['log'] = htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
            } else {
                 $script_data['log'] = "Script is running, but no log output has been generated yet.";
            }
        } else {
            // Not running, check for a finished log file
            if (file_exists($log_file_finished)) {
                $lines = array_slice(file($log_file_finished, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                $script_data['log'] = htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
            } else {
                $script_data['log'] = "No log file found for this script.";
            }
        }
        $response_data[] = $script_data;
    }
    
    echo json_encode($response_data);
}
?>