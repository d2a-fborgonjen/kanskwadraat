<?php

namespace Coachview\Presentation;

/**
 * Create a link button with the given text and URL.
 *
 * @param string $text The text to display on the button.
 * @param string $url The URL the button should link to.
 * @return string The HTML for the link button.
 */
function create_link_button(string $text, string $url): string
{
    return '<div class="d-flex">
        <a class="btn btn-primary" href="' . esc_url($url) . '">' . $text . '</a>
    </div>';
}
