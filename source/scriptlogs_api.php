<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'get_script_states') {
    header('Content-Type: application/json');

    $cfg = parse_plugin_cfg('scriptlogs', true);
    $enabled_scripts_str = $cfg['ENABLED_SCRIPTS'] ?? '';
    $enabled_scripts = !empty($enabled_scripts_str) ? explode(',', $enabled_scripts_str) : [];
    
    // Read the new setting, default to '0' if not present
    $show_idle_logs = $cfg['SHOW_IDLE_LOGS'] ?? '0';

    $response_data = [];

    $log_file_inprogress = '/tmp/user.scripts/logs/in_progress';
    $actively_logging_script = null;
    if (is_link($log_file_inprogress)) {
        $link_target = readlink($log_file_inprogress);
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
        
        if ($is_running) {
            $script_data['status'] = 'running';
            
            // Differentiate between foreground (startScript.sh) and background runs ---
            if (strpos($process_output, 'startScript.sh') !== false) {
                $script_data['log'] = "Script is running in the foreground.\nView its live log in the 'User Scripts' plugin window.";
            } else {
                if ($script_name === $actively_logging_script && file_exists($log_file_inprogress)) {
                     $lines = array_slice(file($log_file_inprogress, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                     $log_content = implode("\n", $lines);
                     $script_data['log'] = !empty($log_content) ? htmlspecialchars($log_content, ENT_QUOTES, 'UTF-8') : "Script is running, but has not produced any output yet.";
                } else {
                     $script_data['log'] = "Script is running in the background. Log will be available upon completion.";
                }
            }
        } else {
            // Conditional logic for idle scripts
            if ($show_idle_logs === '1') {
                $finished_log_file = "/tmp/user.scripts/logs/{$script_name}";
                if (file_exists($finished_log_file)) {
                    $lines = array_slice(file($finished_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -100);
                    $script_data['log'] = "Script is not running. Last log:\n\n" . htmlspecialchars(implode("\n", $lines), ENT_QUOTES, 'UTF-8');
                } else {
                    $script_data['log'] = "Script is not running. No previous log found.";
                }
            } else {
                // If the setting is disabled, just show a simple message
                $script_data['log'] = "Script is not running.";
            }
        }
        $response_data[] = $script_data;
    }
    
    echo json_encode($response_data);
}
?>