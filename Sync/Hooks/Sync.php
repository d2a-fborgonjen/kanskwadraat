<?php

namespace Coachview\Sync\Hooks;

use Coachview\Constants;
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
        $error_messages = get_option(Constants::OPTION_SYNC_ERROR_LOG);
        if ($error_messages) {
            wp_send_json_error([
                'error_log' =>  $error_messages,
                'info_log'  => get_option(Constants::OPTION_SYNC_INFO_LOG),
            ]);
        } else {
            wp_send_json_success([
                'progress' => get_option(Constants::OPTION_SYNC_PROGRESS, 0),
                'running'  => get_option(Constants::OPTION_SYNC_RUNNING),
                'started'  => get_option(Constants::OPTION_SYNC_STARTED),
                'finished' => get_option(Constants::OPTION_SYNC_FINISHED),
                'info_log' => get_option(Constants::OPTION_SYNC_INFO_LOG),
            ]);
        }
    }
}