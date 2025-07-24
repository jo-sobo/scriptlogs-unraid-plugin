$(function() {
    setInterval(updateScriptLogs, 5000); // Update alle 5 Sekunden
});

function updateScriptLogs() {
    $.post('/plugins/scriptlogs/script.php', { action: 'get_logs' })
        .done(function(data) {
            var container = $('#scriptlogs-container');
            container.find('pre').text(data);
            container.scrollTop(container[0].scrollHeight);
        })
        .fail(function() {
            console.error('Fehler beim Aktualisieren der Script-Logs.');
        });
}