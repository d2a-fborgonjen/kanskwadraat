<?php
namespace Coachview\Presentation\Pages;

use Coachview\Presentation\Templates\TemplateEngine;

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
            $this->renderSearchPage();
            exit;
        }
    }

    private function renderSearchPage() {
        $data = [
            'css_url' => plugin_dir_url(__FILE__) . '../../assets/css/coachview-product-search.css',
            'ajax_url' => admin_url('admin-ajax.php'),
            'category_list' => $this->templateEngine->renderCategorySidebar(),
            'header' => $this->templateEngine->captureHeader(),
            'footer' => $this->templateEngine->captureFooter()
        ];
        
        echo $this->templateEngine->render('base-search-layout', $data);
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
            echo $this->templateEngine->renderProductCard($product);
        }

        wp_die();
    }


}