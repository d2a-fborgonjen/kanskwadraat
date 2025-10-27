<?php
namespace Coachview\Presentation\Pages;

class ProductSearchPage
{
    private $templateEngine;

    function __construct()
    {
        $this->templateEngine = new TemplateEngine();
        
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);

        add_action('wp_ajax_filter_products', [$this, 'coachview_filter_products']);
        add_action('wp_ajax_nopriv_filter_products', [$this, 'coachview_filter_products']);
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
            echo $this->renderSearchPage();
            exit;
        }
    }

    private function renderSearchPage(): string {
        wp_enqueue_style('coachview-search-css', plugin_dir_url(__FILE__) . '../../assets/css/coachview-product-search.css');
        wp_enqueue_script('coachview-search-js', plugin_dir_url(__FILE__) . '../../assets/js/coachview-product-search.js', array('jquery'), null, true);

        $data = [
            'category_list' => $this->renderCategorySidebar(),
            'header' => $this->captureHeader(),
            'footer' => $this->captureFooter()
        ];
        return $this->templateEngine->render('base-search-layout', $data);
    }

    private function captureHeader()
    {
        ob_start();
        get_header();
        return ob_get_clean();
    }

    private function captureFooter()
    {
        ob_start();
        get_footer();
        return ob_get_clean();
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
            
            $categories[] = $category;
        }
        
        return $this->templateEngine->render('category-sidebar', ['parent_categories' => $categories]);
    }

    private function renderProductCard($product)
    {
        $num_locations = get_post_meta($product->get_id(), 'num_locations', true);
        $startDate = get_post_meta($product->get_id(), 'start_date', true);
        $duration = get_post_meta($product->get_id(), 'training_duration', true);
        $product_url = get_permalink($product->get_id());
        
        // Get product image URL properly
        $image_id = $product->get_image_id();
        $image_url = '';
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
        }
        
        $data = [
            'product' => $product,
            'product_image_url' => $image_url ?: wc_placeholder_img_src('woocommerce_thumbnail'),
            'product_name' => $product->get_name(),
            'product_url' => $product_url,
            'product_price' => $product->get_price() > 0 ? $product->get_price_html() : '',
            'num_locations' => $num_locations > 0 ? $num_locations : null,
            'duration' => $duration ?: null,
            'start_date_day' => $startDate ? date_i18n('l', $startDate) : null,
            'start_date_formatted' => $startDate ? date_i18n('j F', $startDate) : null
        ];
        
        return $this->templateEngine->render('product-card', $data);
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

        foreach ($products as $product) {
            echo $this->renderProductCard($product);
        }

        wp_die();
    }


}