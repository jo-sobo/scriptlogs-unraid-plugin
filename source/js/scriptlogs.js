function scriptlogs_status() {
    $.get('/Dashboard/scriptlogs?action=getLogs', function(data) {
        if (data) {
            $('#scriptlogs-logs').text(data.logs);
            $('.scriptlogs-entries').text(data.lineCount + ' Zeilen');
            $('#scriptlogs-timestamp').text(new Date().toLocaleTimeString());
            
            // Status-Farbe
            if (data.fileExists) {
                $('.scriptlogs-entries').css('color', '#4CAF50');
            } else {
                $('.scriptlogs-entries').css('color', '#f44336');
            }
            
            // Auto-scroll nach unten
            var container = $('#scriptlogs-container');
            container.scrollTop(container[0].scrollHeight);
        }
    }).fail(function() {
        $('#scriptlogs-logs').text('Fehler beim Laden der Logs');
        $('.scriptlogs-entries').text('Fehler').css('color', '#f44336');
    });
}

function scriptlogs_dash() {
    $('.dash_scriptlogs').show();
}