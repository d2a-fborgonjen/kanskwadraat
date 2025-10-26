<?php
namespace Coachview\Api;

use function Coachview\Sync\log_cv_exception;

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
        $token = get_transient('coachview_api_token');
        if (!$token || $refresh) {
            $token = $this->authenticate();
            if ($token) {
                set_transient('coachview_api_token', $token, 1 * HOUR_IN_SECONDS);
            }
        }
        return $token;
    }

    private function authenticate(): ?string {
        $url = coachview_api_url() . '/auth/connect/token';
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => coachview_api_client_id(),
            'client_secret' => coachview_api_secret()
        ];
        $response = wp_remote_post($url, ['body' => $body]);

//        error_log('Coachview token request: ' . print_r($response, true));

        if (is_wp_error($response)) {
            log_cv_exception('Request[token]', new Exception($response->get_error_message()));
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && isset($data['access_token'])) {
            return $data['access_token'];
        }

        error_log('Coachview token error: ' . print_r($data, true));
        return null;
    }
}
