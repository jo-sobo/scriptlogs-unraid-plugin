/* global $ */

// Configuration is provided via window.scriptlogsConfig from Scriptlogs.page
function scriptlogs_status() {
    const config = window.scriptlogsConfig || {};
    const enabledScripts = config.enabledScripts || [];
    const tabContainer = $('#script-tabs-container');
    const logContainer = $('#scriptlogs-container');
    const logViewport = $('#scriptlogs-log-viewport');
    const logDisplay = $('#scriptlogs-logs');
    const timestampDisplay = $('#scriptlogs-timestamp');
    const scrollTarget = logViewport.length ? logViewport.get(0) : logContainer.get(0);

    if (!enabledScripts || enabledScripts.length === 0) {
        tabContainer.empty();
        logDisplay.text('No scripts selected. Please check your Scriptlogs settings.');
        if (timestampDisplay.length) {
            timestampDisplay.text(new Date().toLocaleTimeString());
        }
        return;
    }

    const previouslySelected = tabContainer.find('.selected-script').attr('data-script-name');
    const wasScrolledToBottom = scrollTarget
        ? scrollTarget.scrollHeight - Math.ceil(scrollTarget.scrollTop) - scrollTarget.clientHeight <= 1
        : false;

    $.getJSON('/plugins/scriptlogs/scriptlogs_api.php?action=get_script_states', function(scripts) {
        tabContainer.empty();

        if (Array.isArray(scripts) && scripts.length > 0) {
            scripts.forEach(script => {
                const tab = $('<button type="button">')
                    .addClass('script-tab')
                    .toggleClass('script-tab--running', script.status === 'running')
                    .attr('data-script-name', script.name)
                    .attr('aria-pressed', 'false')
                    .attr('aria-label', `${script.name} ${script.status}`)
                    .text(script.name)
                    .data('log-content', script.log);

                tab.on('click', function() {
                    const current = $(this);
                    tabContainer.find('.script-tab')
                        .removeClass('selected-script')
                        .attr('aria-pressed', 'false');
                    current.addClass('selected-script').attr('aria-pressed', 'true');
                    logDisplay.text(current.data('log-content'));
                    if (scrollTarget) {
                        scrollTarget.scrollTop = scrollTarget.scrollHeight;
                    }
                });

                tabContainer.append(tab);
            });

            const selectedTab = previouslySelected
                ? tabContainer.find(`.script-tab[data-script-name="${CSS.escape(previouslySelected)}"]`)
                : $();

            if (selectedTab.length > 0) {
                selectedTab.addClass('selected-script').attr('aria-pressed', 'true');
                const activeScript = scripts.find(s => s.name === previouslySelected);
                if (activeScript) {
                    logDisplay.text(activeScript.log);
                    if (scrollTarget) {
                        scrollTarget.scrollTop = scrollTarget.scrollHeight;
                    }
                }
            } else if (tabContainer.children().length > 0) {
                tabContainer.children().first().trigger('click');
            }
        } else {
            logDisplay.text('No scripts selected to display.');
        }

        if (wasScrolledToBottom && scrollTarget) {
            scrollTarget.scrollTop = scrollTarget.scrollHeight;
        }

        if (timestampDisplay.length) {
            timestampDisplay.text(new Date().toLocaleTimeString());
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Scriptlogs AJAX Error:', textStatus, errorThrown);
        tabContainer.empty();
        logDisplay.text('Error loading script states. Check browser console (F12) for details.');
        if (timestampDisplay.length) {
            timestampDisplay.text(new Date().toLocaleTimeString());
        }
    });
}

$(function() {
    const config = window.scriptlogsConfig || {};
    const widgetRoot = $('.scriptlogs-body');

    if (widgetRoot.length) {
        widgetRoot.toggleClass('scriptlogs-body--responsive', !!config.isResponsive);
        widgetRoot.toggleClass('scriptlogs-body--legacy', !config.isResponsive);
    }

    scriptlogs_status();

    if (config.refreshEnabled && config.refreshInterval > 0) {
        setInterval(scriptlogs_status, config.refreshInterval);
    }
});