/* global $ */

function scriptlogs_status() {
    // The 'enabledScripts' variable is now globally available from the .page file
    if (!enabledScripts || !enabledScripts[0]) {
        $('#scriptlogs-logs').text('No scripts selected. Please check your Scriptlogs settings.');
        return;
    }

    $.get('/Dashboard/scriptlogs?action=get_script_states', function(scripts) {
        const tabContainer = $('#script-tabs-container');
        const logDisplay = $('#scriptlogs-logs');
        const timestampDisplay = $('#scriptlogs-timestamp');
        
        // Find out which tab is currently selected, if any
        const previouslySelected = tabContainer.find('.selected-script').data('script-name');
        
        tabContainer.empty(); // Clear old tabs

        scripts.forEach(script => {
            // Define colors based on status
            let bgColor = '#555'; // Gray for idle
            if (script.status === 'running') bgColor = '#4CAF50'; // Green for running
            if (script.status === 'error') bgColor = '#f44336'; // Red for error

            // Create the tab element
            const tab = $('<div>')
                .addClass('script-tab')
                .text(script.name)
                .css({
                    'background-color': bgColor,
                    'color': 'white',
                    'padding': '4px 8px',
                    'border-radius': '3px',
                    'cursor': 'pointer',
                    'font-size': '10px'
                })
                .data('script-name', script.name) // Store name for identification
                .data('log-content', script.log); // Store log content

            // Add click handler to the tab
            tab.on('click', function() {
                // Handle selection style
                tabContainer.find('.script-tab').css('border', 'none').removeClass('selected-script');
                $(this).css('border', '1px solid #FFF').addClass('selected-script');

                // Update the log display
                logDisplay.text($(this).data('log-content'));
                
                // Scroll log to bottom
                const logContainer = $('#scriptlogs-container');
                logContainer.scrollTop(logContainer[0].scrollHeight);
            });

            tabContainer.append(tab);
        });
        
        // Try to re-select the previously active tab
        let selectedTab = tabContainer.find(`.script-tab`).filter(function() {
            return $(this).data('script-name') === previouslySelected;
        });

        if (selectedTab.length > 0) {
            selectedTab.trigger('click'); // Re-select the same tab to update its log
        } else if (tabContainer.children().length > 0) {
            // If previous selection is gone, or none was selected, select the first one
            tabContainer.children().first().trigger('click');
        } else {
            // No scripts are being monitored
            logDisplay.text('No scripts to display. Please select scripts in the settings.');
        }

        timestampDisplay.text(new Date().toLocaleTimeString());

    }).fail(function() {
        $('#scriptlogs-logs').text('Error loading script states.');
    });
}