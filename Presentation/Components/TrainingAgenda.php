<?php

namespace Coachview\Presentation\Components;

use Coachview\Presentation\TemplateEngine;

/**
 * Lists the available trainings (product variations) for the given training type (product)
 */
class TrainingAgenda
{
    private static $agenda_data_key = 'cv_training_agenda_data';

    public function __construct() {
        add_shortcode('cv_training_agenda', [$this, 'render_training_agenda']);
    }

    public function render_training_agenda($atts): string
    {
        $agenda_items = $this->get_agenda_items();
        if (isset($atts['max_items']) && is_numeric($atts['max_items'])) {
            $agenda_items = array_slice($agenda_items, 0, $atts['max_items']);
        }

        // Use TemplateEngine to render the template
        $template_engine = new TemplateEngine();
        return $template_engine->render('training-agenda', [
            'placeholder_image_url' => cv_assets_url('img/example_training4.png'),
            'agenda_items' => $agenda_items,
        ]);
    }

    private function get_agenda_items(): array {
        $cached_agenda_items = $this->get_cached_agenda_items();
        if ($cached_agenda_items) {
            return $cached_agenda_items;
        }

        $tomorrow = strtotime('tomorrow 00:00:00');
        $trainings = wc_get_products([
            'limit'      => -1,
            'status'     => 'publish',
            'type'       => 'variation',
            'meta_query' => [
                ['key' => 'start_date', 'compare' => '>', date('Y-m-d', $tomorrow)]
            ]
        ]);

        $agenda_items = [];
        foreach ($trainings as $training) {
            $parent_id = $training->get_parent_id();
            if (!isset($agenda_items[$parent_id])) {
                $training_type = $this->get_training_type_data($parent_id);
                if (!$training_type) {
                    continue;
                }
                $agenda_items[$parent_id] = [
                    'trainings' => [],
                    'training_type' => $training_type
                ];
            }
            $agenda_items[$parent_id]['trainings'][] = $this->get_training_data($training);
        }

        foreach ($agenda_items as &$item) {
            // Sort trainings by start_date
            usort($item['trainings'], function ($a, $b) {
                return $a['start_date_ts'] - $b['start_date_ts'];
            });
        }

        // Sort grouped items by first start_date
        usort($agenda_items, function ($a, $b) {
            return $a['trainings'][0]['start_date_ts'] - $b['trainings'][0]['start_date_ts'];
        });

        $this->cache_agenda_items($agenda_items);
        return $agenda_items;
    }

    /**
     * Gets the training (Opleiding) data
     * @param $training  The product variation that represents the training
     * @return array
     */
    private function get_training_data($training): array {
        $id = $training->get_id();
        $start_date_ts = strtotime(get_post_meta($id, 'start_date', true));
        return [
            'city' => get_post_meta($id, 'city', true),
            'start_date_ts' => $start_date_ts,
            'start_date_display' => $this->get_display_date($start_date_ts),
            'register_link' => coachview_register_page_url(['woo_vid' => $id]), // woo_vid = WooCommerce Variation ID
        ];
    }

    /**
     * Gets the training type (Opleidingssoort) data
     * @param $id           The ID of the product that represents the training type
     * @return array|null
     */
    private function get_training_type_data($id): array | null {
        $training_type = wc_get_product($id);
        if (!$training_type) {
            return null;
        }
        $image_id = $training_type->get_image_id();
        $image_url = $image_id
            ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail')
            : cv_assets_url('img/example_training4.png');

        return [
            'name' => $training_type->get_name(),
            'image_url' => $image_url,
            'permalink' => $training_type->get_permalink(),
        ];
    }

    /**
     * Caches the agenda items in the WordPress options table along with the current timestamp
     *
     * @param array $agenda_items
     * @return void
     */
    private function cache_agenda_items(array $agenda_items): void {
        $cache_data = [
            'agenda_items' => $agenda_items,
            'timestamp' => time()
        ];
        update_option(TrainingAgenda::$agenda_data_key, $cache_data, false); // Save updated data
    }

    /**
     * Retrieves cached agenda items if they exist and are not expired
     * @return array|null
     */
    private function get_cached_agenda_items(): array | null {
        $cached_data = get_option(TrainingAgenda::$agenda_data_key, null);
        $expiration_time = 1 * 60 * 60;
        if ($cached_data && isset($cached_data['agenda_items']) && $cached_data['timestamp'] > time() - $expiration_time) {
            return $cached_data['agenda_items'];
        }
        return null;
    }

    public static function clear_cached_agenda_data(): void {
        delete_option(TrainingAgenda::$agenda_data_key);
    }

    private function get_display_date($timestamp): string {
        $now = time();
        if (date('Y', $timestamp) != date('Y', $now)) {
            return wp_date('j F Y', $timestamp);
        } else {
            return wp_date('j F', $timestamp);
        }
    }
}