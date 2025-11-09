<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\RegistrationType;
use function Coachview\Presentation\create_link_button;

/**
 * Shortcode to display Call to Action (CTA)
 */
class TrainingTypeCTA {
    public function __construct() {
        add_shortcode('cv_training_call_to_action', [$this, 'render_cta_button']);
    }

    public function render_cta_button($atts): string {
        global $product;
        $atts = shortcode_atts([
            'id' => $product->get_id() ?? 0,
        ], $atts, 'cv_training_call_to_action');

        $product = wc_get_product($atts['id']);
        if (!$product) {
            return '';
        }

        $registration_type = get_registration_type($product);
        $register_link = site_url('/aanmelden/') . '?pid=' . $product->get_id();

        wp_enqueue_style('coachview-font', cv_assets_url('fonts/poppins.css'));
        wp_enqueue_style('coachview-common', cv_assets_url('css/common.css'));
        if ($product->is_type('variable')) {
            if ($registration_type === RegistrationType::ENLIST) {
                return create_link_button('Aanmelden wachtlijst', $register_link);
            } else {
                return create_link_button('Bekijk startdata', '#training-start-dates');
            }
        } else {
            return create_link_button('Aanmelden', $register_link);
        }
    }
}