<?php

namespace Coachview\Presentation\Components;

use Coachview\Helpers\Categories;
use Coachview\Helpers\Assets;
use Coachview\Helpers\SearchForms;
use Coachview\Helpers\Url;
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
        Assets::enqueueScript(self::get_shortcode(), 'js/simple-search.js', ['jquery']);
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
            $form = SearchForms::get_by_name($form_name);
            if ($form) {
                // Build search page URL from form data
                $search_page_url = '';
                if (!empty($form['coachview_search_page'])) {
                    $page_id = absint($form['coachview_search_page']);
                    $search_page_url = get_permalink($page_id);
                }
                if (empty($search_page_url)) {
                    $search_page_url = Url::get_default_search_url();
                }

                $data = [
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

    private function get_search_categories_from_form(array $form): array
    {
        $cat_ids = [
            isset($form['category_1']) ? absint($form['category_1']) : 0,
            isset($form['category_2']) ? absint($form['category_2']) : 0,
        ];
        $result = [];
        foreach ($cat_ids as $cat_id) {
            if ($cat_id > 0) {
                $category = Categories::getCategoryWithChildren($cat_id);
                if ($category) {
                    $result[] = $category;
                }
            }
        }
        return $result;
    }
}