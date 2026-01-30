<?php

namespace Coachview\Sync;
use Coachview\Presentation\Components\TrainingAgenda;

class SyncRunner
{
    public static function run(): void
    {
        SyncRunner::onSynchronizationStart();
        PaymentMethodSync::run();
        CategorySync::run();
        TrainingSync::run();
        CategorySync::cleanup_after_sync();
        SyncRunner::onSynchronizationFinished();
    }

    private static function onSynchronizationStart(): void
    {
        update_option('coachview_sync_running', true);
        update_option('coachview_sync_started', current_time('mysql'));
        update_option('coachview_sync_error', null);
        update_option('coachview_sync_info', null);
        update_option('coachview_sync_finished', null);
        error_log('Coachview sync started at ' . current_time('mysql'));
    }

    private static function onSynchronizationFinished(): void
    {
        TrainingAgenda::clear_cached_agenda_data();

        update_option('coachview_sync_running', false);
        update_option('coachview_sync_finished', current_time('mysql'));
        error_log('Coachview sync finished at ' . current_time('mysql'));
    }
}
