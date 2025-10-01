<?php
namespace Coachview\Api;

class HttpClient {
    private string $base_url;

    public function __construct() {
        $this->base_url = coachview_api_url() . '/api';
    }

    private function getHeaders(bool $is_json = false): array {
        $headers = [
            'Authorization' => 'Bearer ' . coachview_api_token(),
            'Accept' => 'application/json'
        ];

        if ($is_json) {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    public function request(string $method, string $endpoint, array $options = []) {
        $url = $this->base_url . $endpoint;

        if (!empty($options['query'])) {
            $url .= '?' . http_build_query($options['query']);
        }

        $args = ['method' => $method, 'headers' => $this->getHeaders($method !== 'GET')];
        if (!empty($options['body'])) {
            $args['body'] = json_encode($options['body']);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception('Request failed: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            throw new \Exception("HTTP $code error: " . print_r($body, true));
        }

        return $body;
    }
}
