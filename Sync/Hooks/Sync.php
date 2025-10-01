<?php

namespace Coachview\Sync\Hooks;

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
        wp_send_json_success([
            'num_processed' =>  get_option('coachview_sync_num_processed', 0),
            'running' =>  get_option('coachview_sync_running'),
            'started' =>  get_option('coachview_sync_started'),
            'finished' =>  get_option('coachview_sync_finished'),
        ]);
    }
}