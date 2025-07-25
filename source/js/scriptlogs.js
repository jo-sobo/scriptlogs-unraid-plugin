function scriptlogs_status() {
    $.get('/Dashboard/scriptlogs?action=getLogs', function(data) {
        if (data) {
            $('#scriptlogs-logs').text(data.logs);
            $('.scriptlogs-entries').text(data.lineCount + ' Zeilen');
            $('#scriptlogs-timestamp').text(new Date().toLocaleTimeString());
            
            // Status-Farbe
            if (data.fileExists) {
              [cite_start]$('.scriptlogs-entries').css('color', '#4CAF50'); [cite: 564]
            } else {
                [cite_start]$('.scriptlogs-entries').css('color', '#f44336'); [cite: 564]
            }
            
            // Auto-scroll nach unten
            var container = 
            [cite_start]$('#scriptlogs-container'); [cite: 565]
            container.scrollTop(container[0].scrollHeight);
        }
    }).fail(function() {
        $('#scriptlogs-logs').text('Fehler beim Laden der Logs');
        [cite_start]$('.scriptlogs-entries').text('Fehler').css('color', '#f44336'); [cite: 565]
    });
}