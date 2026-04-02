<?php
namespace Coachview\Api;

use Coachview\Constants;
use Coachview\Helpers\Api;
use Coachview\Helpers\Logger;

class TokenManager {
    private static ?TokenManager $instance = null;

    private function __construct() {}

    public static function instance(): TokenManager {
        if (self::$instance === null) {
            self::$instance = new TokenManager();
        }
        return self::$instance;
    }

    public function getToken(bool $refresh = false): ?string {
        $token = get_transient(Constants::OPTION_API_TOKEN);
        if (!$token || $refresh) {
            $token = $this->authenticate();
            if ($token) {
                set_transient(Constants::OPTION_API_TOKEN, $token, 1 * HOUR_IN_SECONDS);
            }
        }
        return $token ?: 'not-authorized';
    }

    private function authenticate(): ?string {
        $url = Api::getBaseUrl() . '/auth/connect/token';

        $client_id = Api::getClientId();
        $client_secret = Api::getSecret();

        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ];
        $response = wp_remote_post($url, ['body' => $body]);

        if (is_wp_error($response)) {
            Logger::error('Request[token]: ' . $response->get_error_message(), 'api');
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && isset($data['access_token'])) {
            return $data['access_token'];
        }
        Logger::error('Request[token]: Token refresh error', 'api', [
            'response' => $data,
        ]);
        return null;
    }
}
