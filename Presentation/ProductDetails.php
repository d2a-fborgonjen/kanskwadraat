<?php

namespace Coachview\Presentation;

class ProductDetails
{
    public function __construct()
    {
        add_action('woocommerce_after_single_product_summary', [$this, 'renderProductDetails'], 25);
    }

    public function renderProductDetails(): void
    {
        global $product;
        if (!$product->is_type('variable')) {
            return;
        }

        echo '<h3>Coachview training details</h3>';
        echo '<p>' . get_post_meta($product->get_id(), 'training_duration', true) . '</p>';
        echo '<p>' . get_post_meta($product->get_id(), 'num_locations', true) . ' locaties</p>';
        echo '<p>' . get_post_meta($product->get_id(), 'training_type_category', true) . '</p>';
    }
}
