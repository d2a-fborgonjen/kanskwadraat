jQuery(document).ready(function($) {
    const runSyncBtn = $('#run-sync');
    let pollTimer;

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
            showSyncError('Synchronisatie is mislukt', response.data?.error_log);
        }
    }

    function handlePollingResponse(response) {
        if (!response.success) {
            showSyncError('Synchronisatie is mislukt', response.data?.error_log);
            return;
        }

        if (response.data.running) {
            updateProgress(response.data.num_processed);
            pollTimer = setTimeout(() => getAction('cv_get_sync_progress', handlePollingResponse), 1000);
        } else {
            showLastSyncTime();
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

    function setSyncErrorLogText(text) {
        $('#sync-error-log pre').text(text || '');
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

    function showSyncError(message, errorLog) {
        setSyncStatusText(message);
        setSyncStatusClass('error');
        setSyncErrorLogText(errorLog);
        enableButton(runSyncBtn);
    }

    function disableButton(btn) {
        btn.prop("disabled", true);
    }

    function enableButton(btn) {
        btn.prop("disabled", false);
    }
});
