<?php

namespace Coachview\Helpers;

use Coachview\Constants;
use Coachview\Api\TokenManager;

class Api
{
    public static function isTestMode(): bool
    {
        return get_option(Constants::OPTION_API_MODE, Constants::API_MODE_TEST) === Constants::API_MODE_TEST;
    }

    public static function getBaseUrl(): string
    {
        return self::isTestMode() ? Constants::API_URL_TEST : Constants::API_URL_PRODUCTION;
    }

    public static function getClientId(): string
    {
        return self::isTestMode()
            ? (string) get_option(Constants::OPTION_API_TEST_CLIENT_ID)
            : (string) get_option(Constants::OPTION_API_CLIENT_ID);
    }

    public static function getSecret(): string
    {
        return self::isTestMode()
            ? (string) get_option(Constants::OPTION_API_TEST_SECRET)
            : (string) get_option(Constants::OPTION_API_SECRET);
    }

    public static function getToken(bool $refresh = false): string
    {
        return TokenManager::instance()->getToken($refresh);
    }
}

