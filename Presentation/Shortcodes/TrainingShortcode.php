<?php

namespace Coachview\Presentation\Shortcodes;

use WC_Product_Variation;
use function Coachview\Presentation\create_link_button;

class TrainingShortcode {
    public function __construct() {
        add_shortcode('cv_training_details', [$this, 'renderTrainingDetails']);
    }

    public function renderTrainingDetails($atts): string {
        global $product;

        $atts = shortcode_atts([
            'id' => $product->get_id(),
        ], $atts, 'cv_training_details');

        $product = wc_get_product($atts['id']);
        if (!$product || !$product->is_type('variable')) {
            return '';
        }
        $variations = $product->get_available_variations('products');
        wp_enqueue_style('coachview-training', plugin_dir_url(__FILE__) . '../../assets/css/coachview-training.css');

        ob_start();
        echo '<div class="trainings e-con-full e-flex e-con e-child" data-id="' . $product->get_id() . '" data-element_type="container">';
        foreach ($variations as $variation) {
            $this->render_single_training($variation, $product->get_id());
        }
        echo '</div>';
        return ob_get_clean();
    }

    private static function render_single_training(WC_Product_Variation $variation, string $training_id): void
    {
        $variation_id = $variation->get_id();
        $price = wc_price($variation->get_price());
        $location = collect(get_post_meta($variation_id, 'location', true))->first() ?? 'Onbekend';
        $startDate = get_post_meta($variation_id, 'start_date', true);
        $day = date_i18n('l', strtotime($startDate));
        $date = date_i18n('j F', strtotime($startDate));

        $link = home_url('/aanmelden/') . '?vid=' . $variation_id;

        ?>
        <div class="training-info e-con-full e-flex e-con e-child" data-element_type="container" data-training-id="<?= $training_id ?>">
            <div class="training-info--start e-con-full e-flex e-con e-child" data-element_type="container">
                <div class="training-info--start--day elementor-widget-container"><h5 class="elementor-heading-title"><?= $day ?></h5></div>
                <div class="training-info--start--date elementor-widget-container"><h3 class="elementor-heading-title"><?= $date ?></h3></div>
            </div>
            <div class="training-info--location e-con-full e-flex e-con e-child">
                <svg style="max-width: 30px;" aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                    <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/>
                </svg>
                <h3><?= $location ?></h3>
            </div>
            <div class="training-info--show-info e-con-full e-flex e-con e-child">
                <?php echo create_link_button('Toon informatie', '#', 'sm'); ?>
            </div>
            <div class="training-info--apply e-con-full e-flex e-con e-child">
                <div class="elementor-widget-container">
                <?php
                    if ($variation->is_in_stock()) {
                        echo create_link_button('Aanmelden', $link, 'md', 'training-apply-button');
                     } else {
                       echo create_link_button('Vol', '#', 'md', 'training-apply-button disabled');
                    }
                ?>
                </div>
            </div>
        </div>
        <?php
    }
}