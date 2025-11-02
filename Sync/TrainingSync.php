<?php

namespace Coachview\Sync;

use Coachview\Sync\Dataloaders\TrainingDataloader;
use Coachview\Sync\Models\Training;
use Coachview\Sync\Models\TrainingType;
use Illuminate\Support\Collection;

use SimplePie\Exception;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Simple;
use WC_Product_Variation;
use WC_Product_Attribute;


class TrainingSync {

    public static function run(): void
    {
        TrainingSync::report_progress(0, 1);
        $take = get_option('training_import_limit', 1);
        $training_types = TrainingDataloader::load_training_types($take, [TrainingSync::class, 'report_progress']);
        $training_types->each(function(TrainingType $training_type, $idx) {
            try {
//                error_log(print_r($training_type, true));
                if ($training_type->get_course_format() == 'elearning') {
                    $product = TrainingSync::__save_single_product($training_type);
                    $product_id = $product->get_id();
                    error_log("$idx. [SAVE] Single Product [$training_type->code] WP ID [$product_id]");
                } else {
                    $product = TrainingSync::__save_variable_product($training_type);
                    $variations = TrainingSync::__save_variations($product, $training_type->trainings);
                    TrainingSync::__archive_stale_variations($product, $training_type->trainings);

                    $product_id = $product->get_id();
                    $num_variations = $variations->count();

                    error_log("$idx. [SAVE] Variable Product [$training_type->code] WP ID [$product_id] Num variations: [$num_variations]");
                }
                TrainingSync::__save_product_categories($product, $training_type);
            } catch (Exception $e) {
                log_cv_exception("Save[TrainingType::" . $training_type->code . "]", $e);
            }
        });
    }

    public static function report_progress(int $done, int $total): void
    {
        $progress = round(($done / $total) * 100, 2);
        update_option('coachview_sync_num_processed', $progress);
    }

    private static function __ensure_term_exists(string $term, ?int $parent, string $taxonomy): ?int {
        $term_exists = term_exists($term, $taxonomy);
        if ($term_exists && is_array($term_exists) && $term_exists['taxonomy'] === $taxonomy) {
            return $term_exists['term_id'];
        }

        $args = ['slug' => sanitize_title($term)];
        if ($parent) {
            $args['parent'] = $parent;
        }

        $new_term = wp_insert_term($term, $taxonomy, $args);
        if (is_wp_error($new_term)) {
            error_log("Error inserting term '$term' in taxonomy '$taxonomy': " . $new_term->get_error_message());
            return null;
        }

        return is_array($new_term) ? $new_term['term_id'] : (int)$new_term;
    }

    private static function __save_product_categories(WC_Product $product, TrainingType $training_type): void {
        $product_cat_ids = [];
        $location_category_id = get_or_create_category('Locatie');
        if ($location_category_id !== null) {
            foreach ($training_type->get_locations() as $location) {
                $product_cat_ids[] = get_or_create_category($location, $location_category_id);
            }
        }

        $training_type_category_id = get_or_create_category('Lesvorm');
        if ($training_type_category_id !== null) {
            $product_cat_ids[] = get_or_create_category($training_type->get_course_format()->value, $training_type_category_id);
        }

        foreach($training_type->categories as $category) {
            $term = get_term_by('name', $category, 'product_cat');
            if ($term) {
                $product_cat_ids[] = $term->term_id;
            }
        }
        wp_set_object_terms($product->get_id(), $product_cat_ids, 'product_cat', false);
    }


    public static function __save_single_product(TrainingType $training_type): WC_Product_Simple
    {
        $product = get_product_by_cv_id($training_type->id) ?? new WC_Product_Simple();
        TrainingSync::__set_product_info($product, $training_type);
        $product->set_manage_stock(false);
        $product->save();
        return $product;
    }

