<?php
/*
Plugin Name: Coachview
Description: Koppeling met Coachview API
Version: 1.26.3
Author: Frank Borgonjen
*/
require_once __DIR__ . '/vendor/autoload.php';

use Coachview\Admin\Admin;
use Coachview\Admin\CategoryList;
use Coachview\Admin\CustomACF;
use Coachview\Admin\ProductList;
use Coachview\Admin\ProductMeta;
use Coachview\Admin\SearchForms;
use Coachview\Admin\Settings;
use Coachview\Cron\Cron;
use Coachview\Presentation\Components\RegisterForm;
use Coachview\Presentation\Components\RegisterFormHandler;
use Coachview\Presentation\Components\TrainingAgenda;
use Coachview\Presentation\Components\TrainingSimpleSearch;
use Coachview\Presentation\Components\TrainingTypeCTA;
use Coachview\Presentation\Components\TrainingTypeSearch;
use Coachview\Presentation\Components\TrainingTypeStartDates;
use Coachview\Sync\Hooks\Sync;

add_action('plugins_loaded', function () {
    // Admin pages
    new Admin();
    new Settings();
    new ProductList();
    new CategoryList();
    new ProductMeta();
    new SearchForms();
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

// enqueue styles and scripts
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('coachview-common', cv_assets_url('css/common.css'), array(), null);
});