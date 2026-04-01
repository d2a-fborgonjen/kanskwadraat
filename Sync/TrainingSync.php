<?php

namespace Coachview\Sync;

use Coachview\Constants;
use Coachview\Models\Enums\CourseFormat;
use Coachview\Models\Training;
use Coachview\Models\TrainingType;
use Exception;
use Illuminate\Support\Collection;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class TrainingSync {

    public static function run(): void
    {
        TrainingSync::report_progress(0, 1);
        $take = get_option(Constants::TRAINING_IMPORT_LIMIT, 1000);
        $training_types = Dataloaders\TrainingDataloader::load_training_types($take, [TrainingSync::class, 'report_progress']);
        $training_types->each(function(TrainingType $training_type, $idx) {
            try {
                if ($training_type->get_course_format() == CourseFormat::E_LEARNING) {
                    $product = TrainingSync::__save_single_product($training_type);
                    $product_id = $product->get_id();
                    log_cv_info("Saved [$training_type->code] WP ID [$product_id]");
                } else {
                    $product = TrainingSync::__save_variable_product($training_type);
                    $variations = TrainingSync::__save_variations($product, $training_type->trainings);

                    TrainingSync::__archive_stale_variations($product, $training_type->trainings);

                    $product_id = $product->get_id();
                    $num_variations = $variations->count();
                    log_cv_info("Saved [$training_type->code] WP ID [$product_id] Num variations: [$num_variations] Price: [{$training_type->price}]");
                }
                TrainingSync::__save_product_categories($product, $training_type);
            } catch (Exception $e) {
                log_cv_exception("Save[TrainingType::" . $training_type->code . "] CV id [" . $training_type->id . "]", $e);
            }
        });
    }

    public static function report_progress(int $done, int $total): void
    {
        $progress = round(($done / $total) * 100, 2);
        update_option(Constants::OPTION_SYNC_PROGRESS, $progress);
    }

    private static function __save_product_categories(WC_Product $product, TrainingType $training_type): void {
        $product_cat_ids = [];
        $location_category_id = get_or_create_category('Locatie');
        if ($location_category_id !== null) {
            foreach ($training_type->get_cities() as $city) {
                $product_cat_ids[] = get_or_create_category($city, $location_category_id);
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
//        $product = get_product_by_cv_id($training_type->id) ?? new WC_Product_Simple();
        $product = get_product_by_sku($training_type->code) ?? new WC_Product_Simple();

        if ($product->get_id() === 0) {
            log_cv_info('NEW Product. SKU: ' . $training_type->code);
        }

        if ($product instanceof WC_Product_Variable) {
            log_cv_info('Product type mismatch: expected Simple, got Variable. Deleting and recreating as Simple.');
            $product->delete(true);
            $product = new WC_Product_Simple();
        }
        TrainingSync::__set_product_info($product, $training_type);
        $product->save();

        TrainingSync::__set_acf_repeaters($product, $training_type);

        return $product;
    }

    public static function __save_variable_product(TrainingType $training_type): WC_Product_Variable
    {
        //$product = get_product_by_cv_id($training_type->id) ?? new WC_Product_Variable();
        $product = get_product_by_sku($training_type->code) ?? new WC_Product_Variable();
        if ($product->get_id() === 0) {
            log_cv_info('NEW Product. SKU: ' . $training_type->code);
        }
        if (!($product instanceof WC_Product_Variable)) {
            log_cv_info('Product type mismatch: expected Variable, got Simple. Deleting and recreating as Variable.');
            $product->delete(true);
            $product = new WC_Product_Variable();
        }

        TrainingSync::__set_product_info($product, $training_type);

        $training_attribute = new WC_Product_Attribute();
        $training_attribute->set_name('training_code');
        $training_attribute->set_options($training_type->trainings->pluck('code')->toArray());
        $training_attribute->set_visible(true);
        $training_attribute->set_variation(true);

        $product->set_attributes([$training_attribute]);
        $product->save();

        TrainingSync::__set_acf_repeaters($product, $training_type);

        return $product;
    }

    private static function __set_product_info(WC_Product $product, TrainingType $training_type): void
    {
        $product->set_name($training_type->name);
        $product->set_sku($training_type->code);
        $product->set_regular_price($training_type->price);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        $product->update_meta_data(Constants::META_LAST_SYNC, time());
        $product->update_meta_data(Constants::META_TRAINING_GOAL, $training_type->goal);
        $product->update_meta_data(Constants::META_TRAINING_DURATION, $training_type->num_half_days);

        // one of: elearning, klassikaal, blended
        $product->update_meta_data(Constants::META_TRAINING_TYPE_CATEGORY, $training_type->get_course_format()->value);

        if ($product->get_id() === 0) {
            $product->set_virtual(true);
            $product->set_status('draft');
            $product->add_meta_data(Constants::META_COACHVIEW_ID, $training_type->id, true);
            $product->add_meta_data(Constants::META_COACHVIEW_SOURCE, coachview_test_mode_enabled() ? 'TEST' : 'PRODUCTION');
            $product->set_description($training_type->description);
        }
    }

    private static function __save_variations(WC_Product_Variable $product, Collection $trainings): Collection
    {
        return $trainings->map(function(Training $training) use ($product) {
            $variation = get_product_variation_by_sku($training->code)?? new WC_Product_Variation();
            if ($variation->get_id() === 0) {
                log_cv_info('NEW Variation. SKU: ' . $training->code);

                $variation->set_sku($training->code);
                $variation->set_manage_stock(true);
                $variation->set_parent_id($product->get_id());
                $variation->set_attributes(['training_code' => $training->code]);
                $variation->set_status('publish');

                $variation->update_meta_data(Constants::META_COACHVIEW_ID, $training->id);
                $variation->update_meta_data(Constants::META_COACHVIEW_SOURCE, coachview_test_mode_enabled() ? 'TEST' : 'PRODUCTION');
            }
            $variation->update_meta_data(Constants::META_LAST_SYNC, time());
            $variation->update_meta_data(Constants::META_LOCATION,  firstNonEmpty($training->components->pluck('location')));
            $variation->update_meta_data(Constants::META_ADDRESS, firstNonEmpty($training->components->pluck('address')));
            $variation->update_meta_data(Constants::META_ZIPCODE, firstNonEmpty($training->components->pluck('zipcode')));
            $variation->update_meta_data(Constants::META_CITY, firstNonEmpty($training->components->pluck('city')));
            $variation->update_meta_data(Constants::META_PLANNING, json_encode($training->components));
            $variation->update_meta_data(Constants::META_START_DAY, $training->start_day);
            $variation->update_meta_data(Constants::META_START_DATE, $training->start_date);
            $variation->update_meta_data(Constants::META_END_DATE, $training->end_date);
            $variation->update_meta_data(Constants::META_TOTAL_STUDY_HOURS, $training->total_study_hours);
            $variation->update_meta_data(Constants::META_TOTAL_DAYS, $training->total_days);
            $variation->update_meta_data(Constants::META_NUM_SEATS_TAKEN, $training->num_seats_taken);
            $variation->update_meta_data(Constants::META_NUM_SEATS_AVAILABLE, $training->num_seats_available);
            $variation->update_meta_data(Constants::META_MIN_SEATS, $training->min_seats);
            $variation->update_meta_data(Constants::META_MAX_SEATS, $training->max_seats);
            $variation->save();

            return $variation;
        });
    }

    /**
     * Archive variations that are no longer active
     */
    private static function __archive_stale_variations(WC_Product_Variable $product, Collection $active_trainings): void
    {
        $training_codes = $active_trainings->pluck('code')->toArray();
        $training_type_name = $product->get_name();
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation instanceof WC_Product_Variation) {
                continue;
            }
            $attributes = $variation->get_attributes();
            $training_code = $attributes['training_code'] ?? null;
            if (!$training_code || !in_array($training_code, $training_codes)) {
                wp_delete_post($variation_id, true);
//                $variation->set_status('private');
//                $variation->set_stock_quantity(0);
//                $variation->set_manage_stock(true);
//                $variation->save();
                log_cv_info("Archived stale training. Variation ID [$variation_id] code: [$training_code] TrainingType: $training_type_name");
            }
        }
    }

    private static function __set_acf_repeaters(WC_Product_Variable|WC_Product $product, TrainingType $training_type)
    {
        update_field('training_type_components', $training_type->get_training_type_components(), $product->get_id());
    }

}

