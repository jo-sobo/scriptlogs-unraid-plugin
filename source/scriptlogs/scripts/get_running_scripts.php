<?php
/**
 * API endpoint to get running user scripts
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include the main class
require_once '../ScriptLogs.php';

// Create instance and get running scripts
$dashboard = new ScriptLogs();
$runningScripts = $dashboard->getRunningScripts();

// Return JSON response
echo json_encode([
    'success' => true,
    'scripts' => $runningScripts,
    'count' => count($runningScripts),
    'timestamp' => time()
]);
?>