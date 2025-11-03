<?php
/*
Plugin Name: Coachview
Description: Koppeling met Coachview API
Version: 1.0.0
Author: Frank Borgonjen
*/
require_once __DIR__ . '/vendor/autoload.php';

use Coachview\Admin\Admin;
use Coachview\Admin\ProductList;
use Coachview\Admin\ProductMeta;
use Coachview\Admin\Settings;
use Coachview\Cron\Cron;
use Coachview\Presentation\Components\TrainingTypeCTA;
use Coachview\Presentation\Components\TrainingTypeStartDates;
use Coachview\Presentation\Hooks\RegisterHooks;
use Coachview\Presentation\Hooks\TrainingHooks;
use Coachview\Presentation\Hooks\TrainingTypeHooks;
use Coachview\Presentation\Pages\RegisterPage;
use Coachview\Presentation\Pages\TrainingTypeSearchPage;
use Coachview\Sync\Hooks\Sync;

add_action('plugins_loaded', function () {
    // Admin pages
    new Admin();
    new Settings();
    new ProductList();
    new ProductMeta();

    // Presentation
    new TrainingTypeSearchPage();
    new RegisterPage();
    new RegisterHooks();
    new TrainingTypeStartDates();
    new TrainingTypeCTA();
    new TrainingHooks();
    new TrainingTypeHooks();

    new Cron();
    new Sync();
});

register_activation_hook(__FILE__, function() {
    (new TrainingTypeSearchPage())->add_rewrite_rule();
    (new RegisterPage())->add_rewrite_rule();
    flush_rewrite_rules();
});