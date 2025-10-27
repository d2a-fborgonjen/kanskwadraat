<?php
namespace Coachview\Presentation\Pages;

use Coachview\Forms\Models\FormSection;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class RegisterPage
{
    private $templateEngine;

    function __construct()
    {
        $this->templateEngine = new TemplateEngine();
        
        add_filter('query_vars', function ($vars) {
            $vars[] = 'register';
            return $vars;
        });

        add_action('template_redirect', [$this, 'template_redirect']);
    }

    public function add_rewrite_rule() {
        add_rewrite_rule('^aanmelden/?$', 'index.php?register=1', 'top');
    }

    public function template_redirect(): void {
        if (get_query_var('register')) {
            echo $this->renderRegisterPage();
            exit;
        }
    }

    private function renderRegisterPage(): string
    {
        [$training_type, $training] = $this->resolveTraining();
        if (!$training_type) {
            return '<p>' . esc_html__('Ongeldige training.', 'coachview') . '</p>';
        }

        wp_enqueue_style('coachview-forms', plugin_dir_url(__FILE__) . '../../assets/css/coachview-forms.css');
        return $this->renderForm($training_type, $training);
    }

    private function renderForm(WC_Product $training_type, ?WC_Product_Variation $training): string
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
            'form_header' => $this->renderFormHeader($training_type, $training),
            'form_action' => esc_url(admin_url('admin-post.php')),
            'hidden_inputs' => $this->renderHiddenInputs($training_type, $training),
            'form_sections' => $rendered_sections
        ];
        
        return $this->templateEngine->render('register-page', $data);
    }

    private function resolveTraining(): array
    {
        if (isset($_GET['vid'])) {
            $training = new WC_Product_Variation((int) $_GET['vid']);
            $training_type = new WC_Product_Variable($training->get_parent_id());
            return [$training_type, $training];
        }

        if (isset($_GET['pid'])) {
            $training_type = new WC_Product_Simple((int) $_GET['pid']);
            return [$training_type, null];
        }

        return [null, null];
    }

    public function renderFormHeader($training_type, $training = null)
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

    public function renderHiddenInputs($training_type, $training = null)
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