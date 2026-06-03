<?php

namespace Coachview\Sync\Hooks;

use Coachview\Constants;
use Coachview\Helpers\Logger;
use Coachview\Sync\SyncRunner;

class Sync {

    public function __construct()
    {
        add_action( 'wp_ajax_cv_run_sync', [$this, 'run']);
        add_action( 'wp_ajax_cv_get_sync_progress', [$this, '__get_sync_progress']);
    }

    public function run(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Sync started']);
        ignore_user_abort(true);
        flush();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        SyncRunner::run();
        exit;
    }

    public function __get_sync_progress(): void
    {
        $started = get_option(Constants::OPTION_SYNC_STARTED);
        $sync_errors = Logger::query(['level' => 'error', 'channel' => 'sync', 'since' => $started, 'limit' => 20]);

        if (!empty($sync_errors)) {
            wp_send_json_error([
                'logs' => $sync_errors,
            ]);
        } else {
            wp_send_json_success([
                'progress' => get_option(Constants::OPTION_SYNC_PROGRESS, 0),
                'running'  => get_option(Constants::OPTION_SYNC_RUNNING),
                'started'  => $started,
                'finished' => get_option(Constants::OPTION_SYNC_FINISHED),
                'logs'     => Logger::query(['channel' => 'sync', 'since' => $started, 'limit' => 20]),
            ]);
        }
    }
}