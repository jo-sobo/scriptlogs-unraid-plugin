<?php

require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';


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
            if ($lines === false) {
                return 'Fehler beim Lesen der Log-Datei.';
            }
            $last_lines = array_slice($lines, -100);
            return htmlspecialchars(implode("\n", $last_lines));
        } else {
            return "Log-Datei nicht gefunden unter:\n" . htmlspecialchars($this->logFile);
        }
    }
}

?>