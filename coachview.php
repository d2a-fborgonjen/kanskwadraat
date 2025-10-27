<?php
/*
Plugin Name: Coachview
Description: Koppeling met Coachview API
*/
require_once __DIR__ . '/vendor/autoload.php';

use Coachview\Admin\Admin;
use Coachview\Admin\ProductList;
use Coachview\Admin\ProductMeta;
use Coachview\Admin\Settings;
use Coachview\Cron\Cron;
use Coachview\Forms\FormProcessor;
use Coachview\Presentation\Pages\ProductSearchPage;
use Coachview\Presentation\Pages\RegisterPage;
use Coachview\Presentation\Hooks\TrainingHooks;
use Coachview\Presentation\Hooks\TrainingTypeHooks;
use Coachview\Presentation\Shortcodes\TrainingShortcode;
use Coachview\Presentation\Shortcodes\TrainingTypeCTAShortcode;
use Coachview\Sync\Hooks\Sync;

add_action('plugins_loaded', function () {
    // Admin pages
    new Admin();
    new Settings();
    new ProductList();
    new ProductMeta();

    // Customize wordpress / woocommerce
    new ProductSearchPage();
    new RegisterPage();
    new TrainingShortcode();
    new TrainingTypeCTAShortcode();
    new TrainingHooks();
    new TrainingTypeHooks();

    new Cron();
    new Sync();
    new FormProcessor();
});

register_activation_hook(__FILE__, function() {
    $psp = new ProductSearchPage();
    $psp->add_rewrite_rule();
    $rp = new RegisterPage();
    $rp->add_rewrite_rule();
    flush_rewrite_rules();
});