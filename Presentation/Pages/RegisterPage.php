<?php
namespace Coachview\Presentation\Pages;

use Coachview\Forms\Models\FormSection;
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
            echo $this->render_register_page($_GET['vid'], $_GET['pid']);
            exit;
        }
    }

    private function render_register_page(mixed $vid, mixed $pid): string
    {
        $this->templateEngine = new TemplateEngine();
        [$training_type, $training] = $this->resolve_training($vid, $pid);
        if (!$training_type) {
            return '<p>' . esc_html__('Ongeldige training.', 'coachview') . '</p>';
        }
        wp_enqueue_style('coachview-common', plugin_dir_url(__FILE__) . '../../assets/css/common.css');
        wp_enqueue_style('coachview-register', plugin_dir_url(__FILE__) . '../../assets/css/register.css');
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
                $rendered_sections[] = $section->render($form_type, $registration_type);
            }
        }
        
        $data = [
            'header' => $this->captureHeader(),
            'footer' => $this->captureFooter(),
            'form_header' => $this->render_form_header($training_type, $training),
            'form_action' => esc_url(admin_url('admin-post.php')),
            'hidden_inputs' => $this->render_hidden_inputs($training_type, $training),
            'form_sections' => $rendered_sections
        ];
        
        return $this->templateEngine->render('register-page', $data);
    }

    private function resolve_training(mixed $vid, mixed $pid): array
    {
        if ($vid) {
            $training = new WC_Product_Variation((int)$vid);
            $training_type = new WC_Product_Variable($training->get_parent_id());
            return [$training_type, $training];
        }

        if ($pid) {
            $training_type = new WC_Product_Simple((int)$pid);
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

    public function render_hidden_inputs($training_type, $training = null)
    {
        $hidden_form_data = [
            '_coachview_wpnonce' => wp_create_nonce('coachview_order_form'),
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