<?php
namespace Coachview;

class Constants {

    // API configuration
    public const OPTION_API_MODE = 'coachview_api_mode';
    public const API_MODE_TEST = 'test';
    public const API_MODE_PRODUCTION = 'production';
    public const OPTION_API_TOKEN = 'coachview_api_token';
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
    public const META_FORM_PARTICIPANT_HEADER = 'cv_form_participant_header';
    public const META_FORM_CONTACT_PERSON_HEADER = 'cv_form_contact_person_header';

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

    // Training type meta
    public const META_NUM_LOCATIONS = 'num_locations';
    public const META_START_DATE = 'start_date';
    public const META_TRAINING_DURATION = 'training_duration';
    public const META_TRAINING_CITIES = 'training_cities';
    public const META_TRAINING_GOAL = 'training_goal';

    // Training variation (opleiding) meta
    public const META_LOCATION = 'location';
    public const META_CITY = 'city';
    public const META_ADDRESS = 'address';
    public const META_ZIPCODE = 'zipcode';
    public const META_PLANNING = 'planning';
    public const META_START_DAY = 'start_day';
    public const META_END_DATE = 'end_date';
    public const META_TOTAL_STUDY_HOURS = 'total_study_hours';
    public const META_TOTAL_DAYS = 'total_days';
    public const META_NUM_SEATS_TAKEN = 'num_seats_taken';
    public const META_NUM_SEATS_AVAILABLE = 'num_seats_available';
    public const META_MIN_SEATS = 'min_seats';
    public const META_MAX_SEATS = 'max_seats';

    // Core Coachview identifiers / sync meta
    public const META_COACHVIEW_ID = 'coachview_id';
    public const META_COACHVIEW_SOURCE = 'coachview_source';
    public const META_LAST_SYNC = 'cv_last_sync';

    // Assets & frontend
    public const ASSETS_BASE_DIR = 'assets';
    public const STYLE_HANDLE_COMMON = 'coachview-common';

    // Search relevance weights
    public const SEARCH_WEIGHT_TAG = 10;
    public const SEARCH_WEIGHT_TITLE = 5;
    public const SEARCH_WEIGHT_EXCERPT = 2;
    public const SEARCH_MIN_WORD_LENGTH = 3;

    // Settings / option groups
    public const OPTION_GROUP_SYNC_SETTINGS = 'coachview_sync_settings';

    // Misc
    public const TEXT_DOMAIN = 'coachview';

    // Sync options & logging
    public const OPTION_SYNC_ERROR_LOG    = 'coachview_sync_error';
    public const OPTION_SYNC_INFO_LOG     = 'coachview_sync_info';
    public const OPTION_SYNC_PROGRESS     = 'coachview_sync_progress';
    public const OPTION_SYNC_RUNNING      = 'coachview_sync_running';
    public const OPTION_SYNC_STARTED      = 'coachview_sync_started';
    public const OPTION_SYNC_FINISHED     = 'coachview_sync_finished';
}