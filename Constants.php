<?php
namespace Coachview;

class Constants {

    // API configuration
    public const OPTION_API_MODE = 'coachview_api_mode';
    public const API_MODE_TEST = 'test';
    public const API_MODE_PRODUCTION = 'production';
    public const OPTION_API_TEST_CLIENT_ID = 'coachview_test_client_id';
    public const OPTION_API_CLIENT_ID = 'coachview_client_id';
    public const OPTION_API_TEST_SECRET = 'coachview_test_secret';
    public const OPTION_API_SECRET = 'coachview_secret';
    public const API_URL_TEST = 'https://training.coachview.net';
    public const API_URL_PRODUCTION = 'https://kanskwadraat.coachview.com';
    public const TRAINING_IMPORT_LIMIT = 'coachview_training_import_limit';

    // Registration & forms
    public const META_TRAINING_TYPE_FORM_TYPE = 'cv_form_type';
    public const META_TRAINING_TYPE_CATEGORY = 'training_type_category';
    public const META_TRAINING_TYPE_HIDE_FROM_SEARCH = 'cv_hide_from_search';

    public const OPTION_REGISTER_SUCCESS_MESSAGE_PREFIX = 'cv_register_success_message_';
    public const OPTION_REGISTER_SUCCESS_MESSAGE_DEFAULT = 'cv_register_success_message_default';
    public const OPTION_REGISTER_SUCCESS_MESSAGE_PARTOU = 'cv_register_success_message_partou';
    public const OPTION_REGISTER_SUCCESS_MESSAGE_ELEARNING = 'cv_register_success_message_elearning';
    public const OPTION_REGISTER_SUCCESS_REDIRECT_MESSAGE = 'cv_register_success_message_redirect';
    public const OPTION_SEARCH_FORMS = 'coachview_search_forms';

    // Pages & routing
    public const DEFAULT_REGISTER_PAGE_SLUG = 'aanmelden';
    public const DEFAULT_SEARCH_PAGE_SLUG = 'zoek-opleidingen';
    public const OPTION_REGISTER_PAGE_ID = 'coachview_register_page';

    // Payment methods
    public const OPTION_PAYMENT_METHODS = 'cv_payment_methods';
    public const OPTION_DEFAULT_PAYMENT_METHOD_IDS = 'cv_default_payment_method_ids';
    public const META_PRODUCT_PAYMENT_METHODS = 'cv_payment_methods';

    // Assets & frontend
    public const ASSETS_BASE_DIR = 'assets';
    public const STYLE_HANDLE_COMMON = 'coachview-common';

    // Search relevance weights
    public const SEARCH_WEIGHT_TAG = 10;
    public const SEARCH_WEIGHT_TITLE = 5;
    public const SEARCH_WEIGHT_EXCERPT = 2;
    public const SEARCH_MIN_WORD_LENGTH = 3;

    // Misc
    public const TEXT_DOMAIN = 'coachview';
}