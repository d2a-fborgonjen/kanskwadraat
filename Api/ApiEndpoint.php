<?php
namespace Coachview\Api;

class ApiEndpoint {
    private HttpClient $http_client;
    private string $endpoint;


    public function __construct(HttpClient $http_client, string $endpoint) {
        $this->http_client = $http_client;
        $this->endpoint = $endpoint;
    }

    public function get(array $query = []) {
        return $this->http_client->request('GET', $this->endpoint, ['query' => $query]);
    }

    public function post(array $body = []) {
        return $this->http_client->request('POST', $this->endpoint, ['body' => $body]);
    }

    public function put(array $body = []) {
        return $this->http_client->request('PUT', $this->endpoint, ['body' => $body]);
    }

    public function delete(array $query = []) {
        return $this->http_client->request('DELETE', $this->endpoint, ['query' => $query]);
    }
}
