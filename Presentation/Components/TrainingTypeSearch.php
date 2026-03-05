<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\CourseFormat;
use Coachview\Presentation\TemplateEngine;
use WP_REST_Response;
use WP_Query;

class TrainingTypeSearch extends ShortCodeComponent
{
    private $templateEngine;

    public static function get_shortcode(): string
    {
        return 'cv_training_type_search';
    }

    public function enqueue_styles(): void
    {
        wp_enqueue_style(self::get_shortcode(), cv_assets_url('css/training-search.css'), [], null);
    }

    public function enqueue_scripts(): void
    {
        wp_enqueue_script(self::get_shortcode(), cv_assets_url('js/training-search.js'), ['jquery'], '1.0', true);
    }

    public function __construct()
    {
        parent::__construct();
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function render_shortcode($atts): string
    {
        $this->templateEngine = new TemplateEngine();
        $data = [
            'category_list' => $this->renderCategorySidebar()
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

        $search_trimmed = trim((string) $search);

        $relevance_filter = null;
        if ($search_trimmed !== '') {
            $relevance_filter = $this->build_relevance_filter_for_search($search_trimmed);
            add_filter('posts_clauses', $relevance_filter);
        }

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

        $query = new WP_Query($query_args);

        if ($relevance_filter !== null) {
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

    /**
     * Build a posts_clauses filter that scores relevance based on per-word matches
     * of the search term in post_title.
     */
    private function build_relevance_filter_for_search(string $search): callable
    {
        $words = preg_split('/\s+/', $search) ?: [];
        $words = array_values(array_filter(array_map('trim', $words)));

        return function (array $clauses) use ($words): array {
            global $wpdb;

            if (empty($words)) {
                return $clauses;
            }

            $score_parts = [];
            foreach ($words as $word) {
                $like = '%' . $wpdb->esc_like($word) . '%';
                $score_parts[] = $wpdb->prepare(
                    'CASE WHEN ' . $wpdb->posts . '.post_title LIKE %s THEN 1 ELSE 0 END',
                    $like
                );
            }

            if (empty($score_parts)) {
                return $clauses;
            }

            $score_sql = implode(' + ', $score_parts);

            $clauses['orderby'] = $score_sql . ' DESC, ' . $wpdb->posts . '.post_date DESC';

            return $clauses;
        };
    }
}

