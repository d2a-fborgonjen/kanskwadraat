<?php

namespace Coachview\Presentation\Shortcodes;

use Coachview\Presentation\TemplateEngine;

/**
 * Lists the available trainings (product variations) for the given training type (product)
 */
class TrainingStartDates
{
    public function __construct() {
        add_shortcode('cv_training_start_dates', [$this, 'apply_start_dates_shortcode']);
    }

    public function apply_start_dates_shortcode($atts): string
    {
        $atts = shortcode_atts(['id' => null], $atts, 'cv_training_start_dates');
        return $this->render_start_dates($atts['id']);
    }

    public function render_start_dates($product_id): string {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            return '';
        }

        // TODO: Filter based on dates?
        $variations = $product->get_available_variations('products');

        wp_enqueue_style('coachview-training', plugin_dir_url(__FILE__) . '../../assets/css/training-start-dates.css');

        // Prepare data for template
        $template_data = [
            'product_id' => $product->get_id(),
            'variations' => $this->prepare_variations_data($variations)
        ];

        $template_engine = new TemplateEngine();
        return $template_engine->render('training-start-dates', $template_data);
    }

    private function prepare_variations_data(array $variations): array
    {
        $prepared_variations = [];
        
        foreach ($variations as $variation) {
            $variation_id = $variation->get_id();
            $startDate = get_post_meta($variation_id, 'start_date', true);
            $date = date_i18n('j F', strtotime($startDate));
            $link = home_url('/aanmelden/') . '?vid=' . $variation_id;

            $prepared_variations[] = [
                'id' => $variation_id,
                'date' => $date,
                'link' => $link,
                'is_in_stock' => $variation->is_in_stock(),
                'price' => wc_price($variation->get_price())
            ];
        }
        return $prepared_variations;
    }
}