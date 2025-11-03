<?php

namespace Coachview\Presentation\Components;

use Coachview\Sync\Models\Enums\CourseFormat;
use Coachview\Presentation\TemplateEngine;
use WC_Product_Variation;

/**
 * Lists the available trainings (product variations) for the given training type (product)
 */
class TrainingTypeStartDates
{
    public function __construct() {
        add_shortcode('cv_training_type_start_dates', [$this, 'apply_start_dates_shortcode']);
    }

    public function apply_start_dates_shortcode($atts): string
    {
        $atts = shortcode_atts(['id' => null], $atts, 'cv_training_type_start_dates');
        return $this->render_start_dates($atts['id']);
    }

    public function render_start_dates($product_id): string {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            return '';
        }

        // TODO: Filter based on dates?
        $variations = $product->get_available_variations('products');

        wp_enqueue_style('coachview-common', plugin_dir_url(__FILE__) . '../../assets/css/common.css');
        wp_enqueue_style('coachview-training-type-start-dates', plugin_dir_url(__FILE__) . '../../assets/css/training-type-start-dates.css');
        wp_enqueue_script('coachview-training-type-start-dates', plugin_dir_url(__FILE__) . '../../assets/js/training-type-start-dates.js', array('jquery'), null, true);

        // Prepare data for template
        $template_data = [
            'product_id' => $product->get_id(),
            'variations' => $this->prepare_variations_data($variations),
            'assets_url' => plugin_dir_url(__FILE__) . '../../assets/'
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
                'price' => wc_price($variation->get_price()),
                'location' => get_post_meta($variation_id, 'location', true),
                'city' => get_post_meta($variation_id, 'city', true),
                'address' => get_post_meta($variation_id, 'address', true),
                'zipcode' => get_post_meta($variation_id, 'zipcode', true),
                'planning' => $this->prepare_planning_data($variation)
            ];
        }
        return $prepared_variations;
    }

    private function prepare_planning_data(WC_Product_Variation $variation): array
    {
        $planningJson = get_post_meta($variation->get_id(), 'planning', true);
        $planningEvents = json_decode($planningJson, true) ?? [];

        $first_date = collect($planningEvents)->pluck('date')->filter()->sort()->first();
        return array_map(function($event) use ($first_date) {
            $entry = [];
            $entry['course_format'] = $event['course_format'];
            $entry['name'] = $event['name'];

            if (!empty($event['start_time'])) {
                $entry['time'] = date_i18n('H:i', strtotime($event['start_time']));
                if (!empty($event['end_time'])) {
                    $entry['time'] .= ' - ' . date_i18n('H:i', strtotime($event['end_time']));
                }
            }

            if (!empty($event['date'])) {
                $entry['formatted_date'] = date_i18n('D. j M. Y', strtotime($event['date']));
            } else if ($event['course_format'] == CourseFormat::E_LEARNING->value && !empty($first_date)) {
                $elearning_date = date('Y-m-d', strtotime($first_date . ' -1 day'));;
                $entry['formatted_date'] = date_i18n('D. j M. Y', strtotime($elearning_date));
                $entry['time'] = '23:00';
            }
            return $entry;
        }, $planningEvents);
    }

}