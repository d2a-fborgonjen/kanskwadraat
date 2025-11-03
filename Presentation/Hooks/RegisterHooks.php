<?php

namespace Coachview\Presentation\Hooks;

use WP_Http;


class RegisterHooks {

    public function __construct() {
        add_action('admin_post_coachview_training_form', [$this, 'process']);
        add_action('admin_post_nopriv_coachview_training_form', [$this, 'process']);
    }

    public function process(): string {
        if (isset($_POST['_coachview_wpnonce']) && wp_verify_nonce($_POST['_coachview_wpnonce'], 'coachview_order_form')) {
            $order_data = $this->to_coachview_order_data($_POST);
            error_log("Processing form submission for training registration." . print_r($order_data, true));

            $result = $this->create_coachview_order($order_data);
            if ($result) {
                $this->update_total_participants($_POST);

                if (isset($registration['betaalproviderRedirectUrl'])) {
                    wp_redirect($registration['betaalproviderRedirectUrl']);
                    exit;
                }
                wp_safe_redirect(get_order_success_redirect_url());
                exit;
            }
            error_log("Order creation result: " . print_r($result, true));
            return '<p>' . esc_html__('Dankjewel voor je aanmelding.', 'coachview') . '</p>';
        } else {
            error_log('Invalid form submission attempt.');
            return '<p>' . esc_html__('Ongeldige formulierverzending.', 'coachview') . '</p>';
        }
    }

    /**
     * Collect the data and prepare it to be sent as web-order to Coachview.
     */
    private function to_coachview_order_data($post)
    {
        $deelnemers = $post['deelnemer'];
        $contactpersoon = isset($post['is_contactpersoon']) ? $post['contactpersoon'] : '';
        $aanvraagIsOrder = true;
        $bedrijf = !empty($post['bedrijf']['naam']) ? $post['bedrijf'] : '';
        $debiteur = collect($post['debiteur'])->put('emailType', (!empty($bedrijf) ? 'Bedrijf' : 'ContactpersoonAanvraag'))->toArray();
        $opmerking = isset($post['opmerking']) ? $post['opmerking'] : '';

        $cv_data = [
            'referentieNrKlant' => "",
            'opmerking' => $opmerking,
            'vrijevelden' => "",
            'bedrijf' => $bedrijf,
            'aanvraagIsOrder' => $aanvraagIsOrder,
            'contactpersoon' => $contactpersoon,
            'debiteur' => $debiteur,
            'deelnemers' => isset($deelnemers) ? array_values($deelnemers) : '',
            'opleidingen' => [
                $post['opleidingen']
            ]
        ];

        return $cv_data;
    }

    /**
     * Create a new order in Coachview.
     * If the order is created successfully, update the total participants for the training.
     */
    private function create_coachview_order($order_data) {
        $response = (new WP_Http)->post(coachview_api_url() . '/api/v1/Webaanvragen', [
            'headers' => [
                'Authorization' => 'Bearer ' . coachview_api_token(),
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'body' => collect($order_data)->toJson(),
            'ssl_verify' => false
        ]);


        if (wp_remote_retrieve_response_code($response) !== 201) {
            wp_send_json_error("Er is iets misgegaan bij het verwerken van je aanmelding. Probeer het later opnieuw.", 500);
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function update_total_participants($post_data)
    {
        if (empty($post_data['training_id'])) {
            return;
        }
        $training = wc_get_product($post_data['training_id']);
        $training->set_stock_quantity($training->get_stock_quantity() - count($post_data['deelnemer']));
        $training->save();
    }
}