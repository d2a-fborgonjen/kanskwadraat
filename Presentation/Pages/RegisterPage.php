<?php
namespace Coachview\Presentation\Pages;

use Coachview\Models\FormSection;
use Coachview\Models\RegistrationType;
use Coachview\Presentation\TemplateEngine;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class RegisterPage
{
    private $templateEngine;

    public function __construct() {
        add_shortcode('cv_register_form', [$this, 'apply_register_form_shortcode']);
        add_filter('query_vars', [$this, 'parse_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);
        add_action('init', [$this, 'add_rewrite_rule']);
    }

    public function parse_query_vars($vars): array {
        $vars[] = 'register';
        return $vars;
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^aanmelden/?$', 'index.php?register=1', 'top');
    }

    public function apply_register_form_shortcode($atts): string
    {
        $atts = shortcode_atts(['vid' => null, 'pid' => null], $atts, 'cv_register_form');
        return $this->render_register_page($atts['vid'], $atts['pid']);
    }


    public function template_redirect(): void {
        if (get_query_var('register')) {

            $variation_id = wp_get_query_var('vid');
            $product_id = wp_get_query_var('pid');
            $training_id = wp_get_query_var('tid');

            if (!empty($training_id)) {
                $products = wc_get_products([
                    'type' => 'variation',
                    'limit' => 1,
                    'meta_key' => 'coachview_id',
                    'meta_value' => $training_id,
                ]);
                error_log(print_r($products, true));
                if (!empty($products)) {
                    $product = $products[0];
                    $variation_id = $product->get_id();
                }
            }

            echo $this->render_register_page($variation_id, $product_id);
            exit;
        }
    }

    private function render_register_page(mixed $variation_id, mixed $product_id): string
    {
        $this->templateEngine = new TemplateEngine();
        [$training_type, $training] = $this->resolve_training($variation_id, $product_id);
        if (!$training_type) {
            return '<p>' . esc_html__('Ongeldige training.', 'coachview') . '</p>';
        }

        wp_enqueue_style('coachview-register', cv_assets_url('css/register-page.css'));
        wp_enqueue_script('coachview-register-wizard', cv_assets_url('js/register-page-wizard.js'), ['jquery'], '1.0', true);
        wp_enqueue_script('coachview-register-participants', cv_assets_url('js/register-page-participants.js'), ['jquery'], '1.0', true);
        wp_enqueue_script('coachview-register', cv_assets_url('js/register-page.js'), ['jquery', 'coachview-register-wizard', 'coachview-register-participants'], '1.0', true);
        return $this->render_form($training_type, $training);
    }

    private function render_form(WC_Product $training_type, ?WC_Product_Variation $training): string
    {
        $form_type = get_post_meta($training_type->get_id(), 'form_type', true) ?? 'default';
        $registration_type = get_registration_type($training_type);
        $participant_header = get_post_meta(get_the_ID(), 'participant_header', true) ?? null;
        $contact_person_header = get_post_meta(get_the_ID(), 'contact_person_header', true) ?? null;


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
                    'form' => $section->render($form_type, $registration_type),
                ];
            }
        }
        
        $data = [
            // Page structure
            'header' =>  $this->captureHeader(),
            'footer' => $this->captureFooter(),

            // Order details
            'training_type_title' => $training_type->get_title(),
            'price' => $training_type->get_price(),

            // Form contents
            'hidden_inputs' => $this->render_hidden_inputs($training_type, $training),
            'form_sections' => $rendered_sections,
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

    public function render_form_header($training_type, $training = null)
    {
        $data = [
            'training_type_title' => $training_type->get_title()
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
        
        return $this->templateEngine->render('form-header', $data);
    }

    private function generate_form_token() {
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

    public function render_form_section(string $form_type, RegistrationType $registration_type): string {
        if (!$this->canShow($form_type, $registration_type)) {
            return '';
        }

        $templateEngine = new TemplateEngine();

        // Prepare items with their render methods
        $renderedItems = [];
        foreach ($this->items as $item) {
            $renderedItems[] = $item->render($form_type, $registration_type);
        }

        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'items' => $renderedItems
        ];

        return $templateEngine->render('form-section', $data);
    }

    public function captureHeader()
    {
        ob_start();
        get_header();
        return ob_get_clean();
    }

    public function captureFooter()
    {
        ob_start();
        get_footer();
        return ob_get_clean();
    }

}