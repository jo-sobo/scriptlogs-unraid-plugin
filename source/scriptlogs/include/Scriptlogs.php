<?php

class Scriptlogs extends dashboardApp {
    public $pluginName = 'scriptlogs';
    public $cardName = 'Script Logs';
    private $logFile = '/tmp/user.scripts/logs/in_progress'; // Pfad anpassen, falls nötig!

    public function __construct() {
        $this->jsFile = "/plugins/{$this->pluginName}/js/scriptlogs.js";
        parent::__construct();
    }

    public function display() {
        echo '
        <div id="scriptlogs-container" style="height:220px; overflow-y:auto; font-family: monospace; font-size: 12px; background-color: #333; color: #fff; padding: 10px; border-radius: 3px;">
            <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">' . $this->getLogs() . '</pre>
        </div>';
    }

    public function getLogs() {
        if (file_exists($this->logFile)) {
            $lines = file($this->logFile, FILE_IGNORE_NEW_LINES);
            $last_lines = array_slice($lines, -100); // Zeigt die letzten 100 Zeilen
            return htmlspecialchars(implode("\n", $last_lines));
        } else {
            return "Log-Datei nicht gefunden unter:\n" . htmlspecialchars($this->logFile);
        }
    }
}

?>