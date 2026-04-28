<?php

namespace Coachview\Presentation\Components;

use Coachview\Helpers\Api;
use Coachview\Helpers\Logger;
use Coachview\Helpers\Registration;
use Coachview\Models\Enums\CourseFormat;
use Coachview\Models\Enums\RegistrationFormType;
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

//        $result = $this->blocked_submission_response();
//        $result = Api::isTestMode()
//            ? $this->handle_submission($payload ?? [])
//            : $this->blocked_submission_response();

        $result = $this->handle_submission($payload ?? []);

        if (!$result['success']) {
            return new WP_REST_Response($result, $result['status']);
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
        $form_token = 'coachview_form_' . $token;
        if (!$token || !get_transient($form_token)) {
            Logger::warn('Invalid or expired form submission attempt.', 'order', ['token' => $token]);

            return [
                'success' => false,
                'status'  => 400,
                'message' => esc_html__('Formulierverzending is ongeldig omdat de maximale tijd is verstreken.', 'coachview'),
                'redirect_url' => null,
                'order'   => null,
            ];
        }

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
                'error_details' => json_decode($order->get_error_data('error_details'))
            ];
        }

        delete_transient($form_token);
        $this->update_total_participants($data);

        $form_type = RegistrationFormType::from($data['_coachview_form_type'] ?: RegistrationFormType::DEFAULT->value);
        $course_format = CourseFormat::from($data['_coachview_course_format'] ?: CourseFormat::BLENDED->value);
        set_transient('register_success_form_type_' . $order->nummer, $form_type->value, 1 * HOUR_IN_SECONDS);
        set_transient('register_success_course_format_' . $order->nummer, $course_format->value, 1 * HOUR_IN_SECONDS);

        $redirectUrl = $order['betaalproviderRedirectUrl'];

        $message = $redirectUrl
            ? Registration::get_redirect_success_message()
            : Registration::get_success_message($form_type, $course_format);

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
        $company = $this->get_company_data($post);
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

    private function get_company_data(array $post): array | string
    {
        if (empty($post['bedrijf']['naam'])) {
            return '';
        }
        return [
            'naam' => $post['bedrijf']['naam'],
            'factuurAdres' => $post['deelnemer'][0]['factuurAdres'] ?? '',
            'emailadres' => $post['debiteur']['emailadresAnders'] ?? ''
        ];
    }

    /**
     * @param array $order_data
     * @return array|WP_Error
     */
    private function create_coachview_order(array $order_data)
    {
        $response = (new WP_Http())->post(Api::getBaseUrl() . '/api/v1/Webaanvragen', [
            'headers' => [
                'Authorization' => 'Bearer ' . Api::getToken(),
                'Content-Type'  => 'application/json; charset=utf-8',
            ],
            'body'       => collect($order_data)->toJson(),
            'ssl_verify' => false,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($statusCode !== 201) {
            Logger::error('Order creation failed', 'order', [
                'status_code' => $statusCode,
                'response'    => $body
            ]);

            $error = new WP_Error(
                $statusCode,
                esc_html__('Er is iets misgegaan bij het verwerken van je aanmelding. Probeer het later opnieuw.', 'coachview')
            );
            $error->add_data($body, 'error_details');
            return $error;
        }

        Logger::info("Order created", 'order', ['result' => $body]);
        return json_decode($body, true);
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

