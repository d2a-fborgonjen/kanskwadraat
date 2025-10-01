<?php
namespace Coachview\Api;

//    'locations' => '/v1/Locaties',
//
//    // Opleidingssoorten
//    'training_types' => '/v1/Opleidingssoorten',
//    'training_type_components' => '/v1/Opleidingssoortonderdelen',
//    'categories' => '/v1/Opleidingssoortcategorieen',
//    'category_groups' => '/v1/Opleidingssoortcategoriegroepen',
//    'sales_rules' => '/v1/Verkoopregels',
//
//    // Opleidingen
//    'trainings' => '/v1/Opleidingen',
//    'training_components' => '/v1/Opleidingsonderdelen',
//
//    'payment_methods' => '/v1/Betaalwijzen',
//    'custom_fields' => '/v1/Vrijvelddefinities',
//    'register' => '/v1/Webaanvragen'

class ApiClient {
    private HttpClient $http_client;
    private static ApiClient $instance;

    private function __construct() {
        $this->http_client = new HttpClient();
    }

    public static function instance(): ApiClient
    {
        if (!isset(self::$instance)) {
            self::$instance = new ApiClient();
        }
        return self::$instance;
    }

    public static function training_types(): ApiEndpoint {
        return ApiClient::instance()->create_endpoint('/v1/Opleidingssoorten');
    }

    public static function trainings(): ApiEndpoint {
        return ApiClient::instance()->create_endpoint('/v1/Opleidingen');
    }

    public static function training_type_components(): ApiEndpoint {
        return ApiClient::instance()->create_endpoint('/v1/Opleidingssoortonderdelen');
    }
    public static function training_type_categories(): ApiEndpoint {
        return ApiClient::instance()->create_endpoint('/v1/Opleidingssoortcategorieen');
    }

    public static function training_components(): ApiEndpoint {
        return ApiClient::instance()->create_endpoint('/v1/Opleidingsonderdelen');
    }

    public static function training_types_by_id(string $id): ApiEndpoint {
        return ApiClient::instance()->create_endpoint("training_types/{$id}");
    }

    public static function sales_rules(): ApiEndpoint {
        return ApiClient::instance()->create_endpoint('/v1/Verkoopregels');
    }

    private function create_endpoint(string $endpoint_key): ApiEndpoint {
        return new ApiEndpoint($this->http_client, $endpoint_key);
    }
}
