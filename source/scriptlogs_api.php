<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    header('Content-Type: application/json');

    $cfg = parse_plugin_cfg('scriptlogs', true);
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    
    $response_data = [];

    // --- NEW: Find out which script is actively writing to the 'in_progress' log ---
    $log_file_inprogress = '/tmp/user.scripts/logs/in_progress';
    $actively_logging_script = null;
    if (is_link($log_file_inprogress)) {
        $link_target = readlink($log_file_inprogress);
        // Extracts the script name from a path like '/tmp/user.scripts/tmpScripts/My Script/log.txt'
        if (preg_match('%/tmp/user.scripts/tmpScripts/([^/]+)/log.txt%', $link_target, $matches)) {
            $actively_logging_script = $matches[1];
        }
    }

    foreach ($enabled_scripts as $script_name) {
        $script_data = ['name' => $script_name, 'status' => 'idle', 'log' => ''];
        
        $search_pattern = "/tmp/user.scripts/tmpScripts/{$script_name}/script";
        $command = "ps -ef | grep " . escapeshellarg($search_pattern) . " | grep -v 'grep'";
        $process_output = shell_exec($command);
        $is_running = !empty($process_output);
        
        $log_file_finished = "/tmp/user.scripts/logs/{$script_name}";

        if ($is_running) {
            $script_data['status'] = 'running';
            // Check if THIS is the script that is actively logging
            if ($script_name === $actively_logging_script && file_exists($log_file_inprogress)) {
                 $lines = array_slice(file($log_file_inprogress, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                 $script_data['log'] = htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
            } else {
                 // The script is running, but its log is not in 'in_progress' right now.
                 $script_data['log'] = "Script is running in the background. Log will be available upon completion.";
            }
        } else {
            // --- FIX: User-friendly text for idle scripts ---
            if (file_exists($log_file_finished)) {
                $lines = array_slice(file($log_file_finished, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
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