    public static function __save_variable_product(TrainingType $training_type): WC_Product_Variable
    {
        $product = get_product_by_cv_id($training_type->id) ?? new WC_Product_Variable();
        if ($product instanceof WC_Product_Simple) {
            $product = new WC_Product_Variable($product->get_id());
        }

        TrainingSync::__set_product_info($product, $training_type);

        $training_attribute = new WC_Product_Attribute();
        $training_attribute->set_name('training_code');
        $training_attribute->set_options($training_type->trainings->pluck('code')->toArray());
        $training_attribute->set_visible(true);
        $training_attribute->set_variation(true);

        $product->set_attributes([$training_attribute]);
        $product->save();

        return $product;
    }

    private static function __set_product_info(WC_Product $product, TrainingType $training_type): void
    {
        $product->set_name($training_type->name);
        $product->set_sku($training_type->code);
        $product->set_regular_price($training_type->price);
        $product->update_meta_data('training_duration', $training_type->num_half_days);
        $product->update_meta_data('num_locations', count($training_type->get_locations()));
        $product->update_meta_data('locations', $training_type->get_locations());
        $product->update_meta_data('start_date', $training_type->trainings->pluck('start_date')->min());
        // one of: elearning, klassikaal, blended
        $product->update_meta_data('training_type_category', $training_type->get_course_format()->value);
        // one of: default, elearning, list
//        $product->update_meta_data('registration_type', $training_type->get_registration_type());

        $product->set_description($training_type->description);
        if ($product->get_id() === 0) {
            $product->set_virtual(true);
            $product->set_status('draft');
            $product->add_meta_data('coachview_id', $training_type->id, true);
        }

        $product->set_status('publish');
    }


    private static function __save_variations(WC_Product_Variable $product, Collection $trainings): Collection
    {
        return $trainings->map(function(Training $training) use ($product) {
            $variation = get_product_variation_by_sku($training->code)?? new WC_Product_Variation();
            if ($product->get_id() === 0) {
                $variation->set_manage_stock(true);
                $variation->set_status('publish');
            }
            $variation->set_parent_id($product->get_id());
            $variation->set_attributes(['training_code' => $training->code]);
            $variation->set_sku($training->code);
            $variation->set_regular_price($product->get_regular_price());
            $variation->set_manage_stock(true);
            $variation->set_stock_quantity($training->num_seats_available);

            $variation->update_meta_data('coachview_id', $training->id);
            $variation->update_meta_data('location',  firstNonEmpty($training->components->pluck('location')));
            $variation->update_meta_data('address', firstNonEmpty($training->components->pluck('address')));
            $variation->update_meta_data('zipcode', firstNonEmpty($training->components->pluck('zipcode')));
            $variation->update_meta_data('city', firstNonEmpty($training->components->pluck('city')));
            $variation->update_meta_data('planning', json_encode($training->components));
            $variation->update_meta_data('start_day', $training->start_day);
            $variation->update_meta_data('start_date', $training->start_date);
            $variation->update_meta_data('end_date', $training->end_date);
            $variation->update_meta_data('total_study_hours', $training->total_study_hours);
            $variation->update_meta_data('total_days', $training->total_days);
            $variation->update_meta_data('num_seats_taken', $training->num_seats_taken);
            $variation->update_meta_data('num_seats_available', $training->num_seats_available);
            $variation->update_meta_data('min_seats', $training->min_seats);
            $variation->update_meta_data('max_seats', $training->max_seats);
            $variation->save();

            return $variation;
        });
    }

    private static function __archive_stale_variations(WC_Product_Variable $product, Collection $current_trainings): void
    {
        $current_codes = $current_trainings->pluck('code')->toArray();

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation instanceof WC_Product_Variation) {
                continue;
            }

            $attributes = $variation->get_attributes();
            $variation_code = $attributes['training_code'] ?? null;

            if (!$variation_code || !in_array($variation_code, $current_codes)) {
                $variation->set_status('private');
                $variation->set_stock_quantity(0);
                $variation->set_manage_stock(true);
                $variation->save();

                error_log("[ARCHIVE] Archived stale training. Product variation ID [$variation_id] code: [$variation_code]");
            }
        }
    }

}