<?php

namespace Coachview\Sync;


use mysql_xdevapi\Exception;

class SyncRunner
{
    public static function run(): void
    {
        SyncRunner::onSynchronizationStart();
        CategorySync::run();
        TrainingSync::run();
        SyncRunner::onSynchronizationFinished();
    }

    private static function onSynchronizationStart(): void
    {
        update_option('coachview_sync_running', true);
        update_option('coachview_sync_started', current_time('mysql'));
        update_option('coachview_sync_error', null);
        update_option('coachview_sync_finished', null);
        error_log('Coachview sync started at ' . current_time('mysql'));
    }

    private static function onSynchronizationFinished(): void
    {
        update_option('coachview_sync_running', false);
        update_option('coachview_sync_finished', current_time('mysql'));
        error_log('Coachview sync finished at ' . current_time('mysql'));
    }
}
