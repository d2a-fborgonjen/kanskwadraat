<?php

namespace Coachview\Presentation\Components;

use Coachview\Presentation\TemplateEngine;
use Coachview\Helpers\Logger;

abstract class ShortCodeComponent
{
    public function __construct()
    {
        add_shortcode(static::get_shortcode(), [$this, 'do_render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function do_render_shortcode($atts): string
    {
        try {
            $this->enqueue_scripts();
            return $this->render_shortcode($atts);
        } catch (Exception $e) {
            Logger::error('Render['.self::get_shortcode().']: ' . $e->getMessage(), 'sync', [
                'exception' => get_class($e),
                'trace'     => $e->getTraceAsString(),
            ]);
            return 'Er is een fout opgetreden bij het tonen van ' . self::get_shortcode();
        }
    }

    public function render_template($data, ?string $sub_template = null): string
    {
        $shortcode = $this->get_shortcode();
        $template_name = $sub_template ? "{$shortcode}_{$sub_template}" : $shortcode;
        return TemplateEngine::instance()->render($template_name, $data);
    }

    public static abstract function get_shortcode(): string;
    public abstract function render_shortcode($atts): string;
    public abstract function enqueue_scripts(): void;
    public abstract function enqueue_styles(): void;
}