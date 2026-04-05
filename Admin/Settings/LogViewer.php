<?php

namespace Coachview\Admin\Settings;

use Coachview\Helpers\Logger;

class LogViewer
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu']);
        add_action('admin_init', [$this, 'handle_actions']);
    }

    public function add_submenu(): void
    {
        add_submenu_page(
            'coachview',
            'Coachview Logs',
            'Logs',
            'manage_options',
            'coachview-logs',
            [$this, 'render_page']
        );
    }

    /**
     * Handle clear / prune actions via POST.
     */
    public function handle_actions(): void
    {
        if (!isset($_POST['coachview_log_action'])) {
            return;
        }
        if (!check_admin_referer('coachview_log_actions', '_cv_log_nonce')) {
            return;
        }

        $action = sanitize_text_field($_POST['coachview_log_action']);

        if ($action === 'clear') {
            Logger::clear();
            add_settings_error('coachview_logs', 'cleared', 'Alle logs zijn gewist.', 'updated');
        }

        if ($action === 'prune') {
            $days    = max(1, (int) ($_POST['prune_days'] ?? 30));
            $deleted = Logger::prune($days);
            add_settings_error('coachview_logs', 'pruned', "Logs ouder dan {$days} dagen verwijderd ({$deleted} regels).", 'updated');
        }
    }

    public function render_page(): void
    {
        // Current filters
        $level   = sanitize_text_field($_GET['level']   ?? '');
        $channel = sanitize_text_field($_GET['channel'] ?? '');
        $paged   = max(1, (int) ($_GET['paged'] ?? 1));
        $per_page = 50;

        $filters = array_filter([
            'level'   => $level,
            'channel' => $channel,
        ]);

        $total   = Logger::count($filters);
        $entries = Logger::query(array_merge($filters, [
            'limit'  => $per_page,
            'offset' => ($paged - 1) * $per_page,
        ]));

        $channels    = Logger::channels();
        $total_pages = (int) ceil($total / $per_page);

        ?>
        <div class="wrap">
            <h1>Coachview Logs</h1>

            <?php settings_errors('coachview_logs'); ?>

            <!-- Filters -->
            <form method="get" style="margin-bottom:12px;display:flex;gap:8px;align-items:end;">
                <input type="hidden" name="page" value="coachview-logs">

                <div>
                    <label for="level"><strong>Level</strong></label><br>
                    <select name="level" id="level">
                        <option value="">Alle</option>
                        <?php foreach (['info', 'warn', 'error'] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php selected($level, $l); ?>><?php echo ucfirst($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="channel"><strong>Kanaal</strong></label><br>
                    <select name="channel" id="channel">
                        <option value="">Alle</option>
                        <?php foreach ($channels as $ch): ?>
                            <option value="<?php echo esc_attr($ch); ?>" <?php selected($channel, $ch); ?>><?php echo esc_html($ch); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="button">Filteren</button>
                    <a href="<?php echo admin_url('admin.php?page=coachview-logs'); ?>" class="button">Reset</a>
                </div>
            </form>

            <!-- Actions -->
            <form method="post" style="margin-bottom:12px;display:flex;gap:8px;align-items:end;">
                <?php wp_nonce_field('coachview_log_actions', '_cv_log_nonce'); ?>
                <button type="submit" name="coachview_log_action" value="clear" class="button"
                        onclick="return confirm('Weet je zeker dat je alle logs wilt wissen?');">
                    Alles wissen
                </button>
                <input type="number" name="prune_days" value="30" min="1" max="365" style="width:70px;">
                <button type="submit" name="coachview_log_action" value="prune" class="button">
                    Oude logs verwijderen (dagen)
                </button>
            </form>

            <p class="description"><?php echo esc_html($total); ?> logregels gevonden.</p>

            <!-- Log table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th style="width:160px">Datum</th>
                    <th style="width:70px">Level</th>
                    <th style="width:100px">Kanaal</th>
                    <th>Bericht</th>
                    <th style="width:40%">Context</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="5">Geen logregels gevonden.</td></tr>
                <?php endif; ?>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry->created_at); ?></td>
                        <td><?php echo self::level_badge($entry->level); ?></td>
                        <td><?php echo esc_html($entry->channel); ?></td>
                        <td><?php echo esc_html($entry->message); ?></td>
                        <td>
                            <?php if ($entry->context): ?>
                                <details>
                                    <summary>Toon details</summary>
                                    <pre style="white-space:pre-wrap;word-break:break-all;max-height:300px;overflow:auto;background:#f5f5f5;padding:6px;font-size:12px;"><?php echo esc_html($entry->context); ?></pre>
                                </details>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $total_pages,
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function level_badge(string $level): string
    {
        $colors = [
            'info'  => '#0073aa',
            'warn'  => '#dba617',
            'error' => '#dc3232',
        ];
        $color = $colors[$level] ?? '#666';
        $label = esc_html(strtoupper($level));
        return "<span style=\"display:inline-block;padding:2px 8px;border-radius:3px;color:#fff;background:{$color};font-size:12px;font-weight:600;\">{$label}</span>";
    }
}

