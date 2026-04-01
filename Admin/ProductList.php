<?php

namespace Coachview\Admin;

use Coachview\Constants;
use Coachview\Helpers\Registration;
use Coachview\Models\Enums\RegistrationType;
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

            $training_type_category = get_post_meta($post_id, Constants::META_TRAINING_TYPE_CATEGORY, true);
            if (is_string($training_type_category) && $training_type_category !== '') {
                $data .= '<strong>Type:</strong> ' . ucfirst($training_type_category) . '<br>';
            }

            $product = wc_get_product($post_id);
            $registration_type = Registration::get_registration_type($product);
            if ($registration_type instanceof RegistrationType) {
                $data .= '<strong>Registratie type:</strong> ' . ucfirst(strtolower($registration_type->value)) . '<br>';
            }

            $last_sync_ts = get_post_meta($post_id, Constants::META_LAST_SYNC, true);
            if ($last_sync_ts) {
                $data .= '<strong>Laatste sync:</strong> ' . wp_date('j M Y H:i', intval($last_sync_ts)) . '<br>';
            }

            if ($product instanceof WC_Product_Variable) {
                $num_trainings = count($product->get_children());
                if ($num_trainings > 0) {
                    $data .= '<strong>Aantal trainingen:</strong> ' . $num_trainings . '<br>';
                }
            }

            $source = get_post_meta($post_id, Constants::META_COACHVIEW_SOURCE, true) ?: 'Onbekend';
            if (is_string($source) && $source !== '') {
                $data .= '<strong>Coachview bron:</strong> ' . ucfirst(strtolower($source)) . '<br>';
            }

            echo $data;
        }
    }
}