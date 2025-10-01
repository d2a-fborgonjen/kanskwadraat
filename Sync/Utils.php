<?php

namespace Coachview\Sync;

use WC_Product;
use WP_Query;

function get_product_by_cv_id(string $cv_id): ?WC_Product
{
    $query = new WP_Query([
        'post_type'  => 'product',
        'meta_query' => [
            [
                'key'   => 'coachview_id',
                'value' => $cv_id
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


function get_item_count(string $type): int
{
    $query = new WP_Query([
        'post_type'      => 'product',
        'tax_query'      => [
            [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => $type,
            ],
        ],
        'meta_query'     => [
            [
                'key'     => 'coachview_id',
                'compare' => 'EXISTS',
            ],
        ],
        'fields'         => 'ids',
        'nopaging'       => true
    ]);
    return count($query->posts);
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