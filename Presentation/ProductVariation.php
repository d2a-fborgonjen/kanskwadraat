<?php

namespace Coachview\Presentation;

class ProductVariation
{
    public function __construct()
    {
        add_action('woocommerce_variable_add_to_cart', [$this, 'renderProductVariations'], 25);
        remove_action('woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);

        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
        add_action( 'woocommerce_single_product_summary', [$this, 'renderAddToCartSingle'], 30 );
    }

    public function renderAddToCartSingle() {
        global $product;

        $order_page = get_page_by_path('aanmelden');
        $link = get_permalink($order_page->ID) . '?pid=' . $product->get_id();
        echo '<h3>Coachview training details</h3>';

        echo '<p>' . get_post_meta($product->get_id(), 'num_locations', true) . ' locaties</p>';
        echo '<p>' . get_post_meta($product->get_id(), 'training_type_category', true) . '</p>';
        echo '<a href="'.$link.'">Aanmelden</a>';
    }

    public function renderProductVariations(): void
    {
        global $product;
        if (!$product->is_type('variable')) {
            return;
        }

        $variations = $product->get_available_variations();
        echo '<div class="trainings e-con-full e-flex e-con e-child" data-id="' . $product->get_id() . '" data-element_type="container">';

        foreach ($variations as $variation) {
            self::renderSingleVariation($variation);
        }

        echo '</div>';
    }

    private static function renderSingleVariation(array $variation): void
    {
        $variation_id = $variation['variation_id'];
        $price = wc_price($variation['display_price']);
        $location = collect(get_post_meta($variation_id, 'locations', true))->first() ?? 'Onbekend';
        $startDate = get_post_meta($variation_id, 'start_date', true);
        $day = date_i18n('l', $startDate);
        $date = date_i18n('j F', $startDate);

        $order_page = get_page_by_path('aanmelden');
        $link = get_permalink($order_page->ID) . '?var_id=' . $variation_id;

        ?>
        <div class="training-info e-con-full e-flex e-con e-child" data-element_type="container" data-training-id="<?= $id ?>">
            <div class="training-info--start e-con-full e-flex e-con e-child" data-element_type="container">
                <div class="training-info--start--day elementor-widget-container"><h5 class="elementor-heading-title"><?= $day ?></h5></div>
                <div class="training-info--start--date elementor-widget-container"><h3 class="elementor-heading-title"><?= $date ?></h3></div>
            </div>
            <div class="start-date--location e-con-full e-flex e-con e-child">
                <svg aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                    <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/>
                </svg>
                <h3><?= $location ?></h3>
            </div>
            <div class="training-info--show-info e-con-full e-flex e-con e-child">
                <div class="elementor-widget-container"><a href="#"><span class="elementor-icon-list-text">Toon informatie</span></a></div>
            </div>
            <div class="training-info--apply e-con-full e-flex e-con e-child">
                <div class="elementor-widget-container">
                    <a class="elementor-button elementor-button-link elementor-size-sm apply-button" href="<?= $link ?>">
                        <span class="elementor-button-content-wrapper"><span class="elementor-button-text">Aanmelden</span></span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
