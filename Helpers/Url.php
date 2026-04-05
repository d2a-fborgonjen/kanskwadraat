<?php

namespace Coachview\Helpers;

use Coachview\Constants;

class Url
{
    public static function get_default_search_url(): string
    {
        $slug = Constants::DEFAULT_SEARCH_PAGE_SLUG;
        return home_url("/$slug");
    }

    public static function get_default_register_url(): string
    {
        $slug = Constants::DEFAULT_REGISTER_PAGE_SLUG;
        return home_url("/$slug");
    }

    public static function has_custom_register_page(): bool
    {
        $register_page_id = (int) get_option(Constants::OPTION_REGISTER_PAGE_ID, 0);
        return $register_page_id && get_post_status($register_page_id) === 'publish';
    }

    public static function get_register_page_url(array $params = []): string
    {
        $url = self::get_default_register_url();
        if (self::has_custom_register_page()) {
            $url = get_permalink((int) get_option(Constants::OPTION_REGISTER_PAGE_ID));
        }
        return empty($params) ? $url : $url . '?' . http_build_query($params);
    }
}
