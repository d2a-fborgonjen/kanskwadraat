<?php
/*
Plugin Name: Coachview
Description: Koppeling met Coachview API. Laatste update 2026-04-02
Version: 1.0
Author: Frank Borgonjen
*/
require_once __DIR__ . '/vendor/autoload.php';

use Coachview\Admin\Categories\CategoryList;
use Coachview\Admin\Products\CustomACF;
use Coachview\Admin\Products\ProductList;
use Coachview\Admin\Products\ProductMeta;
use Coachview\Admin\Settings\Admin;
use Coachview\Admin\Settings\LogViewer;
use Coachview\Admin\Settings\Settings;
use Coachview\Admin\Settings\SearchForms;
use Coachview\Helpers\Logger;
use Coachview\Constants;
use Coachview\Cron\Cron;
use Coachview\Helpers\Assets;
use Coachview\Helpers\Url;
use Coachview\Presentation\Components\RegisterCallback;
use Coachview\Presentation\Components\RegisterForm;
use Coachview\Presentation\Components\RegisterFormHandler;
use Coachview\Presentation\Components\TrainingAgenda;
use Coachview\Presentation\Components\TrainingSimpleSearch;
use Coachview\Presentation\Components\TrainingTypeCTA;
use Coachview\Presentation\Components\TrainingTypeSearch;
use Coachview\Presentation\Components\TrainingTypeStartDates;
use Coachview\Sync\Hooks\Sync;

add_action('plugins_loaded', function () {
    Logger::maybe_create_table();

    // Admin pages
    new Admin();
    new Settings();
    new ProductList();
    new CategoryList();
    new ProductMeta();
    new SearchForms();
    new CustomACF();
    new LogViewer();

    // Training Type + Training
    new TrainingSimpleSearch();
    new TrainingTypeSearch();
    new TrainingTypeStartDates();
    new TrainingTypeCTA();
    new TrainingAgenda();

    // Register form
    new RegisterForm();
    new RegisterFormHandler();
    new RegisterCallback();

    // Sync hooks
    new Sync();
    new Cron();
});

register_activation_hook(__FILE__, function() {
    Logger::create_table();
    Cron::activate();
    if (Url::has_custom_register_page()) {
        return;
    }
    (new RegisterForm())->add_register_rewrite_rule();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    Cron::deactivate();
});

add_action('wp_enqueue_scripts', function () {
    Assets::enqueueStyle(Constants::STYLE_HANDLE_COMMON, 'css/common.css');
});