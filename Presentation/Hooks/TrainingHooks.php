<?php

namespace Coachview\Presentation\Hooks;

class TrainingHooks {

    public function __construct()
    {
        add_action('woocommerce_variable_add_to_cart', [$this, 'render_training_details'], 25);
    }

    public function render_training_details(): void
    {
        global $product;
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        $atts = ['id' => $product->get_id()];
        $shortcode = '[cv_training_details id="' . $atts['id'] . '"]';
        echo do_shortcode($shortcode);
    }
}