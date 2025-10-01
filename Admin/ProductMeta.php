<?php

namespace Coachview\Admin;

class ProductMeta
{
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_metaboxes']);
        add_action('save_post', [$this, 'save_meta']);
    }

    public function add_metaboxes()
    {
        add_meta_box(
            'form_type',
            __('Coachview Formulier Opties', 'coachview'),
            [$this, 'render_form_type_metabox'],
            'product',
            'side',
            'core'
        );

        add_meta_box(
            'hide_from_search',
            __('Coachview Weergave Opties', 'coachview'),
            [$this, 'render_hide_option_metabox'],
            'product',
            'side',
            'core'
        );
    }

    public function render_form_type_metabox($post)
    {
        if (!get_post_meta($post->ID, 'coachview_id', true)) {
            return;
        }
        $value = get_post_meta($post->ID, 'form_type', true);
        $participantHeaderValue = get_post_meta($post->ID, 'form_participant_header', true);
        $contactPersonHeaderValue = get_post_meta($post->ID, 'form_contact_person_header', true);
        wp_nonce_field('cv_save_meta', 'cv_meta_nonce');
        ?>
        <p><?php _e('Gebruik een aangepaste of versimpelde inschrijfformulier voor deze cursus.', 'coachview'); ?></p>

        <label for="form_type"><?php esc_html_e('Formulier variatie', 'coachview'); ?></label>
        <select name="form_type" id="form_type" class="widefat">
            <option value=""><?php esc_html_e('Kies een optie', 'coachview'); ?></option>
            <option value="default" <?php selected($value, 'default'); ?>><?php esc_html_e('Standaard formulier', 'coachview'); ?></option>
            <option value="contact-person" <?php selected($value, 'contact-person'); ?>><?php esc_html_e('Formulier met contactpersoon', 'coachview'); ?></option>
            <option value="partou" <?php selected($value, 'partou'); ?>><?php esc_html_e('Partou formulier', 'coachview'); ?></option>
        </select>

        <p><?php esc_html_e('Stel hier aangepaste kopteksten in voor deelnemer en pedagogisch medewerker/contactpersoon', 'coachview'); ?></p>
        <label for="form_participant_header"><?php esc_html_e('Deelnemer titel', 'coachview'); ?></label>
        <input type="text"
               id="form_participant_header"
               name="form_participant_header"
               placeholder="Deelnemer aan..."
               class="widefat" value="<?php echo esc_attr($participantHeaderValue); ?>">

        <label for="form_contact_person_header"><?php esc_html_e('Contactpersoon titel', 'coachview'); ?></label>
        <input type="text"
               id="form_contact_person_header"
               name="form_contact_person_header"
               placeholder="Pedagogisch medewerker die je gaat coachen"
               class="widefat" value="<?php echo esc_attr($contactPersonHeaderValue); ?>">
        <?php
    }

    public function render_hide_option_metabox($post)
    {
        if (!get_post_meta($post->ID, 'coachview_id', true)) {
            return;
        }

        $value = get_post_meta($post->ID, 'hide_from_search', true);
        ?>
        <p><?php esc_html_e('Verberg deze training van de zoekresultaten', 'coachview'); ?></p>
        <label>
            <input type="radio" name="hide_from_search" value="1" <?php checked($value, '1'); ?>>
            <?php esc_html_e('Ja', 'coachview'); ?>
        </label><br>
        <label>
            <input type="radio" name="hide_from_search" value="0" <?php checked($value, '0'); ?>>
            <?php esc_html_e('Nee', 'coachview'); ?>
        </label>
        <?php
    }

    public function save_meta($post_id)
    {
        if (!isset($_POST['cv_meta_nonce']) || !wp_verify_nonce($_POST['cv_meta_nonce'], 'cv_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (get_post_type($post_id) !== 'product') {
            return;
        }

        if (isset($_POST['form_type'])) {
            update_post_meta($post_id, 'form_type', sanitize_text_field($_POST['form_type']));
        }

        if (isset($_POST['form_participant_header'])) {
            update_post_meta($post_id, 'form_participant_header', sanitize_text_field($_POST['form_participant_header']));
        }

        if (isset($_POST['form_contact_person_header'])) {
            update_post_meta($post_id, 'form_contact_person_header', sanitize_text_field($_POST['form_contact_person_header']));
        }

        if (isset($_POST['form_hide_from_search'])) {
            update_post_meta($post_id, 'form_hide_from_search', sanitize_text_field($_POST['form_hide_from_search']));
        }
    }
}
