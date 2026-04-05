<?php

namespace Coachview\Admin\Settings;

use Coachview\Constants;
use Coachview\Helpers\Assets;
use Coachview\Helpers\Logger;
use WP_Query;

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
            "trainingType" => $this->get_item_count('product'),
            "training" => $this->get_item_count('product_variation'),
        ];

        $last_sync = get_option(Constants::OPTION_SYNC_FINISHED);
        $last_sync_date  = $last_sync ? date_i18n(get_option('date_format') . ' om ' . get_option('time_format'), strtotime($last_sync)) : 'onbekend';
        $recent_logs = Logger::query(['channel' => 'sync', 'limit' => 15]);
        ?>

        <div id="sync-status" class="updated"><p>Laatste synchronisatie <?php echo $last_sync_date; ?></p></div>

        <div class="wrap">
            <h1>Coachview</h1>
            <button id="run-sync" class="button button-cta">
                Synchroniseer trainingen
            </button>
            <hr>

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

            <h2>Recente sync logs</h2>
            <hr>
            <button class="button button-cta toggle-logging">Toon logging</button>

            <div class="logging" style="display: none">
                <div id="sync-log">
                    <?php if (empty($recent_logs)): ?>
                        <p>Geen recente sync logs.</p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped" style="margin-top:8px">
                            <thead><tr><th style="width:150px">Datum</th><th style="width:60px">Level</th><th>Bericht</th></tr></thead>
                            <tbody>
                            <?php foreach ($recent_logs as $entry): ?>
                                <tr>
                                    <td><?php echo esc_html($entry->created_at); ?></td>
                                    <td><?php echo esc_html(strtoupper($entry->level)); ?></td>
                                    <td><?php echo esc_html($entry->message); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <p><a href="<?php echo admin_url('admin.php?page=coachview-logs&channel=sync'); ?>" class="button" style="margin-top:8px;">Bekijk alle logs →</a></p>
            </div>
        </div>
        <?php
    }

    private function get_item_count(string $product_type): int
    {
        $query = new WP_Query([
                'post_type'      => $product_type,
                'meta_query'     => [
                        [
                                'key'     => Constants::META_COACHVIEW_ID,
                                'compare' => 'EXISTS',
                        ],
                ],
                'fields'         => 'ids',
                'nopaging'       => true
        ]);
        return count($query->posts);
    }
}
