<?php

namespace Coachview\Sync;
use Coachview\Constants;
use Coachview\Helpers\Logger;
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
        update_option(Constants::OPTION_SYNC_RUNNING, true);
        update_option(Constants::OPTION_SYNC_STARTED, current_time('mysql'));
        update_option(Constants::OPTION_SYNC_FINISHED, null);
        Logger::info('Sync started', 'sync');
    }

    private static function onSynchronizationFinished(): void
    {
        TrainingAgenda::clear_cached_agenda_data();

        update_option(Constants::OPTION_SYNC_RUNNING, false);
        update_option(Constants::OPTION_SYNC_FINISHED, current_time('mysql'));
        Logger::info('Sync finished', 'sync');
    }
}
