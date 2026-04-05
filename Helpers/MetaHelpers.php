<?php

namespace Coachview\Helpers;

use Coachview\Constants;

/**
 * Lightweight wrapper helpers around get_post_meta / update_post_meta / delete_post_meta
 * so meta keys are centralized via Constants and usage is consistent.
 */
class MetaHelpers
{
    public static function get_int(int $post_id, string $meta_key, int $default = 0): int
    {
        $value = get_post_meta($post_id, $meta_key, true);
        if ($value === '' || $value === null) {
            return $default;
        }
        return (int) $value;
    }

    public static function get_string(int $post_id, string $meta_key, string $default = ''): string
    {
        $value = get_post_meta($post_id, $meta_key, true);
        if ($value === '' || $value === null) {
            return $default;
        }
        return (string) $value;
    }

    /**
     * @return array<mixed>
     */
    public static function get_array(int $post_id, string $meta_key): array
    {
        $value = get_post_meta($post_id, $meta_key, true);
        return is_array($value) ? $value : [];
    }

    public static function exists(int $post_id, string $meta_key): bool
    {
        return (bool) get_post_meta($post_id, $meta_key, true);
    }

    public static function update(int $post_id, string $meta_key, $value): void
    {
        update_post_meta($post_id, $meta_key, $value);
    }

    public static function delete(int $post_id, string $meta_key): void
    {
        delete_post_meta($post_id, $meta_key);
    }

    // ──────────────────────────────────────────────
    //  Convenience helpers for common Coachview meta
    // ──────────────────────────────────────────────

    public static function coachview_id(int $post_id): ?string
    {
        $value = self::get_string($post_id, Constants::META_COACHVIEW_ID, '');
        return $value === '' ? null : $value;
    }

    public static function form_participant_header(int $post_id, string $default = ''): string
    {
        return self::get_string($post_id, Constants::META_FORM_PARTICIPANT_HEADER, $default);
    }

    public static function form_contact_person_header(int $post_id, string $default = ''): string
    {
        return self::get_string($post_id, Constants::META_FORM_CONTACT_PERSON_HEADER, $default);
    }


    /**
     * @return array<string>
     */
    public static function payment_methods(int $post_id): array
    {
        $value = get_post_meta($post_id, Constants::META_PRODUCT_PAYMENT_METHODS, true);
        return is_array($value) ? $value : [];
    }
}
