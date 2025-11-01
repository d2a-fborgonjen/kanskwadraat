<?php
namespace Coachview\Presentation;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TemplateEngine
{
    private $twig;
    
    public function __construct()
    {
        $loader = new FilesystemLoader([
            'search' =>  plugin_dir_path(__FILE__) . '../assets/templates/search/',
            'register' =>  plugin_dir_path(__FILE__) . '../assets/templates/register/',
            'training' =>  plugin_dir_path(__FILE__) . '../assets/templates/training/',
        ]);
        
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
}
