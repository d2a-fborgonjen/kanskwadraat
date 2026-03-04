<?php

namespace Coachview\Presentation\Components;

abstract class ShortCodeComponent
{
    public function __construct()
    {
        add_shortcode(static::get_shortcode(), [$this, 'do_render_shortcode']);
        $this->enqueue_styles();
    }

    public function do_render_shortcode($atts): string
    {
        $this->enqueue_scripts();
        return $this->render_shortcode($atts);
    }

    public static abstract function get_shortcode(): string;
    public abstract function render_shortcode($atts): string;
    public abstract function enqueue_scripts(): void;
    public abstract function enqueue_styles(): void;
}