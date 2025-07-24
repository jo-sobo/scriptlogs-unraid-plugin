/**
 * ScriptLogs Dashboard Plugin JavaScript
 * Handles the frontend functionality for displaying user script logs
 */

let scriptLogsData = {
    scripts: [],
    activeScript: null,
    refreshInterval: null,
    logRefreshInterval: null
};

/**
 * Initialize the script logs dashboard
 */
function initScriptLogsDashboard() {
    console.log('Initializing ScriptLogs Dashboard...');
    
    // Initial load
    loadRunningScripts();
    
    // Set up auto-refresh every 5 seconds for running scripts
    scriptLogsData.refreshInterval = setInterval(loadRunningScripts, 5000);
    
    // Set up log refresh every 2 seconds for active script
    scriptLogsData.logRefreshInterval = setInterval(refreshActiveScriptLog, 2000);
}

/**
 * Load running scripts from the API
 */
function loadRunningScripts() {
    fetch('/plugins/scriptlogs/scripts/get_running_scripts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateScriptsList(data.scripts);
            } else {
                console.error('Failed to load running scripts:', data.error);
            }
        })
        .catch(error => {
            console.error('Error loading running scripts:', error);
        });
}

/**
 * Update the scripts list and tabs
 */
function updateScriptsList(scripts) {
    scriptLogsData.scripts = scripts;
    
    const tabsContainer = document.getElementById('scriptlogs-tabs');
    const panelsContainer = document.getElementById('scriptlogs-panels');
    const noScriptsDiv = document.getElementById('no-scripts');
    
    // Clear existing content
    tabsContainer.innerHTML = '';
    panelsContainer.innerHTML = '';
    
    if (scripts.length === 0) {
        // Show no scripts message
        noScriptsDiv.style.display = 'flex';
        panelsContainer.appendChild(noScriptsDiv);
        scriptLogsData.activeScript = null;
        return;
    }
    
    // Hide no scripts message
    noScriptsDiv.style.display = 'none';
    
    // Keep track of previously active script
    let foundActiveScript = false;
    
    // Create tabs and panels for each script
    scripts.forEach((script, index) => {
        // Create tab
        const tab = createScriptTab(script, index);
        tabsContainer.appendChild(tab);
        
        // Create panel
        const panel = createScriptPanel(script, index);
        panelsContainer.appendChild(panel);
        
        // Check if this was the previously active script
        if (scriptLogsData.activeScript === script.name) {
            foundActiveScript = true;
            activateScript(script.name, index);
        }
    });
    
    // If no previously active script found, activate the first one
    if (!foundActiveScript && scripts.length > 0) {
        activateScript(scripts[0].name, 0);
    }
}

/**
 * Create a tab element for a script
 */
function createScriptTab(script, index) {
    const tab = document.createElement('button');
    tab.className = 'scriptlogs-tab';
    tab.id = `tab-${index}`;
    tab.innerHTML = `
        <span class="script-status"></span>
        ${script.name}
    `;
    
    tab.addEventListener('click', () => {
        activateScript(script.name, index);
    });
    
    return tab;
}

/**
 * Create a panel element for a script
 */
function createScriptPanel(script, index) {
    const panel = document.createElement('div');
    panel.className = 'scriptlogs-panel';
    panel.id = `panel-${index}`;
    
    const logDiv = document.createElement('div');
    logDiv.className = 'scriptlogs-log';
    logDiv.id = `log-${index}`;
    logDiv.innerHTML = '<div class="loading">Loading logs...</div>';
    
    panel.appendChild(logDiv);
    return panel;
}

/**
 * Activate a specific script tab and load its logs
 */
function activateScript(scriptName, index) {
    // Remove active class from all tabs and panels
    document.querySelectorAll('.scriptlogs-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.scriptlogs-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Add active class to selected tab and panel
    const activeTab = document.getElementById(`tab-${index}`);
    const activePanel = document.getElementById(`panel-${index}`);
    
    if (activeTab && activePanel) {
        activeTab.classList.add('active');
        activePanel.classList.add('active');
    }
    
    // Update active script
    scriptLogsData.activeScript = scriptName;
    
    // Load logs for this script
    loadScriptLog(scriptName, index);
}

/**
 * Load logs for a specific script
 */
function loadScriptLog(scriptName, index) {
    const logDiv = document.getElementById(`log-${index}`);
    
    if (!logDiv) return;
    
    fetch(`/plugins/scriptlogs/scripts/get_script_log.php?script=${encodeURIComponent(scriptName)}&lines=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                logDiv.innerHTML = data.content || '<div class="no-logs">No log content available</div>';
                
                // Auto-scroll to bottom
                logDiv.scrollTop = logDiv.scrollHeight;
            } else {
                logDiv.innerHTML = `<div class="error">Error loading logs: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading script log:', error);
            logDiv.innerHTML = '<div class="error">Failed to load logs</div>';
        });
}

/**
 * Refresh logs for the currently active script
 */
function refreshActiveScriptLog() {
    if (!scriptLogsData.activeScript) return;
    
    // Find the index of the active script
    const scriptIndex = scriptLogsData.scripts.findIndex(script => 
        script.name === scriptLogsData.activeScript
    );
    
    if (scriptIndex !== -1) {
        loadScriptLog(scriptLogsData.activeScript, scriptIndex);
    }
}

/**
 * Manual refresh button handler
 */
function refreshScriptLogs() {
    console.log('Manual refresh triggered');
    
    // Add spinning animation to refresh icon
    const refreshIcon = document.querySelector('.scriptlogs-refresh i');
    if (refreshIcon) {
        refreshIcon.style.animation = 'spin 1s linear infinite';
        setTimeout(() => {
            refreshIcon.style.animation = '';
        }, 1000);
    }
    
    // Reload scripts
    loadRunningScripts();
}

/**
 * Cleanup function when leaving the page
 */
function cleanupScriptLogs() {
    if (scriptLogsData.refreshInterval) {
        clearInterval(scriptLogsData.refreshInterval);
    }
    if (scriptLogsData.logRefreshInterval) {
        clearInterval(scriptLogsData.logRefreshInterval);
    }
}

// Clean up when page is unloaded
window.addEventListener('beforeunload', cleanupScriptLogs);