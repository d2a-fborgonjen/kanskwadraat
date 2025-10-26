<?php
namespace Coachview\Presentation\Templates;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TemplateEngine
{
    private $twig;
    private $templateDir;
    
    public function __construct()
    {
        $this->templateDir = plugin_dir_path(__FILE__);
        $loader = new FilesystemLoader($this->templateDir);
        $this->twig = new Environment($loader, [
            'cache' => false, // Set to a cache directory in production
            'debug' => true,  // Set to false in production
            'auto_reload' => true,
        ]);

        $this->twig->addFunction(new TwigFunction('esc_html', 'esc_html'));
        $this->twig->addFunction(new TwigFunction('esc_url', 'esc_url'));
        $this->twig->addFunction(new TwigFunction('wp_kses_post', 'wp_kses_post'));
        $this->twig->addFunction(new TwigFunction('date_i18n', 'date_i18n'));
        $this->twig->addFunction(new TwigFunction('get_permalink', 'get_permalink'));
        $this->twig->addFunction(new TwigFunction('get_post_meta', 'get_post_meta'));
        $this->twig->addFunction(new TwigFunction('admin_url', 'admin_url'));
        $this->twig->addFunction(new TwigFunction('plugin_dir_url', 'plugin_dir_url'));
    }
    
    /**
     * Render a template with data
     * 
     * @param string $templateName Template filename without .twig extension
     * @param array $data Data to pass to template
     * @return string Rendered HTML
     */
    public function render($templateName, $data = [])
    {
        try {
            return $this->twig->render($templateName . '.twig', $data);
        } catch (\Exception $e) {
            error_log('Twig Template Error: ' . $e->getMessage());
            return '<div class="error">Template rendering error: ' . esc_html($e->getMessage()) . '</div>';
        }
    }

    /**
     * Capture WordPress header content
     *
     * @return string Header HTML content
     */
    public function captureHeader()
    {
        ob_start();
        get_header();
        return ob_get_clean();
    }

    /**
     * Capture WordPress footer content
     *
     * @return string Footer HTML content
     */
    public function captureFooter()
    {
        ob_start();
        get_footer();
        return ob_get_clean();
    }
    
    /**
     * Render product card template
     * 
     * @param object $product WooCommerce product object
     * @return string Rendered product card HTML
     */
    public function renderProductCard($product)
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
        
        return $this->render('product-card', $data);
    }
    
    /**
     * Render category sidebar template
     * 
     * @return string Rendered category sidebar HTML
     */
    public function renderCategorySidebar()
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
        
        return $this->render('category-sidebar', ['parent_categories' => $categories]);
    }
    
    /**
     * Get the template directory path
     * 
     * @return string Template directory path
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }
}
