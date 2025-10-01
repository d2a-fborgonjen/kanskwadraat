<?php

namespace Coachview\Forms;

use WC_Product_Variable;
use WC_Product_Variation;
use WC_Product_Simple;
use WC_Product;
use Coachview\Forms\Models\FormSection;

class OrderForm
{
    public function __construct()
    {
        add_shortcode('coachview_order_form', [$this, 'render']);
    }

    public function render(): string
    {
        [$training_type, $training] = $this->resolve_training();
        if (!$training_type) {
            return '<p>' . esc_html__('Ongeldige training.', 'coachview') . '</p>';
        }

        wp_enqueue_style('coachview-forms', plugin_dir_url(__FILE__) . '../assets/css/coachview-forms.css');
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
            FormSection::load('factuurgegevens.json')];

        $form = '<div class="elementor-widget-form">';
        $form .= $this->get_header($training_type, $training);
        $form .= '<form class="coachview-form " method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $form .= $this->render_hidden_inputs($training_type, $training);
        foreach ($form_sections as $section) {
            $form .= $section->render($form_type, $registration_type);
        };
        $form .= '<button type="submit" class="elementor-button">Verzenden</button>';
        $form .= '</form>';
        $form .= '</div>';
        return $form;
    }

    private function render_hidden_inputs(WC_Product $training_type, ?WC_Product_Variation $training): string
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

        return collect($hidden_form_data)->map(function ($value, $key) {
            return sprintf('<input type="hidden" name="%s" value="%s">', esc_attr($key), esc_attr($value));
        })->implode("\n");
    }

    private function get_header($training_type, ?WC_Product_Variation $training): string
    {
        $header = '<h2>' . esc_html($training_type->get_title()) . '</h2>';
        if ($training) {
            $location = collect(get_post_meta($training->get_id(), 'location', true))->first() ?? 'Onbekend';
            $startDate = get_post_meta($training->get_id(), 'start_date', true);
            $day = date_i18n('l', $startDate->getTimestamp());
            $date = date_i18n('j F', $startDate->getTimestamp());

            $header .= "<h3>$day $date - $location</h3>";
        }
        return $header;
    }

    private function resolve_training(): array
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
}