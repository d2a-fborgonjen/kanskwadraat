<?php

namespace Coachview\Helpers;

use Coachview\Constants;

class Logger
{
    public const LEVEL_INFO  = 'info';
    public const LEVEL_WARN  = 'warn';
    public const LEVEL_ERROR = 'error';

    /**
     * Get the full table name (with WP prefix).
     */
    public static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . Constants::LOG_TABLE;
    }

    /**
     * Create the log table if it doesn't exist. Call on plugin activation.
     */
    public static function create_table(): void
    {
        global $wpdb;
        $table   = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            level      VARCHAR(10)     NOT NULL DEFAULT 'info',
            channel    VARCHAR(60)     NOT NULL DEFAULT 'general',
            message    TEXT            NOT NULL,
            context    LONGTEXT        NULL,
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_level      (level),
            KEY idx_channel    (channel),
            KEY idx_created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('coachview_log_table_version', '1.0');
    }

    /**
     * Lightweight check — only runs create_table() if the version option is missing.
     * Safe to call on every request (no DB hit after first run).
     */
    public static function maybe_create_table(): void
    {
        if (get_option('coachview_log_table_version') !== '1.0') {
            self::create_table();
        }
    }

    /**
     * Write a log entry.
     *
     * @param string      $level   One of info / warn / error.
     * @param string      $message Human-readable message.
     * @param string      $channel Logical grouping (e.g. "order", "sync").
     * @param mixed|null  $context Any extra data (will be JSON-encoded).
     */
    public static function log(string $level, string $message, string $channel = 'general', mixed $context = null): void
    {
        global $wpdb;

        $wpdb->insert(self::table(), [
            'level'      => $level,
            'channel'    => $channel,
            'message'    => $message,
            'context'    => $context !== null ? wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
            'created_at' => current_time('mysql'),
        ]);
    }

    /* ---- Convenience shortcuts ---- */

    public static function info(string $message, string $channel = 'general', mixed $context = null): void
    {
        self::log(self::LEVEL_INFO, $message, $channel, $context);
    }

    public static function warn(string $message, string $channel = 'general', mixed $context = null): void
    {
        self::log(self::LEVEL_WARN, $message, $channel, $context);
    }

    public static function error(string $message, string $channel = 'general', mixed $context = null): void
    {
        self::log(self::LEVEL_ERROR, $message, $channel, $context);
    }

    /* ---- Querying ---- */

    /**
     * Fetch log entries with optional filters.
     *
     * @param array{level?:string,channel?:string,limit?:int,offset?:int} $args
     * @return array
     */
    public static function query(array $args = []): array
    {
        global $wpdb;
        $table = self::table();

        $where  = [];
        $values = [];

        if (!empty($args['level'])) {
            $where[]  = 'level = %s';
            $values[] = $args['level'];
        }
        if (!empty($args['channel'])) {
            $where[]  = 'channel = %s';
            $values[] = $args['channel'];
        }

        $sql = "SELECT * FROM {$table}";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC';

        $limit  = (int) ($args['limit']  ?? 100);
        $offset = (int) ($args['offset'] ?? 0);
        $sql   .= ' LIMIT %d OFFSET %d';
        $values[] = $limit;
        $values[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, ...$values));
    }

    /**
     * Count entries (with optional filters).
     */
    public static function count(array $args = []): int
    {
        global $wpdb;
        $table = self::table();

        $where  = [];
        $values = [];

        if (!empty($args['level'])) {
            $where[]  = 'level = %s';
            $values[] = $args['level'];
        }
        if (!empty($args['channel'])) {
            $where[]  = 'channel = %s';
            $values[] = $args['channel'];
        }

        $sql = "SELECT COUNT(*) FROM {$table}";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($values) {
            $sql = $wpdb->prepare($sql, ...$values);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get distinct channels for the filter dropdown.
     */
    public static function channels(): array
    {
        global $wpdb;
        $table = self::table();
        return $wpdb->get_col("SELECT DISTINCT channel FROM {$table} ORDER BY channel");
    }

    /**
     * Delete all log entries (or filtered).
     */
    public static function clear(array $args = []): void
    {
        global $wpdb;
        $table = self::table();

        if (!empty($args['level'])) {
            $wpdb->delete($table, ['level' => $args['level']]);
        } elseif (!empty($args['channel'])) {
            $wpdb->delete($table, ['channel' => $args['channel']]);
        } else {
            $wpdb->query("TRUNCATE TABLE {$table}");
        }
    }

    /**
     * Delete entries older than $days days.
     */
    public static function prune(int $days = 30): int
    {
        global $wpdb;
        $table = self::table();
        return (int) $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days)
        );
    }
}

