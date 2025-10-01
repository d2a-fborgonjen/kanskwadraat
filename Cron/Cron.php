<?php
namespace Coachview\Cron;

use Coachview\Sync\SyncRunner;

class Cron {

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('coachview_cron', [SyncRunner::class, 'run']);
    }

    private function activate() {
        if (!wp_next_scheduled('coachview_cron')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'coachview_cron');
        }
    }

    private static function deactivate() {
        wp_clear_scheduled_hook('coachview_cron');
    }
}
