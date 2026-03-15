<?php
namespace Coachview\Presentation;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TemplateEngine
{
    private static ?TemplateEngine $template_engine = null;
    private $twig;
    
    public function __construct()
    {
        $baseDir = plugin_dir_path(__FILE__);
        $loader = new FilesystemLoader($baseDir . 'Templates/');
        
        $this->twig = new Environment($loader, [
            'cache' => false, // Set to a cache directory in production
            'debug' => false,  // Set to false in production
            'auto_reload' => true,
        ]);
    }

    public static function instance(): TemplateEngine
    {
        if (self::$template_engine === null) {
            self::$template_engine = new TemplateEngine();
        }
        return self::$template_engine;
    }
    
    /**
     * Render a template with data
     * 
     * @param string $template_name Template filename without .twig extension
     * @param ?array $data Data to pass to template
     * @return string Rendered HTML
     */
    public function render(string $template_name, ?array $data = []): string
    {
        try {
            return $this->twig->render($template_name . '.twig', $data);
        } catch (\Exception $e) {
            error_log('Twig Template Error: ' . $e->getMessage());
            return '<div class="error">Template rendering error: ' . esc_html($e->getMessage()) . '</div>';
        }
    }
}
