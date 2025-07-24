<?php

// Sicherstellen, dass die dashboardApp Klasse verfügbar ist
if (!class_exists('dashboardApp')) {
    require_once '/usr/local/emhttp/plugins/dynamix/include/dashboardApp.php';
}

class Scriptlogs extends dashboardApp {

    public $pluginName = 'scriptlogs';
    public $cardName = 'Script Logs';
    private $logFile = '/tmp/user.scripts/logs/in_progress';

    public function __construct() {
        $this->jsFile = "/plugins/{$this->pluginName}/js/scriptlogs.js";
        $this->cssFile = "/plugins/{$this->pluginName}/css/scriptlogs.css";
        parent::__construct();
    }

    public function display() {
        // CSS einbinden
        if (file_exists("/usr/local/emhttp/plugins/{$this->pluginName}/css/scriptlogs.css")) {
            echo '<style type="text/css">
                @import url("/plugins/' . $this->pluginName . '/css/scriptlogs.css");
            </style>';
        }

        // Hauptcontainer mit Unraid-Dashboard-Struktur - EINDEUTIGE ID!
        echo '
        <table id="db-scriptlogs" class="dash_scriptlogs dashboard box1" style="display:none">
            <thead sort="954"><tr class="hidden"><td></td><td colspan="3"></td><td></td></tr></thead>
            <tbody sort="954" class="sortable">
                <tr>
                    <td></td>
                    <td class="next" colspan="3">
                        <i class="icon-notebook"></i>
                        <div class="section">Script Logs<br>
                            <span id="log-status">Status: <span class="log-entries">Loading...</span></span>
                        </div>
                        <i class="fa fa-fw chevron mt0" id="dash_scriptlogs_toggle" onclick="toggleChevron(\'dash_scriptlogs_toggle\',0)"></i>
                        <br><br>
                    </td>
                    <td></td>
                </tr>
                <tr class="dash_scriptlogs_toggle log-content">
                    <td></td>
                    <td colspan="3">
                        <div id="scriptlogs-container" style="height:220px; overflow-y:auto; font-family: monospace; font-size: 12px; background-color: #333; color: #fff; padding: 10px; border-radius: 3px;">
                            <pre id="log-content" style="margin: 0; white-space: pre-wrap; word-wrap: break-word;">' . $this->getLogs() . '</pre>
                        </div>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>';

        // JavaScript einbinden
        if (file_exists("/usr/local/emhttp/plugins/{$this->pluginName}/js/scriptlogs.js")) {
            echo '<script type="text/javascript" src="/plugins/' . $this->pluginName . '/js/scriptlogs.js"></script>';
        }
        
        // JavaScript für Auto-Refresh und Dashboard-Integration
        echo '<script type="text/javascript">
            $(function() {
                if (typeof scriptlogs_init === "function") {
                    scriptlogs_init();
                }
                // Dashboard-Integration
                if (typeof scriptlogs_dash === "function") {
                    $(scriptlogs_dash);
                }
                // Auto-refresh alle 5 Sekunden
                setInterval(function() {
                    if (typeof scriptlogs_refresh === "function") {
                        scriptlogs_refresh();
                    }
                }, 5000);
            });
        </script>';
    }

    public function getLogs() {
        try {
            if (file_exists($this->logFile)) {
                $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) {
                    return 'Fehler beim Lesen der Log-Datei.';
                }
                
                // Nur die letzten 100 Zeilen
                $last_lines = array_slice($lines, -100);
                return htmlspecialchars(implode("\n", $last_lines), ENT_QUOTES, 'UTF-8');
                
            } else {
                return "Log-Datei nicht gefunden unter:\n" . htmlspecialchars($this->logFile, ENT_QUOTES, 'UTF-8');
            }
        } catch (Exception $e) {
            return 'Fehler beim Verarbeiten der Logs: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    // AJAX-Endpunkt für Live-Updates
    public function getLogsJson() {
        header('Content-Type: application/json');
        $logs = $this->getLogs();
        $lineCount = substr_count($logs, "\n");
        
        echo json_encode([
            'logs' => $logs,
            'lineCount' => $lineCount,
            'timestamp' => date('Y-m-d H:i:s'),
            'fileExists' => file_exists($this->logFile)
        ]);
    }
}

?>