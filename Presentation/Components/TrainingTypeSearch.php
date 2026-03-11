<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\CourseFormat;
use Coachview\Presentation\TemplateEngine;
use WP_REST_Response;
use WP_Query;

class TrainingTypeSearch extends ShortCodeComponent
{
    private const META_KEY_HIDE = 'cv_hide_from_search';
    private const WEIGHT_TAG     = 10;
    private const WEIGHT_TITLE   = 5;
    private const WEIGHT_EXCERPT = 2;
    private const MIN_WORD_LENGTH = 3;

    private ?TemplateEngine $templateEngine = null;

    /** @var callable|null */
    private $relevanceFilter = null;

    /** @var callable|null */
    private $searchWhereFilter = null;

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
            'category_list' => $this->renderCategorySidebar(),
        ];
        return $this->templateEngine->render('training-search', $data);
    }

    public function register_rest_routes(): void
    {
        register_rest_route('coachview/v1', '/products/filter', [
            'methods'             => 'POST',
            'callback'            => [$this, 'coachview_filter_products'],
            'permission_callback' => '__return_true',
            'args'                => [
                'search'     => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'categories' => [
                    'required' => false,
                    'type'     => 'array',
                    'items'    => ['type' => 'integer'],
                ],
                'limit'      => [
                    'required' => false,
                    'type'     => 'integer',
                    'default'  => 12,
                ],
            ],
        ]);
    }

    private function renderCategorySidebar(): string
    {
        $categories = get_hierarchical_categories();
        return $this->templateEngine->render('training-search-categories', ['categories' => $categories]);
    }

    private function render_training_type($product): string
    {
        $productId = $product->get_id();
        $num_locations = get_post_meta($productId, 'num_locations', true);
        $startDate = get_post_meta($productId, 'start_date', true);
        $duration = get_post_meta($productId, 'training_duration', true);
        $training_cities = get_post_meta($productId, 'training_cities', true);
        $training_type_category = get_post_meta($productId, 'training_type_category', true);
        $product_url = get_permalink($productId);

        $is_online = $training_type_category === CourseFormat::E_LEARNING->value;
        $location = $is_online ? 'Online' : implode(', ', $training_cities ?: []);

        $image_id = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : null;

        $description = $product->get_description();

        $data = [
            'image_url'              => $image_url ?: wc_placeholder_img_src('full'),
            'name'                   => $product->get_name(),
            'description'            => mb_substr($description, 0, 200) . (mb_strlen($description) > 200 ? '...' : ''),
            'training_url'           => $product_url,
            'training_type_category' => $training_type_category,
            'location'               => $location,
            'product_price'          => $product->get_price() > 0 ? number_format_i18n($product->get_price(), 2) : null,
            'num_locations'          => $num_locations > 0 ? $num_locations : null,
            'duration'               => $duration ?: null,
            'start_date_day'         => $startDate ? date_i18n('l', $startDate) : null,
            'start_date_formatted'   => $startDate ? date_i18n('j F', $startDate) : null,
            'assets_url'             => cv_assets_url(),
        ];

        return $this->templateEngine->render('training-search-item', $data);
    }

    public function coachview_filter_products($request): WP_REST_Response
    {
        $search = $request->get_param('search') ?? '';
        $cats   = array_filter(array_map('intval', (array) ($request->get_param('categories') ?? [])));
        $limit  = (int) ($request->get_param('limit') ?? 12);

        $search_trimmed = trim((string) $search);
        $words = $this->tokenize($search_trimmed);

        try {
            $this->attachSearchFilters($words);

            $query_args = $this->buildQueryArgs($words, $cats);
            $query = new WP_Query($query_args);
        } finally {
            $this->detachSearchFilters();
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

    // ──────────────────────────────────────────────
    //  Query building
    // ──────────────────────────────────────────────

    /**
     * @param string[] $words
     * @param int[]    $cats
     */
    private function buildQueryArgs(array $words, array $cats): array
    {
        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => self::META_KEY_HIDE,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];

        // Setting 's' to a non-empty value makes WP_Query treat this as a
        // search query, which enables the posts_search filter. The actual
        // WHERE clause is replaced by our custom filter.
        if (!empty($words)) {
            $query_args['s'] = implode(' ', $words);
        }

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

        return $query_args;
    }

    // ──────────────────────────────────────────────
    //  Search tokenisation
    // ──────────────────────────────────────────────

    /**
     * @return string[]
     */
    private function tokenize(string $search): array
    {
        if ($search === '') {
            return [];
        }

        $words = preg_split('/\s+/', $search) ?: [];
        $words = array_map('trim', $words);
        $words = array_filter($words, static fn(string $w): bool => mb_strlen($w) >= self::MIN_WORD_LENGTH);

        return array_values($words);
    }

    // ──────────────────────────────────────────────
    //  Filter attachment / detachment
    // ──────────────────────────────────────────────

    /**
     * @param string[] $words
     */
    private function attachSearchFilters(array $words): void
    {
        if (empty($words)) {
            return;
        }

        $this->relevanceFilter   = $this->buildRelevanceFilter($words);
        $this->searchWhereFilter = $this->buildSearchWhereFilter($words);

        add_filter('posts_clauses', $this->relevanceFilter);
        add_filter('posts_search', $this->searchWhereFilter, 10, 2);
    }

    private function detachSearchFilters(): void
    {
        if ($this->relevanceFilter !== null) {
            remove_filter('posts_clauses', $this->relevanceFilter);
            $this->relevanceFilter = null;
        }

        if ($this->searchWhereFilter !== null) {
            remove_filter('posts_search', $this->searchWhereFilter, 10);
            $this->searchWhereFilter = null;
        }
    }

    // ──────────────────────────────────────────────
    //  Relevance scoring (ORDER BY)
    // ──────────────────────────────────────────────

    /**
     * Joins the product_tag taxonomy tables and scores each word match
     * across title (weight 10), tags (weight 5), and excerpt (weight 2).
     *
     * Uses GROUP BY + GROUP_CONCAT to aggregate all tag names per product
     * into a single matchable string, avoiding duplicate rows.
     *
     * @param string[] $words
     */
    private function buildRelevanceFilter(array $words): callable
    {
        return function (array $clauses) use ($words): array {
            global $wpdb;

            // JOIN product_tag terms via the taxonomy relationship tables
            $clauses['join'] .=
                " LEFT JOIN {$wpdb->term_relationships} AS cv_tr
                    ON ({$wpdb->posts}.ID = cv_tr.object_id)
                  LEFT JOIN {$wpdb->term_taxonomy} AS cv_tt
                    ON (cv_tr.term_taxonomy_id = cv_tt.term_taxonomy_id
                        AND cv_tt.taxonomy = 'product_tag')
                  LEFT JOIN {$wpdb->terms} AS cv_t
                    ON (cv_tt.term_id = cv_t.term_id)";

            $clauses['groupby'] = "{$wpdb->posts}.ID";

            $score_parts = [];

            foreach ($words as $word) {
                $like = '%' . $wpdb->esc_like($word) . '%';

                $wTitle   = self::WEIGHT_TITLE;
                $wTag     = self::WEIGHT_TAG;
                $wExcerpt = self::WEIGHT_EXCERPT;

                $score_parts[] = $wpdb->prepare(
                    "CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN {$wTitle} ELSE 0 END",
                    $like
                );

                $score_parts[] = $wpdb->prepare(
                    "CASE WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN {$wExcerpt} ELSE 0 END",
                    $like
                );

                $score_parts[] = $wpdb->prepare(
                    "CASE WHEN GROUP_CONCAT(cv_t.name SEPARATOR ' ') LIKE %s THEN {$wTag} ELSE 0 END",
                    $like
                );
            }

            $score_sql = implode(' + ', $score_parts);
            $clauses['orderby'] = "({$score_sql}) DESC, {$wpdb->posts}.post_date DESC";

            return $clauses;
        };
    }

    // ──────────────────────────────────────────────
    //  Search WHERE clause (replaces WP default)
    // ──────────────────────────────────────────────

    /**
     * Each word must appear in at least one of: title, excerpt, content,
     * or any of the product's tag names.
     *
     * Uses an EXISTS subquery for tags to avoid issues with the GROUP BY
     * in the outer query.
     *
     * @param string[] $words
     */
    private function buildSearchWhereFilter(array $words): callable
    {
        return function (string $search_sql, WP_Query $query) use ($words): string {
            if (!$query->is_search()) {
                return $search_sql;
            }

            global $wpdb;

            $conditions = [];

            foreach ($words as $word) {
                $like = '%' . $wpdb->esc_like($word) . '%';

                $tag_subquery = $wpdb->prepare(
                    "SELECT 1
                     FROM {$wpdb->term_relationships} tr
                     INNER JOIN {$wpdb->term_taxonomy} tt
                        ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        AND tt.taxonomy = 'product_tag'
                     INNER JOIN {$wpdb->terms} t
                        ON tt.term_id = t.term_id
                     WHERE tr.object_id = {$wpdb->posts}.ID
                       AND t.name LIKE %s
                     LIMIT 1",
                    $like
                );

                $conditions[] = $wpdb->prepare(
                    "({$wpdb->posts}.post_title LIKE %s
                      OR {$wpdb->posts}.post_excerpt LIKE %s
                      OR {$wpdb->posts}.post_content LIKE %s
                      OR EXISTS ({$tag_subquery}))",
                    $like,
                    $like,
                    $like
                );
            }

            return ' AND (' . implode(' AND ', $conditions) . ')';
        };
    }
}