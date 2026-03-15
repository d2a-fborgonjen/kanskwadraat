<?php
/*
Plugin Name: Coachview
Description: Koppeling met Coachview API. Laatste update 2026-03-12 20:56
Version: 1.0
Author: Frank Borgonjen
*/
require_once __DIR__ . '/vendor/autoload.php';

use Coachview\Admin\Categories\CategoryList;
use Coachview\Admin\Products\CustomACF;
use Coachview\Admin\Products\ProductList;
use Coachview\Admin\Products\ProductMeta;
use Coachview\Admin\Settings\Main;
use Coachview\Admin\Settings\SearchForms;
use Coachview\Admin\Settings\Settings;
use Coachview\Cron\Cron;
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
    new Main();
    // settings
    new Settings();
    new SearchForms();
    // Admin products
    new ProductList();
    new CategoryList();
    new ProductMeta();
    new CustomACF();

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
    Cron::activate();
    if (has_custom_register_page()) {
        return;
    }
    (new RegisterForm())->add_register_rewrite_rule();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    Cron::deactivate();
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('coachview-common', cv_assets_url('css/common.css'), array(), null);
});