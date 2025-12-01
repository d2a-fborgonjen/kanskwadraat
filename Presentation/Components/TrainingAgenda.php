<?php

namespace Coachview\Presentation\Components;

use Coachview\Presentation\TemplateEngine;

/**
 * Lists the available trainings (product variations) for the given training type (product)
 */
class TrainingAgenda
{
    public function __construct() {
        add_shortcode('cv_training_agenda', [$this, 'renderTrainingAgenda']);
    }

    public function renderTrainingAgenda($atts): string
    {
        // Query all trainings that have the meta key
        $trainings = wc_get_products([
            'limit'      => -1,
            'status'     => 'publish',
            'meta_key'   => 'start_dates', // change to your key
            'meta_compare' => 'EXISTS',
        ]);

        $items = [];
        $now = time();
        foreach ($trainings as $training) {
            $start_dates = get_post_meta($training->get_id(), 'start_dates', true);
            if (!is_array($start_dates)) {
                continue;
            }

            $image_id = $training->get_image_id();
            $image_url = $image_id ?  wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';

            foreach ($start_dates as $start_date) {
                if (empty($start_date['start_date'])) {
                    continue;
                }
                $start = strtotime($start_date['start_date']);
                if ($start > $now) {
                    $items[] = [
                        'name' => $training->name,
                        'image_url' => $image_url,
                        'product_url' => $training->get_permalink(),
                        'start_date' => $start,
                        'display_date' => $start_date['display_date'],
                        'all_start_dates' => $start_dates,
                    ];
                }
            }
        }

        // Sort by start_date
        usort($items, function ($a, $b) {
            return $a['start_date'] <=> $b['start_date'];
        });

        // Take first 5 items
        $trainings = array_slice($items, 0, 5);

        // Use TemplateEngine to render the template
        $template_engine = new TemplateEngine();
        return $template_engine->render('training-agenda', ['trainings' => $trainings]);
    }
}