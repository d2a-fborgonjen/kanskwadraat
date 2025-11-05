<?php
namespace Coachview\Presentation\Pages;

use Coachview\Presentation\TemplateEngine;
use WP_REST_Response;

class TrainingTypeSearchPage
{
    private $templateEngine;

    function __construct()
    {
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('init', [$this, 'add_rewrite_rule']);
        add_action('template_redirect', [$this, 'template_redirect']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('cv_training_type_search', [$this, 'training_type_search_shortcode']);
    }

    public function register_rest_routes() {
        register_rest_route('coachview/v1', '/products/filter', [
            'methods' => 'POST',
            'callback' => [$this, 'coachview_filter_products'],
            'permission_callback' => '__return_true',
            'args' => [
                'search' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'categories' => [
                    'required' => false,
                    'type' => 'array',
                    'items' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ]);
    }

    private function render_search_page($include_header_and_footer = true): string {
        wp_enqueue_style('coachview-common', cv_assets_url('css/common.css'));
        wp_enqueue_style('coachview-search', cv_assets_url('css/training-type-search.css'));
        wp_enqueue_style('coachview-search-items', cv_assets_url('css/training-type-search-item.css'));
        wp_enqueue_script('coachview-search', cv_assets_url('js/training-type-search.js'), array('jquery'), null, true);

        $this->templateEngine = new TemplateEngine();
        $data = [
            'category_list' => $this->renderCategorySidebar(),
            'header' => $include_header_and_footer ? $this->capture_header() : '',
            'footer' => $include_header_and_footer ? $this->capture_footer() : ''
        ];
        return $this->templateEngine->render('base-search-layout', $data);
    }

    private function renderCategorySidebar()
    {
        $parent_cats = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0
        ]);
        
        $categories = [];
        
        foreach ($parent_cats as $parent) {
            $category = [
                'name' => $parent->name,
                'child_categories' => []
            ];
            
            $child_cats = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => $parent->term_id
            ]);
            
            foreach ($child_cats as $child) {
                $category['child_categories'][] = [
                    'term_id' => $child->term_id,
                    'name' => $child->name
                ];
            }
            if (!empty($category['child_categories'])) {
                $categories[] = $category;
            }
        }
        
        return $this->templateEngine->render('category-sidebar', ['parent_categories' => $categories]);
    }

    private function render_training_type($product)
    {
        $num_locations = get_post_meta($product->get_id(), 'num_locations', true);
        $startDate = get_post_meta($product->get_id(), 'start_date', true);
        $duration = get_post_meta($product->get_id(), 'training_duration', true);
        $cities = get_post_meta($product->get_id(), 'cities', true);
        $training_type_category = get_post_meta($product->get_id(), 'training_type_category', true);
        $product_url = get_permalink($product->get_id());
        
        // Get product image URL properly
        $image_id = $product->get_image_id();
        $image_url = $image_id ?  wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';

        $data = [
            'image_url' => $image_url ?: wc_placeholder_img_src('woocommerce_thumbnail'),
            'name' => $product->get_name(),
            'description' => substr($product->get_description(), 0, 100) . (strlen($product->get_description()) > 100 ? '...' : ''),
            'training_url' => $product_url,
            'training_type_category' => $training_type_category,
            'cities' => $cities,
            'product_price' => $product->get_price() > 0 ? $product->get_price() : '',
            'num_locations' => $num_locations > 0 ? $num_locations : null,
            'duration' => $duration ?: null,
            'start_date_day' => $startDate ? date_i18n('l', $startDate) : null,
            'start_date_formatted' => $startDate ? date_i18n('j F', $startDate) : null,
            'assets_url' => cv_assets_url()
        ];
        return $this->templateEngine->render('training-type-search-item', $data);
    }


    public function coachview_filter_products($request): WP_REST_Response {
        $search = $request->get_param('search') ?? '';
        $cats = $request->get_param('categories') ?? [];

        $args = [
            'limit' => 12,
            's'     => $search,
        ];

        if (!empty($cats)) {
            $args['category'] = array_map(function($id) {
                $term = get_term($id, 'product_cat');
                return $term ? $term->slug : '';
            }, $cats);
        }

        $products = wc_get_products($args);
        $html = '';

        $this->templateEngine = new TemplateEngine();
        foreach ($products as $product) {
            $html .= $this->render_training_type($product);
        }

        return new WP_REST_Response($html, 200);
    }

    public function training_type_search_shortcode(): string {
        return $this->render_search_page(false);
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^zoek\-opleidingen/?$', 'index.php?training_type_search=1', 'top');
    }

    public function template_redirect() {
        if (get_query_var('training_type_search')) {
            echo $this->render_search_page(true);
            exit;
        }
    }

    public function add_query_vars($vars) {
        $vars[] = 'training_type_search';
        return $vars;
    }

    private function capture_header()
    {
        ob_start();
        get_header();
        return ob_get_clean();
    }

    private function capture_footer()
    {
        ob_start();
        get_footer();
        return ob_get_clean();
    }
}