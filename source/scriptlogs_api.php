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
        
        // --- FIX: Replace spaces with underscores for the PID file name ---
        $pid_file_name = str_replace(' ', '_', $script_name);
        $pid_file = "/var/run/user.scripts/pid.{$pid_file_name}";
        
        $log_file_finished = "/tmp/user.scripts/logs/{$script_name}";
        $log_file_inprogress = '/tmp/user.scripts/logs/in_progress';

        if (file_exists($pid_file)) {
            $script_data['status'] = 'running';
            // If running, the log is in 'in_progress'
            if (file_exists($log_file_inprogress)) {
                 $lines = array_slice(file($log_file_inprogress, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $script_data['log'] = htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
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