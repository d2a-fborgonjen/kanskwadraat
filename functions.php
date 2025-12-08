<?php

use Automattic\WooCommerce\Enums\ProductStatus;
use Coachview\Api\TokenManager;
use Coachview\Constants;
use Coachview\Models\CourseFormat;
use Coachview\Models\RegistrationType;

function coachview_test_mode_enabled(): bool {
    return get_option('coachview_api_mode', 'test') === 'test';
}

function coachview_api_url(): string {
    return coachview_test_mode_enabled() ?
        'https://training.coachview.net' :
        'https://secure.coachview.net';
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


function coachview_search_page_url(array $params = []): string {
    $url = coachview_get_default_search_url();
    if (has_custom_search_page()) {
        $url = get_permalink(get_option('coachview_search_page'));
    }
    return empty($params) ? $url : $url . '?' . http_build_query($params);
}

function has_custom_search_page(): bool {
    $register_page_id = get_option('coachview_search_page', 0);
    return $register_page_id && get_post_status($register_page_id) === 'publish';
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

function get_registration_type(WC_Product $training_type): RegistrationType
{
    $training_type_category = get_post_meta($training_type->get_id(), 'training_type_category', true);

    // Published but hidden training types are only available for in-company registrations
    if ($training_type->get_status() == ProductStatus::PUBLISH && !$training_type->is_visible()) {
        return RegistrationType::IN_COMPANY;
    } else if ($training_type_category === CourseFormat::E_LEARNING->value) {
        return RegistrationType::OPEN_ENROLLMENT;
    } else if ($training_type instanceof WC_Product_Variable && count($training_type->get_available_variations()) == 0) {
        // No training dates available, make people enlist
        return RegistrationType::ENLIST;
    }
    return RegistrationType::DEFAULT;
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