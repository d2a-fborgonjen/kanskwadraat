jQuery(document).ready(function($) {
    const runSyncBtn = $('#run-sync');
    const toggleLogBtn = $('.toggle-logging');
    const logging = $('.logging');
    let pollTimer;

    toggleLogBtn.on('click', function() {
        logging.toggle();
        toggleLogBtn.text(logging.is(':visible') ? 'Verberg logging' : 'Toon logging');
    });

    runSyncBtn.on('click', () => {
        disableButton(runSyncBtn);
        startSync();
        startPolling();
    });

    function startSync() {
        setSyncStatusClass('updated');
        postAction('cv_run_sync', {}, handleSyncResponse);
    }

    function startPolling() {
        const poll = () => {
            getAction('cv_get_sync_progress', handlePollingResponse);
        };
        pollTimer = setTimeout(poll, 1000);
    }

    function handleSyncResponse(response) {
        if (!response.success) {
            showSyncError('Synchronisatie is mislukt', response.data?.logs);
        }
    }

    function handlePollingResponse(response) {
        if (!response.success) {
            showSyncError('Synchronisatie is mislukt', response.data?.logs);
            return;
        }
        if (response.data.running) {
            updateProgress(response.data.progress);
            pollTimer = setTimeout(() => getAction('cv_get_sync_progress', handlePollingResponse), 1000);
        } else {
            showLastSyncTime();
            setSyncLogEntries(response.data.logs);
            enableButton(runSyncBtn);
        }
    }

    // Helpers
    function postAction(action, data, callback) {
        $.post(coachview_ajax.ajax_url, { action, ...data }, callback);
    }

    function getAction(action, callback) {
        $.get(coachview_ajax.ajax_url, { action }, callback);
    }

    function setSyncStatusText(text) {
        $('#sync-status p').text(text);
    }

    function setSyncLogEntries(logs) {
        const container = $('#sync-log');
        if (!logs || logs.length === 0) {
            container.html('<p>Geen recente sync logs.</p>');
            return;
        }
        let html = '<table class="wp-list-table widefat fixed striped" style="margin-top:8px">';
        html += '<thead><tr><th style="width:150px">Datum</th><th style="width:60px">Level</th><th>Bericht</th></tr></thead><tbody>';
        logs.forEach(entry => {
            html += `<tr><td>${entry.created_at}</td><td>${entry.level.toUpperCase()}</td><td>${entry.message}</td></tr>`;
        });
        html += '</tbody></table>';
        container.html(html);
    }

    function setSyncStatusClass(className) {
        $('#sync-status').removeClass().addClass(className);
    }

    function updateProgress(numProcessed) {
        setSyncStatusText(`${Math.floor(parseFloat(numProcessed))}% gesynchroniseerd`);
    }

    function showLastSyncTime() {
        const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false };
        const formatted = new Date().toLocaleString('nl-NL', options).replace(',', '');
        setSyncStatusText('Laatste synchronisatie ' + formatted);
    }

    function showSyncError(message, logs) {
        setSyncStatusText(message);
        setSyncStatusClass('error');
        setSyncLogEntries(logs);
        enableButton(runSyncBtn);
    }

    function disableButton(btn) {
        btn.prop("disabled", true);
    }

    function enableButton(btn) {
        btn.prop("disabled", false);
    }
});
