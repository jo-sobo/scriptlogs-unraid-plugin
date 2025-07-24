<?php
require_once '/usr/local/emhttp/plugins/dynamix/include/Helpers.php';
require_once '/usr/local/emhttp/plugins/scriptlogs/include/Scriptlogs.php';

if (isset($_POST['action']) && $_POST['action'] === 'get_logs') {
    $scriptlogs = new Scriptlogs();
    echo $scriptlogs->getLogs();
}
?>