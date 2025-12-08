<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\CourseFormat;
use Coachview\Presentation\TemplateEngine;
use WP_REST_Response;

class TrainingTypeSearch
{
    private $templateEngine;

    function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('cv_training_type_search', [$this, 'training_type_search_shortcode']);

        // Always enqueue styles since they are often ignored when rendering the shortcode
//        wp_enqueue_style('coachview-common', cv_assets_url('css/common.css'));
//        wp_enqueue_style('coachview-search', cv_assets_url('css/training-search.css'));
    }

    public function training_type_search_shortcode(): string
    {
        wp_enqueue_script('coachview-search', cv_assets_url('js/training-search.js'), array('jquery'), null, true);

        $this->templateEngine = new TemplateEngine();
        $data = [
            'category_list' => $this->renderCategorySidebar(),
            'style_urls' => [
                'common' => cv_assets_url('css/common.css'),
                'search' => cv_assets_url('css/training-search.css'),
            ]
        ];
        return $this->templateEngine->render('training-search', $data);
    }

    public function register_rest_routes()
    {
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

    private function renderCategorySidebar()
    {
        $categories = get_hierarchical_categories();
        return $this->templateEngine->render('training-search-categories', ['categories' => $categories]);
    }


    private function render_training_type($product)
    {
        $num_locations = get_post_meta($product->get_id(), 'num_locations', true);
        $startDate = get_post_meta($product->get_id(), 'start_date', true);
        $duration = get_post_meta($product->get_id(), 'training_duration', true);
        $cities = get_post_meta($product->get_id(), 'cities', true);
        $training_type_category = get_post_meta($product->get_id(), 'training_type_category', true);
        $product_url = get_permalink($product->get_id());

        $is_online = $training_type_category === CourseFormat::E_LEARNING->value;
        $location = $is_online ? 'Online' : join(", ", $cities);


        // Get product image URL properly
        $image_id = $product->get_image_id();
        $image_url = $image_id
            ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail')
            : cv_assets_url('img/example_training4.png');

        $data = [
            'image_url' => $image_url ?: wc_placeholder_img_src('woocommerce_thumbnail'),
            'name' => $product->get_name(),
            'description' => substr($product->get_description(), 0, 200) . (strlen($product->get_description()) > 200 ? '...' : ''),
            'training_url' => $product_url,
            'training_type_category' => $training_type_category,
            'location' => $location,
            'product_price' => $product->get_price() > 0 ? $product->get_price() : '',
            'num_locations' => $num_locations > 0 ? $num_locations : null,
            'duration' => $duration ?: null,
            'start_date_day' => $startDate ? date_i18n('l', $startDate) : null,
            'start_date_formatted' => $startDate ? date_i18n('j F', $startDate) : null,
            'assets_url' => cv_assets_url()
        ];
        return $this->templateEngine->render('training-search-item', $data);
    }

    public function coachview_filter_products($request): WP_REST_Response
    {
        $search = $request->get_param('search') ?? '';
        $cats = $request->get_param('categories') ?? [];

        $args = [
            'limit' => 12,
            'status' => 'publish',
            's' => $search,
        ];

        if (!empty($cats)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $cats,
                    'operator' => 'AND',
                ],
            ];
        }

        $products = wc_get_products($args);
        $this->templateEngine = new TemplateEngine();

        $html = '';
        if (empty($products)) {
            $html = $this->templateEngine->render('training-search-no-results');
        } else {
            foreach ($products as $product) {
                $html .= $this->render_training_type($product);
            }
        }

        return new WP_REST_Response($html, 200);
    }
}