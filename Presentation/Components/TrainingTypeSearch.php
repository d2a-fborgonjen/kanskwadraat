<?php

namespace Coachview\Presentation\Components;

use Coachview\Constants;
use Coachview\Helpers\Assets;
use Coachview\Helpers\Categories;
use Coachview\Helpers\MetaHelpers;
use Coachview\Models\Enums\CourseFormat;
use WP_Query;
use WP_REST_Response;

class TrainingTypeSearch extends ShortCodeComponent
{

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
        Assets::enqueueStyle(self::get_shortcode(), 'css/training-search.css');
    }

    public function enqueue_scripts(): void
    {
        Assets::enqueueScript(self::get_shortcode(), 'js/training-search.js', ['jquery']);
    }

    public function __construct()
    {
        parent::__construct();
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function render_shortcode($atts): string
    {
        $data = [
            'category_list' => $this->renderCategorySidebar(),
        ];
        return $this->render_template($data);
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
        $categories = Categories::getHierarchicalCategories();
        return $this->render_template(['categories' => $categories], 'categories');
    }

    private function render_training_type($product): string
    {
        $productId = $product->get_id();
        $num_locations          = MetaHelpers::get_int($productId, Constants::META_NUM_LOCATIONS);
        $startDateRaw           = MetaHelpers::get_string($productId, Constants::META_START_DATE);
        $startDate              = $startDateRaw !== '' ? strtotime($startDateRaw) : 0;
        $duration               = MetaHelpers::get_string($productId, Constants::META_TRAINING_DURATION);
        $training_cities        = MetaHelpers::get_array($productId, Constants::META_TRAINING_CITIES);
        $training_type_category = MetaHelpers::get_string($productId, Constants::META_TRAINING_TYPE_CATEGORY);
        $product_url = get_permalink($productId);

        $is_online = $training_type_category === CourseFormat::E_LEARNING->value;
        $location = $is_online ? 'Online' : implode(', ', $training_cities);

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

        return $this->render_template($data, 'item');
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

        $html = '';

        if (empty($products)) {
            $html = $this->render_template([], 'no_results');
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
                    'key'     => Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH,
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
        $words = array_filter($words, static fn(string $w): bool => mb_strlen($w) >= Constants::SEARCH_MIN_WORD_LENGTH);

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
     * Scores each word match across title (weight 10), tags (weight 5),
     * and excerpt (weight 2).
     *
     * Uses EXISTS subqueries for tag matching instead of JOINs + GROUP_CONCAT,
     * which avoids duplicate rows and unreliable aggregate evaluation in ORDER BY.
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

                $wTitle   = Constants::SEARCH_WEIGHT_TITLE;
                $wTag     = Constants::SEARCH_WEIGHT_TAG;
                $wExcerpt = Constants::SEARCH_WEIGHT_EXCERPT;

                $score_parts[] = $wpdb->prepare(
                    "CASE WHEN {$wpdb->posts}.post_title LIKE %s THEN {$wTitle} ELSE 0 END",
                    $like
                );

                $score_parts[] = $wpdb->prepare(
                    "CASE WHEN {$wpdb->posts}.post_excerpt LIKE %s THEN {$wExcerpt} ELSE 0 END",
                    $like
                );

                // EXISTS subquery — no JOINs needed on the outer query
                $tag_exists = $wpdb->prepare(
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

                $score_parts[] = "CASE WHEN EXISTS ({$tag_exists}) THEN {$wTag} ELSE 0 END";
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

