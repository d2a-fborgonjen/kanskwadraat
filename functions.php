<?php

use Automattic\WooCommerce\Enums\ProductStatus;
use Coachview\Api\TokenManager;
use Coachview\Constants;
use Coachview\Models\CourseFormat;
use Coachview\Models\RegistrationFormType;
use Coachview\Models\RegistrationType;

function coachview_test_mode_enabled(): bool {
    return get_option('coachview_api_mode', 'test') === 'test';
}

function coachview_api_url(): string {
    return coachview_test_mode_enabled() ?
        'https://training.coachview.net' :
        'https://kanskwadraat.coachview.com';
}

function coachview_api_client_id(): string {
    return coachview_test_mode_enabled() ?
        get_option('coachview_test_client_id') :
        get_option('coachview_client_id');
}

function coachview_api_secret(): string {
    return coachview_test_mode_enabled() ?
        get_option('coachview_test_secret') :
        get_option('coachview_secret');
}

function coachview_api_token(bool $refresh = false): string {
    return TokenManager::instance()->getToken($refresh);
}

function coachview_get_default_search_url(): string {
    $slug = Constants::DEFAULT_SEARCH_PAGE_SLUG;
    return home_url("/$slug");
}


function coachview_register_page_url(array $params): string {
    $url = coachview_get_default_register_url();
    if (has_custom_register_page()) {
        $url = get_permalink(get_option('coachview_register_page'));
    }
    return empty($params) ? $url : $url . '?' . http_build_query($params);
}

function has_custom_register_page(): bool {
    $register_page_id = get_option('coachview_register_page', 0);
    return $register_page_id && get_post_status($register_page_id) === 'publish';
}

function coachview_get_default_register_url(): string {
    $slug = Constants::DEFAULT_REGISTER_PAGE_SLUG;
    return home_url("/$slug");
}

/**
 * @param $training_type_id
 * Returns one of 'partou-baby', 'partou', 'default'
 * Used to include / exclude registration form fields
 */
function cv_get_register_form_type($training_type_id): RegistrationFormType {
    $type = get_post_meta($training_type_id, 'cv_form_type', true) ?: 'default';
    return RegistrationFormType::from($type);
}

function cv_get_course_format($training_type_id): CourseFormat {
    $format = get_post_meta($training_type_id, 'training_type_category', true) ?: CourseFormat::BLENDED->value;
    return CourseFormat::from($format);
}

/**
 * @param WC_Product $training_type
 *
 * Returns one of 'in_company', 'open_enrollment', 'enlist', 'default'
 */
function cv_get_registration_type(WC_Product $training_type): RegistrationType
{
    $training_type_category = get_post_meta($training_type->get_id(), 'training_type_category', true);

    // Published but hidden training types are only available for in-company registrations
    if ($training_type->get_status() == ProductStatus::PUBLISH &&
        // TODO: ASK johan about in company trainings
        (get_post_meta($training_type->get_id() , '_yoast_wpseo_meta-robots-noindex', true) === '1'
            || get_post_meta($training_type->get_id() , 'cv_hide_from_search', true) === 'yes')) {
        return RegistrationType::IN_COMPANY;
    } else if ($training_type_category === CourseFormat::E_LEARNING->value) {
        return RegistrationType::OPEN_ENROLLMENT;
    } else if ($training_type instanceof WC_Product_Variable && count($training_type->get_available_variations()) == 0) {
        // No training dates available, make people enlist
        return RegistrationType::ENLIST;
    }
    return RegistrationType::DEFAULT;
}

function cv_get_register_success_message(RegistrationFormType $form_type, CourseFormat $course_format): string {
    $success_message_type = 'default';
    if (in_array($form_type, [RegistrationFormType::PARTOU, RegistrationFormType::PARTOU_BABY])) {
        $success_message_type = 'partou';
    } else if ($course_format == CourseFormat::E_LEARNING) {
        $success_message_type = 'elearning';
    }
    return get_option('cv_register_success_message_' . $success_message_type, 'Dankjewel voor je aanmelding.');
}

