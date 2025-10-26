<?php
namespace Coachview\Presentation\Pages;


class RegisterPage
{

    function __construct()
    {
        add_filter('query_vars', function ($vars) {
            $vars[] = 'register';
            return $vars;
        });

        add_action('template_redirect', function() {
            if (get_query_var('register')) {
                include plugin_dir_path(__FILE__) . 'RegisterPageTemplate.php';
                exit;
            }
        });

        wp_enqueue_style('coachview-forms', WP_PLUGIN_URL . '/coachview/assets/css/coachview-forms.css');
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^aanmelden/?$', 'index.php?register=1', 'top');
    }

}