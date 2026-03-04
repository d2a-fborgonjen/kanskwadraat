<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\CourseFormat;
use Coachview\Presentation\TemplateEngine;
use WP_REST_Response;
use WP_Query;

class TrainingTypeSearch
{
    private $templateEngine;

    function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('cv_training_type_search', [$this, 'training_type_search_shortcode']);

    }

    public function training_type_search_shortcode(): string
    {
        wp_enqueue_script('coachview-search', cv_assets_url('js/training-search.js'), array('jquery'), null, true);

        $this->templateEngine = new TemplateEngine();
        $data = [
            'category_list' => $this->renderCategorySidebar(),
            'style_urls' => [cv_assets_url('css/training-search.css')]
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
        $training_cities = get_post_meta($product->get_id(), 'training_cities', true);
        $training_type_category = get_post_meta($product->get_id(), 'training_type_category', true);
        $product_url = get_permalink($product->get_id());

        $is_online = $training_type_category === CourseFormat::E_LEARNING->value;
        $location = $is_online ? 'Online' : join(", ", $training_cities ?: []);

        // Get product image URL properly
        $image_id = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : null;

        $data = [
            'image_url' => $image_url ?: wc_placeholder_img_src('full'),
            'name' => $product->get_name(),
            'description' => substr($product->get_description(), 0, 200) . (strlen($product->get_description()) > 200 ? '...' : ''),
            'training_url' => $product_url,
            'training_type_category' => $training_type_category,
            'location' => $location,
            'product_price' => $product->get_price() > 0 ? number_format_i18n($product->get_price(), 2) : null,
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
        $cats   = $request->get_param('categories') ?? [];
        $limit  = $request->get_param('limit') ?? 12;

        $relevance_filter = function($clauses) use ($search) {
            global $wpdb;
            $like = '%' . $wpdb->esc_like($search) . '%';

            $clauses['orderby'] = $wpdb->prepare(
                "CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN 2 ELSE 1 END DESC, {$wpdb->posts}.post_date DESC",
                $like
            );

            return $clauses;
        };

        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            's'              => $search,
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => 'cv_hide_from_search',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        if (!empty($cats)) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $cats,
                    'operator' => 'AND',
                ],
            ];
        }

        if (!empty($search)) {
            add_filter('posts_clauses', $relevance_filter);
        }
        $query = new WP_Query($query_args);

        if (!empty($search)) {
            remove_filter('posts_clauses', $relevance_filter);
        }

        $all_ids     = $query->posts;
        $total_count = count($all_ids);

        $products = wc_get_products([
            'include' => array_slice($all_ids, 0, $limit),
            'limit'   => $limit,
            'orderby' => 'post__in',
        ]);

        $this->templateEngine = new TemplateEngine();
        $html = '';
        if (empty($products)) {
            $html = $this->templateEngine->render('training-search-no-results');
        } else {
            foreach ($products as $product) {
                $html .= $this->render_training_type($product);
            }
        }

        return new WP_REST_Response([
            'total_count' => $total_count,
            'limit'       => $limit,
            'items'       => $html,
        ], 200);
    }
}