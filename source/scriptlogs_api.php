<?php
// This file is a dedicated API endpoint. It only contains PHP logic.
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    $cfg = parse_plugin_cfg('scriptlogs', true);
    // Gracefully handle empty or non-existent settings
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    
    $response_data = [];

    foreach ($enabled_scripts as $script_name) {
        $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => ''];
        
        // --- NEW, MORE ROBUST METHOD: Check the system's process list ---
        // We look for the User Scripts helper that runs the actual script.
        $escaped_script_name = escapeshellarg($script_name);
        $command = "ps -ef | grep 'startScript.sh {$escaped_script_name}' | grep -v 'grep'";
        $process_output = shell_exec($command);
        $is_running = !empty($process_output);
        // --- END OF NEW METHOD ---
        
        $log_file_finished = "/tmp/user.scripts/logs/{$script_name}";
        $log_file_inprogress = '/tmp/user.scripts/logs/in_progress';

        if ($is_running) {
            $script_data['status'] = 'running';
            // If running, the log is in 'in_progress'. We also need to check if the running script is the one currently writing the log.
            // For simplicity, we assume if any of our monitored scripts is running, the in_progress log is relevant.
            if (file_exists($log_file_inprogress)) {
                 $lines = array_slice(file($log_file_inprogress, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $script_data['log'] = htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
            } else {
                 $script_data['log'] = "Script is running, but no log output detected yet.";
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
    
    // Set the content type header to signal a JSON response
    header('Content-Type: application/json');
    echo json_encode($response_data);
}
?>