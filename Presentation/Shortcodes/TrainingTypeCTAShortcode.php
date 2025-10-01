<?php

namespace Coachview\Presentation\Shortcodes;

use Coachview\Presentation\Enums\RegistrationType;
use function Coachview\Presentation\create_link_button;

/**
 * Shortcode to display Call to Action (CTA)
 */
class TrainingTypeCTAShortcode {
    public function __construct() {
        add_shortcode('cv_training_call_to_action', [$this, 'render_cta_button']);
    }

    public function render_cta_button($atts): string {
        global $product;
        $atts = shortcode_atts([
            'id' => $product->get_id() ?? 0,
        ], $atts, 'cv_training_type_details');
        $product = wc_get_product($atts['id']);
        if (!$product) {
            return '';
        }

        $registration_type = get_registration_type($product);
        $order_page = get_page_by_path('aanmelden');
        $register_link = get_permalink($order_page->ID) . '?pid=' . $product->get_id();

        if ($product->is_type('variable')) {
            if ($registration_type === RegistrationType::ENLIST) {
                return create_link_button('Aanmelden wachtlijst', $register_link, 'lg');
            } else {
                return create_link_button('Bekijk startdata', '#startdata', 'lg');
            }
        } else {
            return create_link_button('Aanmelden', $register_link, 'lg');
        }
    }
}