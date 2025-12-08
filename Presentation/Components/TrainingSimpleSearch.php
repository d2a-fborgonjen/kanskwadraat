<?php

namespace Coachview\Presentation\Components;

use Coachview\Presentation\TemplateEngine;

use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class TrainingSimpleSearch
{
    public function __construct()
    {
        add_shortcode('cv_simple_search', [$this, 'render_shortcode']);
    }

    public function render_shortcode(): string
    {
        wp_enqueue_script('coachview-register-wizard', cv_assets_url('js/simple-search.js'), ['jquery'], '1.0', true);
        $templateEngine = new TemplateEngine();

        $data = [
            '_coachview_form_token' => $this->generate_form_token(),
            'search_page_url' => coachview_search_page_url(),
            'categories' => $this->get_search_categories()
        ];
        return $templateEngine->render('simple-search', $data);
    }

    private function get_search_categories(): array
    {
        $cat_ids = [
            get_option('coachview_search_page_category_1', 0),
            get_option('coachview_search_page_category_2', 0)
        ];
        $result = [];
        foreach ($cat_ids as $cat_id) {
            if ($cat_id > 0) {
                $result[] = get_category_with_children($cat_id);
            }
        }
        return $result;
    }

    private function generate_form_token()
    {
        $token = wp_generate_password(20, false); // random 20-char string
        $key = 'coachview_form_' . $token;
        set_transient($key, true, 60 * 60); // 1 hour expiration
        return $token;
    }
}