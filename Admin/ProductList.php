<?php

namespace Coachview\Admin;
use WC_Product_Variable;

class ProductList {
    public function __construct()
    {
        add_filter('manage_edit-product_columns', [$this, 'add_product_column']);
        add_action('manage_product_posts_custom_column', [$this, 'render_product_column'], 10, 2);
    }

    public function add_product_column($columns): array
    {
        $columns['coachview_data'] = __('Coachview gegevens', 'coachview');

        unset($columns['featured']);
        unset($columns['taxonomy-product_brand']);
        unset($columns['product_tag']);
        return $columns;
    }

    public function render_product_column($column, $post_id): void
    {
        if ($column === 'coachview_data') {
            $data = '';

            // Training type category
            $training_type_category = get_post_meta($post_id, 'training_type_category', true);
            if ($training_type_category) {
                $data .= '<strong>Type:</strong> ' . ucfirst($training_type_category) . '<br>';
            }

            // Last sync
            $last_sync_ts = get_post_meta($post_id, 'cv_last_sync', true);
            if ($last_sync_ts) {
                $data .= '<strong>Laatste sync:</strong> ' . wp_date('j M Y H:i', intval($last_sync_ts)) . '<br>';
            }

            // Aantal trainingen
            $product = wc_get_product($post_id);
            if ($product instanceof WC_Product_Variable) {
                $num_trainings = count($product->get_children());
                if ($num_trainings > 0) {
                    $data .= '<strong>Aantal trainingen:</strong> ' . $num_trainings . '<br>';
                }
            }

            echo $data;
        }
    }
}