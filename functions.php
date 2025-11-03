<?php

use Automattic\WooCommerce\Enums\ProductStatus;
use Coachview\Api\TokenManager;
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

function get_order_success_redirect_url(): string {
    $url = get_option('coachview_order_success_redirect_url');
    return $url ?: home_url();
}

function get_registration_type(WC_Product $training_type): RegistrationType
{
    $training_type_category = get_post_meta($training_type->get_id(), 'training_type_category', true);

    // Published but hidden training types are only available for in-company registrations
    if ($training_type->get_status() == ProductStatus::PUBLISH && !$training_type->is_visible()) {
        return RegistrationType::IN_COMPANY;
    } else if ($training_type_category === 'elearning') {
        return RegistrationType::OPEN_ENROLLMENT;
    } else if ($training_type instanceof WC_Product_Variable && count($training_type->get_available_variations()) == 0) {
        // No training dates available, make people enlist
        return RegistrationType::ENLIST;
    }
    return RegistrationType::DEFAULT;
}