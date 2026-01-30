<?php
namespace Coachview\Cron;

use Coachview\Sync\SyncRunner;

class Cron {

    public function __construct() {
        add_action('coachview_cron_noon', [SyncRunner::class, 'run']);
        add_action('coachview_cron_night', [SyncRunner::class, 'run']);
    }

    public static function activate() {
        if (!wp_next_scheduled('coachview_cron_noon')) {
            wp_schedule_event(strtotime('12:00:00'), 'daily', 'coachview_cron_noon');
        }

        if (!wp_next_scheduled('coachview_cron_night')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'coachview_cron_night');
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('coachview_cron');
        wp_clear_scheduled_hook('coachview_cron_noon');
        wp_clear_scheduled_hook('coachview_cron_night');
    }
}
