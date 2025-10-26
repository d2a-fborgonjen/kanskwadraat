<?php

namespace Coachview\Admin;

use Coachview\Sync\SyncRunner;
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
        wp_enqueue_script('coachview-synchronization', plugin_dir_url(__FILE__) . '../assets/js/synchronization.js', array('jquery'), null, true);
        wp_localize_script('coachview-synchronization', 'coachview_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);

        $counts = [
            "trainingType" => get_item_count('variable') + get_item_count('simple'),
            "training" => get_item_count('variation'),
        ];

        $last_sync = get_option('coachview_sync_finished');
        if ($last_sync) {
            $formatted_date = date_i18n(get_option('date_format') . ' om ' . get_option('time_format'), strtotime($last_sync));
            echo '<div id="sync-status" class="updated"><p>Laatste synchronisatie ' . esc_html($formatted_date) . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Coachview</h1>
            <button id="run-sync" class="button button-primary">
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

            <div id="sync-error-log"><pre></pre></div>
        </div>
        <?php
    }
}
