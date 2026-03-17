<?php

namespace Coachview\Helpers;

use Coachview\Constants;

class Url
{
    public static function getDefaultSearchUrl(): string
    {
        $slug = Constants::DEFAULT_SEARCH_PAGE_SLUG;
        return home_url("/$slug");
    }

    public static function getDefaultRegisterUrl(): string
    {
        $slug = Constants::DEFAULT_REGISTER_PAGE_SLUG;
        return home_url("/$slug");
    }

    public static function hasCustomRegisterPage(): bool
    {
        $register_page_id = (int) get_option(Constants::OPTION_REGISTER_PAGE_ID, 0);
        return $register_page_id && get_post_status($register_page_id) === 'publish';
    }

    public static function getRegisterPageUrl(array $params = []): string
    {
        $url = self::getDefaultRegisterUrl();
        if (self::hasCustomRegisterPage()) {
            $url = get_permalink((int) get_option(Constants::OPTION_REGISTER_PAGE_ID));
        }
        return empty($params) ? $url : $url . '?' . http_build_query($params);
    }
}
