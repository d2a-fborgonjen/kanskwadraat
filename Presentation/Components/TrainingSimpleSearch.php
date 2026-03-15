<?php

namespace Coachview\Presentation\Components;

use Coachview\Presentation\TemplateEngine;

use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class TrainingSimpleSearch extends ShortCodeComponent
{
    public static function get_shortcode(): string
    {
        return 'cv_simple_search';
    }

    public function enqueue_scripts(): void
    {
        wp_enqueue_script(self::get_shortcode(), cv_assets_url('js/simple-search.js'), ['jquery'], '1.0', true);
    }

    public function enqueue_styles(): void {}

    public function render_shortcode($atts): string
    {
        $atts = shortcode_atts([
            'name' => 'not-set',
            'orientation' => 'horizontal'
        ], $atts, self::get_shortcode());

        $form_name = sanitize_text_field($atts['name']);
        $orientation = sanitize_text_field($atts['orientation']);

        $templateEngine = new TemplateEngine();

        // If form name is provided, use form settings
        if (!empty($form_name)) {
            $form = coachview_get_search_form_by_name($form_name);
            if ($form) {
                // Build search page URL from form data
                $search_page_url = '';
                if (!empty($form['coachview_search_page'])) {
                    $page_id = absint($form['coachview_search_page']);
                    $search_page_url = get_permalink($page_id);
                }
                if (empty($search_page_url)) {
                    $search_page_url = coachview_get_default_search_url();
                }

                $data = [
                    '_coachview_form_token' => $this->generate_form_token(),
                    'name' => $form_name,
                    'orientation' => $orientation,
                    'search_page_url' => $search_page_url,
                    'categories' => $this->get_search_categories_from_form($form)
                ];
                return $templateEngine->render($this->get_shortcode(), $data);
            }
        }
        return '<div class="cv-simple-search-error">Zoekformulier met naam "'. $form_name .'" niet gevonden.</div>';
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

    private function get_search_categories_from_form(array $form): array
    {
        $cat_ids = [
            isset($form['category_1']) ? absint($form['category_1']) : 0,
            isset($form['category_2']) ? absint($form['category_2']) : 0
        ];
        $result = [];
        foreach ($cat_ids as $cat_id) {
            if ($cat_id > 0) {
                $category = get_category_with_children($cat_id);
                if ($category) {
                    $result[] = $category;
                }
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