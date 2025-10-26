<?php
namespace Coachview\Presentation\Templates;

class ProductSearchPage
{

    function __construct()
    {
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);


        add_action('wp_ajax_filter_products', [$this, 'coachview_filter_products']);
        add_action('wp_ajax_nopriv_filter_products', [$this, 'coachview_filter_products']);

        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_style('coachview-search', plugin_dir_url(__FILE__) . '../../assets/css/coachview-product-search.css');
        });
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^zoek/?$', 'index.php?product_search_page=1', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'product_search_page';
        return $vars;
    }

    public function template_redirect() {
        if (get_query_var('product_search_page')) {
            include plugin_dir_path(__FILE__) . 'ProductSearchTemplate.php';
            exit;
        }
    }


    public function coachview_filter_products(): void {
        $search = sanitize_text_field($_POST['search'] ?? '');
        $cats   = array_map('intval', $_POST['categories'] ?? []);

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

        ob_start();
        foreach ($products as $product) {
            $duration = get_post_meta($product->get_id(), 'training_duration', true);
            $num_locations = get_post_meta($product->get_id(), 'num_locations', true);
            $startDate = get_post_meta($product->get_id(), 'start_date', true);
            $product_url = get_permalink($product->get_id());

            echo '<div class="coachview-search__product">';
            echo wp_kses_post($product->get_image('woocommerce_thumbnail', ['class' => 'coachview-search__product-img']));
            echo '<div class="coachview-search__product-info">';
            echo '<a href="' . esc_url($product_url) . '" class="coachview-search__product-link">';
            echo '<h4 class="coachview-search__product-title">' . esc_html($product->get_name()) . '</h4>';
            echo '</a>';
            echo '<div class="coachview-search__product-meta">';
            if ($product->get_price() > 0) {
                echo '<span class="coachview-search__product-price">' . wp_kses_post($product->get_price_html()) . '</span>';
            }
            if ($num_locations > 0) {
                echo '<span class="coachview-search__product-num-locations">' . esc_html($num_locations) . ' locaties</span>';
            }
            echo '<span class="coachview-search__product-duration">' . esc_html($duration) . '</span>';
            if ($startDate) {
                echo '<div class="coachview-search__product-start-date">';
                echo '<span class="coachview-search__product-start-date__day">' .date_i18n('l', $startDate). '</span>';
                echo '<span class="coachview-search__product-start-date__date">' .date_i18n('j F', $startDate). '</span>';
                echo '</div>';
            }
            echo '</div>';

            echo '</div></div>';
        }

        echo ob_get_clean();
        wp_die();
    }


}