function cv_get_register_success_redirect_message(): string {
    $fallback_message =  esc_html__('Dankjewel voor je aanmelding. Je wordt over enkele ogenblikken doorgestuurd naar de betaalpagina.', 'coachview');
    return get_option('cv_register_success_redirect_message', $fallback_message);
}

function get_display_date(int $timestamp): string {
    $now = time();
    if (date('Y', $timestamp) != date('Y', $now)) {
        return wp_date('j F Y', $timestamp);
    } else {
        return wp_date('j F', $timestamp);
    }
}

function get_hierarchical_categories(): array {
    $parent_cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0
    ]);

    $categories = [];
    foreach($parent_cats as $parent_cat) {
        $category = get_category_with_children($parent_cat->term_id);
        if ($category) {
            $categories[] = $category;
        }
    }
    return $categories;
}

function get_category_with_children($id): array | null {
    $term = get_term($id, 'product_cat');
    if (!$term || is_wp_error($term)) {
        return null;
    }
    $result = [
        'term_id' => $term->term_id,
        'name' => $term->name,
        'child_categories' => []
    ];

    $child_cats = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => $id
    ]);

    foreach ($child_cats as $child) {
        $result['child_categories'][] = [
            'term_id' => $child->term_id,
            'name' => $child->name
        ];
    }
    return $result;
}

function cv_assets_url(string $path = ''): string {
    return plugin_dir_url(__FILE__) . 'assets/' . ltrim($path, '/');
}

function wp_get_query_var( $key, $default = '' ) {
    if ( isset( $_GET[ $key ] ) ) {
        return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
    }
    return $default;
}

function normalize_enums(mixed $data): mixed
{
    // Enum → scalar
    if ($data instanceof \BackedEnum) {
        return $data->value;
    }

    if ($data instanceof \UnitEnum) {
        return $data->name;
    }

    // Object → associative array copy
    if (is_object($data)) {
        $out = [];
        foreach (get_object_vars($data) as $k => $v) {
            $out[$k] = normalize_enums($v);
        }
        return $out;
    }

    // Array → normalized array
    if (is_array($data)) {
        return array_map(fn($v) => normalize_enums($v), $data);
    }

    return $data;
}

function coachview_get_payment_methods(): array {
    return get_option('cv_payment_methods', []);
}

function coachview_get_default_payment_method_ids(): array {
    $saved_method_ids = get_option('cv_default_payment_method_ids', []);
    if (is_string($saved_method_ids)) {
        $saved_method_ids = json_decode($saved_method_ids, true) ?? [];
    }
    return $saved_method_ids;
}

function coachview_get_product_payment_methods(WC_Product $product): array {
    $all_methods = coachview_get_payment_methods();
    $default_method_ids = coachview_get_default_payment_method_ids();
    $product_method_ids = get_post_meta($product->get_id(), 'cv_payment_methods', true);
    if (is_array($product_method_ids)) {
        return array_filter($all_methods, fn($method) => in_array($method['id'], $product_method_ids));
    } else if (!empty($default_method_ids)) {
        return array_filter($all_methods, fn($method) => in_array($method['id'], $default_method_ids));
    }
    return $all_methods;
}

/**
 * Get all search forms
 *
 * @return array Array of search forms indexed by form ID
 */
function coachview_get_search_forms(): array
{
    $forms = get_option('coachview_search_forms', []);
    return is_array($forms) ? $forms : [];
}

/**
 * Get a specific search form by name
 *
 * @param string $form_name The form name
 * @return array|null The search form data or null if not found
 */
function coachview_get_search_form_by_name(string $form_name): ?array
{
    $forms = coachview_get_search_forms();
    foreach ($forms as $form) {
        if (isset($form['name']) && $form['name'] === $form_name) {
            return $form;
        }
    }
    return null;
}