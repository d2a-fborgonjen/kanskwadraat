<?php

namespace Coachview\Presentation\Hooks;

class TrainingTypeHooks {

    public function __construct()
    {
        remove_action( 'woocommerce_after_single_product', 'woocommerce_template_single_add_to_cart', 30 );
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

        add_action('woocommerce_single_product_summary', [$this, 'render_training_type_details'], 25);
        add_action('woocommerce_after_single_product', [$this, 'render_training_type_details_after_description'], 5);
    }

    public function render_training_type_details(): void
    {
        global $product;
        if (!$product) {
            return;
        }

        $atts = ['id' => $product->get_id()];
        $shortcode = '[cv_training_call_to_action id="' . $atts['id'] . '"]';
        echo do_shortcode($shortcode);
    }

    public function render_training_type_details_after_description(): void
    {
        global $product;
        if (!$product) {
            return;
        }
        $atts = ['id' => $product->get_id()];
        $shortcode = '[cv_training_start_dates id="' . $atts['id'] . '"]';
        echo do_shortcode($shortcode);
    }

//    public function renderAddToCartSingle() {
//        global $product;
//
//        $order_page = get_page_by_path('aanmelden');
//        $link = get_permalink($order_page->ID) . '?pid=' . $product->get_id();
//        echo '<h3>Coachview training details</h3>';
//
//        echo '<p>' . get_post_meta($product->get_id(), 'num_locations', true) . ' locaties</p>';
//        echo '<p>' . get_post_meta($product->get_id(), 'training_type_category', true) . '</p>';
//        echo '<a href="'.$link.'">Aanmelden</a>';
//    }
}