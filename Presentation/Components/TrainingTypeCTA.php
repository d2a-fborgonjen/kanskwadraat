<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\RegistrationType;

/**
 * Shortcode to display Call to Action (CTA)
 */
class TrainingTypeCTA extends ShortCodeComponent {

    public static function get_shortcode(): string {
        return 'cv_training_type_call_to_action';
    }
    public function enqueue_scripts(): void {}
    public function enqueue_styles(): void {}

    public function render_shortcode($atts): string {
        global $product;
        $atts = shortcode_atts([
            'id' => $product ? $product->get_id() : 0,
        ], $atts, self::get_shortcode());

        $product = wc_get_product($atts['id']);
        if (!$product) {
            return '<span class="d-none">cv_training_type_call_to_action not used on product page or invalid product ID.</span>';
        }

        $registration_type = cv_get_registration_type($product);
        $register_link = coachview_register_page_url(['woo_pid' => $product->get_id()]);

        if ($product->is_type('variable')) {
            if ($registration_type === RegistrationType::ENLIST) {
                return $this->create_primary_button('Inschrijven wachtlijst', $register_link);
            } else {
                return $this->create_primary_button('Bekijk startdata', '#startdata');
            }
        } else {
            return $this->create_primary_button('Inschrijven', $register_link);
        }
    }

    /**
     * Create a link button with the given text and URL.
     *
     * @param string $text The text to display on the button.
     * @param string $url The URL the button should link to.
     * @return string The HTML for the link button.
     */
    function create_primary_button(string $text, string $url): string
    {
        return '<div class="d-flex">
            <a class="btn btn-primary" href="' . esc_url($url) . '">' . $text . '</a>
        </div>';
    }

}