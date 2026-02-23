<?php

namespace Coachview\Presentation\Components;

use Coachview\Constants;
use Coachview\Models\FormSection;
use Coachview\Presentation\TemplateEngine;

use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class RegisterForm
{
    private $templateEngine;

    public function __construct()
    {
        add_shortcode('cv_register_form', [$this, 'render_shortcode']);
        add_filter('query_vars', [$this, 'parse_query_vars']);

        if (!has_custom_register_page()) {
            add_action('template_redirect', [$this, 'template_redirect']);
            add_action('init', [$this, 'add_register_rewrite_rule']);
        }
    }

    public function parse_query_vars($vars): array
    {
        $vars[] = 'register';
        return $vars;
    }

    public function add_register_rewrite_rule(): void
    {
        $slug = Constants::DEFAULT_REGISTER_PAGE_SLUG;
        add_rewrite_rule("^{$slug}/?$", 'index.php?register=1', 'top');
    }

    public function template_redirect(): void
    {
        if (get_query_var('register')) {
            echo $this->render_register_page(true);
            exit;
        }
    }

    public function render_shortcode($atts): string
    {
        return $this->render_register_page(false);
    }

    private function render_register_page(bool $with_header_and_footer = false): string
    {
        $params = $this->get_query_parameters();
        $this->templateEngine = new TemplateEngine();
        [$training_type, $training] = $this->resolve_training($params['variation_id'], $params['product_id']);
        if (!$training_type) {
            return '<p>' . esc_html__('Ongeldige training.', 'coachview') . '</p>';
        }

        wp_enqueue_script('coachview-register-wizard', cv_assets_url('js/register-page-wizard.js'), ['jquery'], '1.0', true);
        wp_enqueue_script('coachview-register-participants', cv_assets_url('js/register-page-participants.js'), ['jquery'], '1.0', true);
        wp_enqueue_script('coachview-register', cv_assets_url('js/register-page.js'), ['jquery', 'coachview-register-wizard', 'coachview-register-participants'], '1.0', true);
        return $this->render_form($training_type, $training, $with_header_and_footer);
    }

    private function render_form(WC_Product $training_type, ?WC_Product_Variation $training, bool $with_header_and_footer): string
    {
        $form_type = get_post_meta($training_type->get_id(), 'cv_form_type', true) ?? 'default';
        $registration_type = get_registration_type($training_type);
        $participant_header = get_post_meta($training_type->get_id(), 'cv_form_participant_header', true) ?? null;
        $contact_person_header = get_post_meta($training_type->get_id(), 'cv_form_contact_person_header', true) ?? null;


        $form_sections = [
            FormSection::load('deelnemer.json')->with_title($participant_header),
            FormSection::load('contactpersoon.json')->with_title($contact_person_header),
            FormSection::load('factuurgegevens.json')
        ];

        // Render form sections as HTML strings
        $rendered_sections = [];
        foreach ($form_sections as $section) {
            if ($section->canShow($form_type, $registration_type)) {
                $rendered_sections[] = [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'form' => $section->render($form_type, $registration_type)
                ];
            }
        }

        $data = [
            // Page structure
            'header' => $with_header_and_footer ? $this->capture_header() : '',
            'footer' => $with_header_and_footer ? $this->captureFooter() : '',

            // Include styles in the html since wp_enqueue_style is sometimes ignored on shotcode rendering
            'style_urls' => [cv_assets_url('css/register-page.css')],

            // Order details
            'training_type_title' => $training_type->get_title(),
            'price' => $training_type->get_price(),

            // Form contents
            'hidden_inputs' => $this->render_hidden_inputs($training_type, $training),
            'form_sections' => $rendered_sections,

            // Payment methods
            'payment_methods' => coachview_get_product_payment_methods($training_type),
        ];

        if ($training) {
            $location = collect(get_post_meta($training->get_id(), 'location', true))->first() ?? 'Onbekend';
            $startDate = get_post_meta($training->get_id(), 'start_date', true);
            $day = date_i18n('l', strtotime($startDate));
            $date = date_i18n('j F', strtotime($startDate));
            $data['training'] = true;
            $data['training_day'] = $day;
            $data['training_date'] = $date;
            $data['training_location'] = $location;
        }
        return $this->templateEngine->render('register-page', $data);
    }

    private function generate_form_token()
    {
        $token = wp_generate_password(20, false); // random 20-char string
        $key = 'coachview_form_' . $token;
        set_transient($key, true, 60 * 60); // 1 hour expiration
        return $token;
    }

    public function render_hidden_inputs($training_type, $training = null)
    {
        $hidden_form_data = [
            '_coachview_form_token' => $this->generate_form_token(),
            'action' => 'coachview_training_form',
            'opleidingen[opleidingssoortId]' => get_post_meta($training_type->get_id(), 'coachview_id', true),
            'debiteur[verzendwijzeFactuur]' => 'Email'
        ];

        if ($training) {
            $hidden_form_data['opleidingen[opleidingId]'] = get_post_meta($training->get_id(), 'coachview_id', true);
            $hidden_form_data['training_id'] = $training->get_id();
        }

        return $this->templateEngine->render('hidden-inputs', ['hidden_inputs' => $hidden_form_data]);
    }


    private function get_query_parameters(): array
    {
        $variation_id = wp_get_query_var('woo_vid');
        $product_id = wp_get_query_var('woo_pid');
        $cv_training_id = wp_get_query_var('cv_tid');

        if (!empty($cv_training_id)) {
            $variations = wc_get_products([
                'type' => 'variation',
                'limit' => 1,
                'meta_key' => 'coachview_id',
                'meta_value' => $cv_training_id,
            ]);
            if (!empty($variations)) {
                $variation = $variations[0];
                $variation_id = $variation->get_id();
            }
        }
        return [
            'variation_id' => $variation_id,
            'product_id' => $product_id
        ];
    }

    private function resolve_training(mixed $variation_id, mixed $product_id): array
    {
        if ($variation_id) {
            $training = new WC_Product_Variation((int)$variation_id);
            $training_type = new WC_Product_Variable($training->get_parent_id());
            return [$training_type, $training];
        }

        if ($product_id) {
            $training_type = new WC_Product_Simple((int)$product_id);
            return [$training_type, null];
        }

        return [null, null];
    }

    public function capture_header(): false | string
    {
        ob_start();
        get_header();
        return ob_get_clean();
    }

    public function captureFooter(): false | string
    {
        ob_start();
        get_footer();
        return ob_get_clean();
    }

}