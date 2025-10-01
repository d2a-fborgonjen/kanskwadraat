jQuery(document).ready(function($) {
    let runSyncBtn = $('#run-sync');
    let pollTimer;

    runSyncBtn.on('click', function() {
        startSync();
        startPolling();
    });

    function startSync() {
        setSyncStatusClass('updated');
        runSyncBtn.prop("disabled", true);
        $.post(coachview_ajax.ajax_url, { action: 'cv_run_sync' }, function(response) {
            if (response.success) {
                const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false };
                const formatted = new Date().toLocaleString('nl-NL', options).replace(',', '');
                setSyncStatusText('Laatste synchronisatie ' + formatted);
                runSyncBtn.prop("disabled", false);
            } else {
                setSyncStatusText('Synchronisatie is mislukt');
                setSyncStatusClass('error');
            }
        });
    }

    function startPolling() {
        function poll() {
            $.get(coachview_ajax.ajax_url, { action: 'cv_get_sync_progress' }, function(data) {
                if (data.success) {
                    if (data.data.running) {
                        setSyncStatusText(Math.floor(parseFloat(data.data.num_processed)) + '% gesynchroniseerd');
                        pollTimer = setTimeout(poll, 1000);
                    }
                }
            });
        }
        pollTimer = setTimeout(poll, 1000);
    }

    function setSyncStatusText(text) {
        $('#sync-status p').text(text);
    }
    function setSyncStatusClass(className) {
        $('#sync-status').removeClass().addClass(className);
    }
});