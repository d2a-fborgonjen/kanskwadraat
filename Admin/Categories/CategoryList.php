<?php

namespace Coachview\Admin\Categories;

use Coachview\Constants;

class CategoryList {
    public function __construct()
    {
        add_filter('manage_edit-product_cat_columns', [$this, 'add_column']);
        add_action('manage_product_cat_custom_column', [$this, 'render_column'], 10, 3);
    }

    public function add_column($columns): array
    {
        $title = __('Coachview synchronisatie', 'coachview');
        $columns['last_sync'] = '<span class="dashicons dashicons-update" title="' . esc_attr($title) . '"></span>';
        return $columns;
    }

    public function render_column($ignore, $column, $term_id): void
    {
        if ($column === 'last_sync') {
            $last_sync = get_term_meta($term_id, Constants::META_LAST_SYNC, true);
            if ($last_sync) {
                $readable_date = wp_date('j M Y H:i', intval($last_sync));
                $days_ago = (time() - intval($last_sync)) / 86400;
                if ($days_ago <= 1) {
                    echo '<span class="dashicons dashicons-yes-alt" style="color: green;" title="Gesynchroniseerd op ' . esc_attr($readable_date) . '"></span>';
                } else {
                    echo '<span class="dashicons dashicons-warning" style="color: red;" title="Gesynchroniseerd op ' . esc_attr($readable_date) . '"></span>';
                }
            } else {
                echo '<span class="dashicons dashicons-no" title="Niet gesynchroniseerd vanuit coachview"></span>';
            }
        }
    }
}