<?php

namespace Coachview\Presentation\Components;

use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;

class RegisterFormHandler
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_rest_routes(): void
    {
        register_rest_route('coachview/v1', '/register', [
            'methods'             => 'POST',
            'callback'            => [$this, 'process_rest_request'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function process_rest_request(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();
        if (empty($payload)) {
            $payload = $request->get_body_params();
        }

//        $result = coachview_test_mode_enabled()
//            ? $this->handle_submission($payload ?? [])
//            : $this->blocked_submission_response();
        $result = $this->handle_submission($payload ?? []);

        if (!$result['success']) {
            return new WP_REST_Response([
                'message' => $result['message'],
            ], $result['status']);
        }

        return new WP_REST_Response([
            'message'       => $result['message'],
            'redirect_url'  => $result['redirect_url'],
            'order'         => $result['order'],

        ], $result['status']);
    }

    private function handle_submission(array $data): array
    {
        $token = $data['_coachview_form_token'] ?? '';
        $key = 'coachview_form_' . $token;
        if (!$token || !get_transient($key)) {
            error_log('Invalid or expired form submission attempt.');

            return [
                'success' => false,
                'status'  => 400,
                'message' => esc_html__('Ongeldige formulierverzending.', 'coachview'),
                'redirect_url' => null,
                'order'   => null,
            ];
        }
        delete_transient($key);

        $order_data = $this->to_coachview_order_data($data);
//        error_log('Processing form submission for training registration.' . print_r($order_data, true));

        $order = $this->create_coachview_order($order_data);
//        error_log('Response from CoachView: ' . print_r($order, true));

        if (is_wp_error($order) || !$order) {
            $statusCode = 500;
            $message = esc_html__('Er is iets misgegaan bij het verwerken van je aanmelding. Probeer het later opnieuw.', 'coachview');

            if ($order instanceof WP_Error) {
                $statusCode = (int) $order->get_error_code();
                if ($statusCode < 100) {
                    $statusCode = 500;
                }
                $message = $order->get_error_message() ?: $message;
            }

            return [
                'success' => false,
                'status'  => $statusCode,
                'message' => $message,
                'redirect_url' => null,
                'order'   => null,
            ];
        }

        $this->update_total_participants($data);

        $redirectUrl = $order['betaalproviderRedirectUrl'];
        $message = $redirectUrl
            ? esc_html__('Dankjewel voor je aanmelding. Je wordt over enkele ogenblikken doorgestuurd naar de betaalpagina.', 'coachview')
            : esc_html__('Dankjewel voor je aanmelding.', 'coachview');

        return [
            'success'      => true,
            'status'       => 200,
            'message'      => $message,
            'redirect_url' => $redirectUrl,
            'order'        => $order,
        ];
    }

    private function blocked_submission_response(): array
    {
        return [
            'success' => true,
            'status' => 200,
            'message' => esc_html__('Aanmelding geblokkeerd. We draaien productie modus en blokkeren aanmeldingen naar Coachview.', 'coachview'),
            'redirect_url' => null,
            'order' => [],
        ];
    }

    private function to_coachview_order_data(array $post): array
    {
        $participants = $post['deelnemer'] ?? [];
        $contactPerson = isset($post['is_contactpersoon']) ? ($post['contactpersoon'] ?? '') : '';
        $company = !empty($post['bedrijf']['naam']) ? $post['bedrijf'] : '';
        $debtor = collect($post['debiteur'] ?? [])
            ->put('emailType', !empty($company) ? 'Bedrijf' : 'ContactpersoonAanvraag')
            ->toArray();
        $remark = $post['opmerking'] ?? '';

        return [
            'referentieNrKlant' => '',
            'opmerking'         => $remark,
            'vrijevelden'       => '',
            'bedrijf'           => $company,
            'aanvraagIsOrder'   => true,
            'contactpersoon'    => $contactPerson,
            'debiteur'          => $debtor,
            'deelnemers'        => !empty($participants) ? array_values($participants) : '',
            'opleidingen'       => [
                $post['opleidingen'] ?? [],
            ],
        ];
    }

    /**
     * @param array $order_data
     * @return array|WP_Error
     */
    private function create_coachview_order(array $order_data)
    {
        $response = (new WP_Http())->post(coachview_api_url() . '/api/v1/Webaanvragen', [
            'headers' => [
                'Authorization' => 'Bearer ' . coachview_api_token(),
                'Content-Type'  => 'application/json; charset=utf-8',
            ],
            'body'       => collect($order_data)->toJson(),
            'ssl_verify' => false,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode !== 201) {
            $body = wp_remote_retrieve_body($response);
            error_log('Order creation failed: ' . $statusCode . ' Response: ' . $body);

            return new WP_Error(
                $statusCode,
                esc_html__('Er is iets misgegaan bij het verwerken van je aanmelding. Probeer het later opnieuw.', 'coachview')
            );
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function update_total_participants(array $post_data): void
    {
        if (empty($post_data['training_id'])) {
            return;
        }

        $training = wc_get_product((int) $post_data['training_id']);

        if (!$training) {
            return;
        }

        $participants = $post_data['deelnemer'] ?? [];
        $participantCount = is_array($participants) ? count($participants) : 0;

        if ($participantCount <= 0) {
            return;
        }

        $currentQuantity = (int) $training->get_stock_quantity();
        $training->set_stock_quantity($currentQuantity - $participantCount);
        $training->save();
    }
}

