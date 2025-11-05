/* global $ */

// Configuration is provided via window.scriptlogsConfig from Scriptlogs.page
function scriptlogs_updateCompactIndicators(scripts) {
    const compactContainer = $('#scriptlogs-compact-indicators');
    if (!compactContainer.length) return;
    
    compactContainer.empty();
    
    let runningCount = 0;

    if (Array.isArray(scripts) && scripts.length > 0) {
        scripts.forEach(script => {
            const indicator = $('<span>')
                .addClass('scriptlogs-compact-indicator')
                .toggleClass('scriptlogs-compact-indicator--running', script.status === 'running')
                .text(script.name);
            compactContainer.append(indicator);

            if (script.status === 'running') {
                runningCount += 1;
            }
        });
    }


    const summaryText = $('#scriptlogs-head-summary-text');
    if (summaryText.length) {
        summaryText.text(
            runningCount > 0
                ? `${runningCount} running`
                : 'No scripts running'
        );
    }
}

function scriptlogs_status() {
    const config = window.scriptlogsConfig || {};
    const enabledScripts = config.enabledScripts || [];
    const tabContainer = $('#script-tabs-container');
    const logContainer = $('#scriptlogs-container');
    const logDisplay = $('#scriptlogs-logs');
    const timestampDisplay = $('#scriptlogs-timestamp');
    const autoscrollCheckbox = $('#scriptlogs-autoscroll');
    const scrollTarget = logContainer.length ? logContainer.get(0) : null;
    const autoscrollEnabled = autoscrollCheckbox.length ? autoscrollCheckbox.prop('checked') : true;

    if (!enabledScripts || enabledScripts.length === 0) {
        tabContainer.empty();
        logDisplay.text('No scripts selected. Please check your Scriptlogs settings.');
        scriptlogs_updateCompactIndicators([]);
        if (timestampDisplay.length) {
            timestampDisplay.text(new Date().toLocaleTimeString());
        }
        return;
    }

    const previouslySelected = tabContainer.find('.selected-script').attr('data-script-name');
    const wasScrolledToBottom = autoscrollEnabled && scrollTarget
        ? scrollTarget.scrollHeight - Math.ceil(scrollTarget.scrollTop) - scrollTarget.clientHeight <= 1
        : false;

    $.getJSON('/plugins/scriptlogs/scriptlogs_api.php?action=get_script_states', function(scripts) {
        tabContainer.empty();
        
        // Update compact indicators
        scriptlogs_updateCompactIndicators(scripts);

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
                    if (autoscrollEnabled && scrollTarget) {
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
                    if (autoscrollEnabled && scrollTarget) {
                        scrollTarget.scrollTop = scrollTarget.scrollHeight;
                    }
                }
            } else if (tabContainer.children().length > 0) {
                tabContainer.children().first().trigger('click');
            }
        } else {
            logDisplay.text('No scripts selected to display.');
        }

        if (autoscrollEnabled && wasScrolledToBottom && scrollTarget) {
            scrollTarget.scrollTop = scrollTarget.scrollHeight;
        }

        if (timestampDisplay.length) {
            timestampDisplay.text(new Date().toLocaleTimeString());
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('Scriptlogs AJAX Error:', textStatus, errorThrown);
        tabContainer.empty();
        scriptlogs_updateCompactIndicators([]);
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

    // Apply font size setting to the log container and pre element
    if (config.fontSize) {
        const logContainer = $('#scriptlogs-container');
        const logPre = $('#scriptlogs-logs');
        if (logContainer.length) {
            logContainer.css('font-size', config.fontSize);
        }
        if (logPre.length) {
            logPre.css('font-size', config.fontSize);
        }
    }

    // Setup custom toggle for compact indicators (show when collapsed, hide when expanded)
    const compactWrapper = $('#scriptlogs-compact-wrapper');
    
    // Watch the collapsible row which gets toggled by Unraid's openClose() function
    const collapsibleRow = $('.dash_scriptlogs_toggle');
    
    if (compactWrapper.length && collapsibleRow.length) {
        // Function to update compact indicator visibility
        function updateCompactVisibility() {
            const isHidden = collapsibleRow.css('display') === 'none';
            // Show compact when content is hidden, hide when content is shown
            compactWrapper.css('display', isHidden ? 'flex' : 'none');
        }
        
        // Create MutationObserver to watch for style/class changes on the row
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'style' || mutation.attributeName === 'class') {
                    updateCompactVisibility();
                }
            });
        });
        
        observer.observe(collapsibleRow[0], {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
        
        // Set initial state
        updateCompactVisibility();
        
        // Also check periodically in case toggle happens without mutation
        setInterval(updateCompactVisibility, 500);
    }

    scriptlogs_status();

    if (config.refreshEnabled && config.refreshInterval > 0) {
        setInterval(scriptlogs_status, config.refreshInterval);
    }
});