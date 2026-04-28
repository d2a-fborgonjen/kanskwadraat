<?php

namespace Coachview\Presentation\Components;

use Coachview\Constants;
use Coachview\Helpers\Assets;
use Coachview\Helpers\Formatting;
use Coachview\Helpers\Logger;
use Coachview\Helpers\MetaHelpers;
use Coachview\Helpers\Training;
use Coachview\Helpers\Url;
use WC_Product_Variation;

/**
 * Lists the available trainings (product variations) for the given training type (product)
 */
class TrainingTypeStartDates extends ShortCodeComponent
{
    public static function get_shortcode(): string
    {
        return 'cv_training_type_start_dates';
    }

    public function enqueue_styles(): void
    {
        Assets::enqueueStyle(self::get_shortcode(), 'css/training-type-start-dates.css');
    }

    public function enqueue_scripts(): void
    {
        Assets::enqueueScript(self::get_shortcode(), 'js/training-type-start-dates.js', ['jquery']);
    }

    public function render_shortcode($atts): string
    {
        $atts = shortcode_atts(['id' => null], $atts, self::get_shortcode());
        return $this->render_start_dates($atts['id']);
    }

    public function render_start_dates($product_id): string {
        try {
            global $post;
            $product = wc_get_product($product_id ?: $post->ID);

            if (!$product || !$product->is_type('variable')) {
                return 'Geen startdata beschikbaar.';
            }
            $variations = $this->get_future_variations($product);

            // Prepare data for template
            $template_data = [
                'product_id' => $product->get_id(),
                'variations' => $this->prepare_variations_data($variations)
            ];

            return $this->render_template($template_data);
        } catch (Exception $e) {
            Logger::error('Render['.self::get_shortcode().']: ' . $e->getMessage(), 'sync', [
                'exception' => get_class($e),
                'trace'     => $e->getTraceAsString(),
            ]);
            return 'Er is een fout opgetreden bij het tonen van ' . self::get_shortcode();
        }
    }

    private function get_future_variations($product): array {
        $variation_ids = $product->get_children();
        $variations = [];
        $tomorrow = strtotime('tomorrow 00:00:00');
        foreach ( $variation_ids as $variation_id ) {
            $variation = wc_get_product($variation_id);
            $startDate = strtotime(MetaHelpers::get_string($variation_id, Constants::META_START_DATE));
            if ($startDate && $startDate >= $tomorrow ) {
                $variations[] = $variation;
            }
        }

        // sort by start date
        usort($variations, function($a, $b) {
            $startDateA = strtotime(MetaHelpers::get_string($a->get_id(), Constants::META_START_DATE));
            $startDateB = strtotime(MetaHelpers::get_string($b->get_id(), Constants::META_START_DATE));
            return $startDateA <=> $startDateB;
        });

        return $variations;
    }

    private function prepare_variations_data(array $variations): array
    {
        $prepared_variations = [];
        foreach ($variations as $variation) {
            $variation_id = $variation->get_id();
            $startDate = MetaHelpers::get_string($variation_id, Constants::META_START_DATE);
            $date = Formatting::displayDate(strtotime($startDate));
            $link = Url::get_register_page_url(['woo_vid' => $variation_id]);

            $prepared_variations[] = [
                'id' => $variation_id,
                'date' => $date,
                'link' => $link,
                'is_in_stock' => $variation->is_in_stock(),
                'price' => is_numeric($variation->get_price()) ? number_format_i18n($variation->get_price()) : '',
                'location' => MetaHelpers::get_string($variation_id, Constants::META_LOCATION),
                'city'     => MetaHelpers::get_string($variation_id, Constants::META_CITY),
                'address'  => MetaHelpers::get_string($variation_id, Constants::META_ADDRESS),
                'zipcode'  => MetaHelpers::get_string($variation_id, Constants::META_ZIPCODE),
                'planning' => Training::prepare_planning_data($variation)
            ];
        }
        return $prepared_variations;
    }


}