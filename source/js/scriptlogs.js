function scriptlogs_init() {
    // Dashboard-Box anzeigen - KORREKTE ID verwenden!
    $('#db-scriptlogs').show();
    
    // Initial laden
    scriptlogs_refresh();
}

function scriptlogs_refresh() {
    $.get('/Dashboard/scriptlogs?action=getLogs', function(data) {
        if (data && data.logs) {
            $('#log-content').text(data.logs);
            $('.log-entries').text(data.lineCount + ' Zeilen');
            
            // Auto-scroll nach unten
            var container = $('#scriptlogs-container');
            container.scrollTop(container[0].scrollHeight);
        }
    }).fail(function() {
        $('#log-content').text('Fehler beim Laden der Logs');
        $('.log-entries').text('Fehler');
    });
}

// Dashboard-Integration - WICHTIG für Unraid!
function scriptlogs_dash() {
    // Dashboard-spezifische Funktionen
    $('.dash_scriptlogs').show();
    
    // Widget in Dashboard registrieren
    if (typeof addDashboardWidget === 'function') {
        addDashboardWidget('scriptlogs', 'Script Logs');
    }
}