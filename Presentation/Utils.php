<?php

namespace Coachview\Presentation;

/**
 * Create a link button with the given text and URL.
 *
 * @param string $text The text to display on the button.
 * @param string $url The URL the button should link to.
 * @param string $size The size the button: xs, sm, md, lg, xl.
 * @return string The HTML for the link button.
 */
function create_link_button(string $text, string $url, string $size = 'md', $extra_class = ''): string
{
    return '<div class="elementor-button-wrapper">
            <a class="elementor-button elementor-button-link elementor-size-'.$size .' '. $extra_class.'" href="' . esc_url($url) . '">
                <span class="elementor-button-content-wrapper">
                   <span class="elementor-button-text">' . $text . '</span>
                </span>
            </a>
        </div>';
}
