<?php

namespace Coachview\Admin;
use WC_Product_Variable;

class ProductList {
    public function __construct()
    {
        // Add column to product list
        add_filter('manage_edit-product_columns', [$this, 'add_product_column']);

        // Render column content
        add_action('manage_product_posts_custom_column', [$this, 'render_product_column'], 10, 2);
    }

    public function add_product_column($columns): array
    {
        // add column that shows metadata fields 'training_type_category' and 'product_type'
        $columns['product_type'] = __('Product type', 'coachview');
        $columns['training_type_category'] = __('Type', 'coachview');

        unset($columns['featured']);
        unset($columns['taxonomy-product_brand']);
        unset($columns['product_tag']);
        return $columns;
    }

    public function render_product_column($column, $post_id): void
    {
        if ($column === 'training_type_category') {
            $training_type_category = get_post_meta($post_id, 'training_type_category', true);
            if ($training_type_category) {
                echo ucfirst($training_type_category);
            } else {
                echo '-';
            }
        } else if ($column === 'product_type') {
            $product = wc_get_product($post_id);
            if ($product instanceof WC_Product_Variable) {
                $num_trainings = count($product->get_children());
                echo "Opleidinigssoort ($num_trainings trainingen)";
            } else {
                echo "Opleidingssoort (los)";
            }
        }
    }
}