<?php

namespace Coachview\Sync;

use Coachview\Constants;
use WC_Product;
use WP_Query;

function get_product_by_sku(string $sku): ?WC_Product
{
    $query = new WP_Query([
        'post_type'  => 'product',
        'meta_query' => [
            [
                'key'     => '_sku',
                'value'   => $sku,
            ],
        ],
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    return !empty($query->posts) ? wc_get_product($query->posts[0]) : null;
}

function get_product_variation_by_sku(string $sku): ?WC_Product
{
    $query = new WP_Query([
        'post_type'  => 'product_variation',
        'meta_query' => [
            [
                'key'     => '_sku',
                'value'   => $sku,
            ],
        ],
        'posts_per_page' => 1,
        'fields' => 'ids',
    ]);
    return !empty($query->posts) ? wc_get_product($query->posts[0]) : null;
}



function minutes_to_time_string(int $minutes): string
{
    if ($minutes < 60) {
        return sprintf('%d min', $minutes);
    }

    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;

    if ($remaining_minutes > 0) {
        return sprintf('%d uur %d min', $hours, $remaining_minutes);
    }

    return sprintf('%d uur', $hours);
}

function get_or_create_category(string $name, int $parentId = 0): ?int
{
    $term = get_term_by('name', $name, 'product_cat');
    if ($term) {
        set_category_last_sync($term->term_id);
        return $term->term_id;
    }
    $insert = wp_insert_term($name, 'product_cat', ['parent' => $parentId]);
    if (is_wp_error($insert)) {
        return null;
    }
    set_category_last_sync((int)$insert['term_id']);
    return (int) $insert['term_id'];
}

function set_category_last_sync(int $term_id): void
{
    update_term_meta($term_id, Constants::META_LAST_SYNC, time());
}

function get_categories_by_parent_not_in(int $parent_id, $not_in): array
{
    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'parent'     => $parent_id,
        'exclude'    => $not_in,
    ]);
    return is_wp_error($terms) ? [] : $terms;
}

// Helper method to find the first non-empty value in a collection
function firstNonEmpty($array_or_collection) {
    return collect($array_or_collection)->first(fn($value) => !empty($value));
}
