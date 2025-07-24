<?php
/**
 * API endpoint to get log content for a specific script
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include the main class
require_once '../ScriptLogs.php';

// Get parameters
$scriptName = $_GET['script'] ?? '';
$lines = intval($_GET['lines'] ?? 100);

if (empty($scriptName)) {
    echo json_encode([
        'success' => false,
        'error' => 'Script name is required'
    ]);
    exit;
}

// Sanitize script name to prevent directory traversal
$scriptName = basename($scriptName);

// Create instance and get log content
$dashboard = new ScriptLogs();
$logContent = $dashboard->getScriptLog($scriptName, $lines);
$formattedContent = $dashboard->formatLogContent($logContent);

// Return JSON response
echo json_encode([
    'success' => true,
    'script' => $scriptName,
    'content' => $formattedContent,
    'raw_content' => $logContent,
    'lines' => $lines,
    'timestamp' => time()
]);
?>