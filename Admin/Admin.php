<?php

namespace Coachview\Admin;

use Coachview\Constants;
use Coachview\Helpers\Assets;
use Coachview\Sync\Store\TrainingDetail;

use function Coachview\Sync\get_item_count;

class Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu()
    {
        add_menu_page('Coachview', 'Coachview', 'manage_options', 'coachview', [$this, 'admin_page'], 'dashicons-welcome-learn-more', 10);
    }

    public function admin_page()
    {
        Assets::enqueueScript('coachview-synchronization', 'js/synchronization.js', ['jquery']);
        wp_localize_script('coachview-synchronization', 'coachview_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);

        $counts = [
            "trainingType" => get_item_count('product'),
            "training" => get_item_count('product_variation'),
        ];

        $last_sync = get_option(Constants::OPTION_SYNC_FINISHED);
        $last_sync_date  = $last_sync ? date_i18n(get_option('date_format') . ' om ' . get_option('time_format'), strtotime($last_sync)) : 'onbekend';
        $info_log = get_option(Constants::OPTION_SYNC_INFO_LOG, '');
        $error_log = get_option(Constants::OPTION_SYNC_ERROR_LOG, '');
        ?>

        <div id="sync-status" class="updated"><p>Laatste synchronisatie <?php echo $last_sync_date; ?></p></div>

        <div class="wrap">
            <h1>Coachview</h1>
            <button id="run-sync" class="button button-cta">
                Synchroniseer trainingen
            </button>
            <hr>

<!--            <strong>--><?php //echo coachview_api_token(true); ?><!--</strong>-->

            <h2>Statistieken</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th><strong>Type</strong></th>
                    <th><strong>Aantal</strong></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Opleidingssoorten</td>
                    <td><?php echo esc_html($counts['trainingType']); ?></td>
                </tr>
                <tr>
                    <td>Opleidingen</td>
                    <td><?php echo esc_html($counts['training']); ?></td>
                </tr>
                </tbody>
            </table>

            <h2>Logging</h2>
            <hr>
            <button class="button button-cta toggle-logging">Toon logging</button>

            <div class="logging" style="display: none">
                <h3>Info</h3>
                <div id="sync-info-log">
                    <pre><?php echo $info_log ?? 'geen logging'; ?></pre>
                </div>

                <h3>Fouten</h3>
                <div id="sync-error-log">
                    <pre><?php echo $error_log ?? 'geen logging'; ?></pre>
                </div>
            </div>
        </div>
        <?php
    }
}
