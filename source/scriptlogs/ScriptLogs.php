<?php
/**
 * ScriptLogs Dashboard Plugin for Unraid
 * 
 * This file provides the main PHP backend functionality for the dashboard widget.
 */

// Prevent direct access
if (!defined('DOCROOT')) {
    exit('Direct access not permitted');
}

class ScriptLogs {
    
    private $userScriptsPath = '/boot/config/plugins/user.scripts/scripts/';
    private $tmpScriptsPath = '/tmp/user.scripts/tmpScripts/';
    
    /**
     * Get all running user scripts
     */
    public function getRunningScripts() {
        $runningScripts = [];
        
        // Check if user.scripts plugin is installed
        if (!is_dir($this->userScriptsPath)) {
            return $runningScripts;
        }
        
        // Get all script directories
        $scriptDirs = glob($this->userScriptsPath . '*', GLOB_ONLYDIR);
        
        foreach ($scriptDirs as $scriptDir) {
            $scriptName = basename($scriptDir);
            $pidFile = $this->tmpScriptsPath . $scriptName . '.pid';
            
            // Check if script is running by looking for PID file
            if (file_exists($pidFile)) {
                $pid = trim(file_get_contents($pidFile));
                
                // Verify the process is still running
                if ($this->isProcessRunning($pid)) {
                    $runningScripts[] = [
                        'name' => $scriptName,
                        'pid' => $pid,
                        'logFile' => $this->tmpScriptsPath . $scriptName . '.log',
                        'started' => $this->getProcessStartTime($pid)
                    ];
                }
            }
        }
        
        return $runningScripts;
    }
    
    /**
     * Check if a process is running
     */
    private function isProcessRunning($pid) {
        if (!$pid || !is_numeric($pid)) {
            return false;
        }
        
        return file_exists("/proc/$pid");
    }
    
    /**
     * Get process start time
     */
    private function getProcessStartTime($pid) {
        $statFile = "/proc/$pid/stat";
        if (!file_exists($statFile)) {
            return null;
        }
        
        $stat = file_get_contents($statFile);
        $fields = explode(' ', $stat);
        
        if (count($fields) > 21) {
            $starttime = $fields[21];
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            
            $seconds_since_boot = $starttime / 100; // Convert from clock ticks
            $process_start = time() - ($uptime - $seconds_since_boot);
            
            return date('Y-m-d H:i:s', $process_start);
        }
        
        return null;
    }
    
    /**
     * Get script log content
     */
    public function getScriptLog($scriptName, $lines = 100) {
        $logFile = $this->tmpScriptsPath . $scriptName . '.log';
        
        if (!file_exists($logFile)) {
            return "Log file not found: $logFile";
        }
        
        // Get last N lines of the log file
        $command = "tail -n $lines " . escapeshellarg($logFile);
        $output = shell_exec($command);
        
        return $output ?: "No log content available";
    }
    
    /**
     * Format log content for HTML display
     */
    public function formatLogContent($content) {
        // Escape HTML characters and preserve line breaks
        $content = htmlspecialchars($content);
        $content = nl2br($content);
        
        // Add some basic styling for common log patterns
        $content = preg_replace('/\[(ERROR|FAIL)\]/', '<span class="log-error">[$1]</span>', $content);
        $content = preg_replace('/\[(WARN|WARNING)\]/', '<span class="log-warning">[$1]</span>', $content);
        $content = preg_replace('/\[(INFO|SUCCESS|OK)\]/', '<span class="log-success">[$1]</span>', $content);
        
        return $content;
    }
}
?>