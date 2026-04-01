<?php

namespace Coachview\Presentation\Components;

use Coachview\Constants;
use Coachview\Helpers\Assets;
use Coachview\Helpers\MetaHelpers;
use Coachview\Helpers\Payment;
use Coachview\Helpers\Registration;
use Coachview\Helpers\Url;
use Coachview\Models\Enums\RegistrationFormType;
use Coachview\Models\FormSection;
use Coachview\Presentation\TemplateEngine;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class RegisterForm extends ShortCodeComponent
{
    private $templateEngine;

    public static function get_shortcode(): string
    {
        return 'cv_register_form';
    }

    public function enqueue_styles(): void
    {
        Assets::enqueueStyle(self::get_shortcode(), 'css/register-form.css');
    }

    public function enqueue_scripts(): void
    {
        Assets::enqueueScript(self::get_shortcode() . '-wizard', 'js/register-page-wizard.js', ['jquery']);
        Assets::enqueueScript(self::get_shortcode() . '-participants', 'js/register-page-participants.js', ['jquery']);
        Assets::enqueueScript(self::get_shortcode(), 'js/register-page.js', [self::get_shortcode().'-wizard', self::get_shortcode().'-participants']);
    }

    public function __construct()
    {
        parent::__construct();
        add_filter('query_vars', [$this, 'parse_query_vars']);

        if (!Url::has_custom_register_page()) {
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
        return $this->render_form($training_type, $training, $with_header_and_footer);
    }

    private function render_form(WC_Product $training_type, ?WC_Product_Variation $training, bool $with_header_and_footer): string
    {
        // Registration form variation
        $registration_form_type = Registration::get_register_form_type($training_type->get_id());
        // Type of registration (in_company, open_enrollment, enlist, )
        $registration_type = Registration::get_registration_type($training_type);
        $participant_header = MetaHelpers::form_participant_header($training_type->get_id()) ?: null;
        $contact_person_header = MetaHelpers::form_contact_person_header($training_type->get_id()) ?: null;

        $form_sections = [
            FormSection::load('deelnemer.json')->with_title($participant_header),
            FormSection::load('contactpersoon.json')->with_title($contact_person_header),
            FormSection::load('factuurgegevens.json')
        ];

        // Render form sections as HTML strings
        $rendered_sections = [];
        foreach ($form_sections as $section) {
            if ($section->canShow($registration_form_type, $registration_type)) {
                $rendered_sections[] = [
                    'id' => $section->id,
                    'title' => $section->title,
                    'description' => $section->description,
                    'form' => $section->render($registration_form_type, $registration_type)
                ];
            }
        }

        $data = [
            // Page structure
            'header' => $with_header_and_footer ? $this->capture_header() : '',
            'footer' => $with_header_and_footer ? $this->captureFooter() : '',

            // Order details
            'training_type_title' => $training_type->get_title(),
            'price' => $training_type->get_price(),

            // Form contents
            'hidden_inputs'  => $this->render_hidden_inputs($training_type, $training, $registration_form_type),
            'form_sections'  => $rendered_sections,

            // Payment methods
            'payment_methods' => Payment::getProductPaymentMethods($training_type),
        ];

        if ($training) {
            $location = collect(MetaHelpers::get_array($training->get_id(), Constants::META_LOCATION))->first() ?? 'Onbekend';
            $startDate = MetaHelpers::get_string($training->get_id(), Constants::META_START_DATE);
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

    public function render_hidden_inputs(WC_Product $training_type,
                                         ?WC_Product_Variation $training,
                                         RegistrationFormType $form_type)
    {
        $hidden_form_data = [
            '_coachview_form_token'         => $this->generate_form_token(),
            '_coachview_form_type'          => $form_type->value,
            '_coachview_course_format'      => Registration::get_course_format($training_type->get_id())->value,
            'action'                        => 'coachview_training_form',
            'opleidingen[opleidingssoortId]' => MetaHelpers::coachview_id($training_type->get_id()),
            'debiteur[verzendwijzeFactuur]' => 'Email',
        ];

        if ($training) {
            $hidden_form_data['opleidingen[opleidingId]'] = MetaHelpers::coachview_id($training->get_id());
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
                'meta_key' => Constants::META_COACHVIEW_ID,
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

    public function capture_header(): false|string
    {
        ob_start();
        get_header();
        return ob_get_clean();
    }

    public function captureFooter(): false|string
    {
        ob_start();
        get_footer();
        return ob_get_clean();
    }

}