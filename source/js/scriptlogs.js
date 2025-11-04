/* global $ */

// Configuration is provided via window.scriptlogsConfig from Scriptlogs.page
function scriptlogs_status() {
    const config = window.scriptlogsConfig || {};
    const enabledScripts = config.enabledScripts || [];

    if (!enabledScripts || enabledScripts.length === 0) {
        $('#scriptlogs-logs').text('No scripts selected. Please check your Scriptlogs settings.');
        $('#script-tabs-container').empty();
        return;
    }

    $.getJSON('/plugins/scriptlogs/scriptlogs_api.php?action=get_script_states', function(scripts) {
        const tabContainer = $('#script-tabs-container');
        const logContainer = $('#scriptlogs-container');
        const logDisplay = $('#scriptlogs-logs');
        const timestampDisplay = $('#scriptlogs-timestamp');
        const logEl = logContainer[0];

        const previouslySelected = tabContainer.find('.selected-script').data('script-name');

        const isScrolledToBottom = logEl.scrollHeight - Math.ceil(logEl.scrollTop) - logEl.clientHeight <= 1;

        tabContainer.empty();

        if (scripts && Array.isArray(scripts) && scripts.length > 0) {
            scripts.forEach(script => {
                let bgColor = '#555';
                if (script.status === 'running') bgColor = '#4CAF50';

                const tab = $('<div>')
                    .addClass('script-tab')
                    .text(script.name)
                    .css({
                        'background-color': bgColor,
                        color: 'white',
                        padding: '4px 8px',
                        'border-radius': '3px',
                        cursor: 'pointer',
                        'font-size': '10px'
                    })
                    .data('script-name', script.name)
                    .data('log-content', script.log);

                tab.on('click', function() {
                    tabContainer.find('.script-tab').removeClass('selected-script').css('border', '1px solid transparent');
                    $(this).addClass('selected-script').css('border', '1px solid #FFF');
                    logDisplay.text($(this).data('log-content'));
                    logEl.scrollTop = logEl.scrollHeight;
                });

                tabContainer.append(tab);
            });

            const selectedTab = tabContainer.find('.script-tab').filter(function() {
                return $(this).data('script-name') === previouslySelected;
            });

            if (selectedTab.length > 0) {
                selectedTab.addClass('selected-script').css('border', '1px solid #FFF');
                const activeScript = scripts.find(s => s.name === previouslySelected);
                if (activeScript) {
                    logDisplay.text(activeScript.log);
                }
            } else if (tabContainer.children().length > 0) {
                tabContainer.children().first().trigger('click');
                return;
            }
        } else {
            logDisplay.text('No scripts selected to display.');
        }

        if (isScrolledToBottom) {
            logEl.scrollTop = logEl.scrollHeight;
        }

        timestampDisplay.text(new Date().toLocaleTimeString());
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Scriptlogs AJAX Error:', textStatus, errorThrown);
        $('#scriptlogs-logs').text('Error loading script states. Check browser console (F12) for details.');
    });
}

$(function() {
    const config = window.scriptlogsConfig || {};

    if (config.refreshEnabled && config.refreshInterval > 0) {
        scriptlogs_status();
        setInterval(scriptlogs_status, config.refreshInterval);
    } else {
        scriptlogs_status();
    }
});