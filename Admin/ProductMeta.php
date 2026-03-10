<?php

namespace Coachview\Admin;

use Coachview\Models\RegistrationFormType;
use Coachview\Presentation\Components\TrainingAgenda;

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
                'payment_types',
                __('Coachview Betaalwijzen', 'coachview'),
                [$this, 'render_payment_types_metabox'],
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
        $value = get_post_meta($post->ID, 'cv_form_type', true);
        $participantHeaderValue = get_post_meta($post->ID, 'cv_form_participant_header', true);
        $contactPersonHeaderValue = get_post_meta($post->ID, 'cv_form_contact_person_header', true);
        wp_nonce_field('cv_save_meta', 'cv_meta_nonce');
        ?>
        <p><?php _e('Gebruik een aangepaste of versimpelde inschrijfformulier voor deze cursus.', 'coachview'); ?></p>

        <label for="cv_form_type"><?php esc_html_e('Formulier variatie', 'coachview'); ?></label>
        <select name="cv_form_type" id="cv_form_type" class="widefat">
            <option value=""><?php esc_html_e('Kies een optie', 'coachview'); ?></option>
            <?php foreach (RegistrationFormType::cases() as $type): ?>
                <option value="<?php echo esc_attr($type->value); ?>" <?php selected($value, $type->value); ?>>
                    <?php esc_html_e($type->label(), 'coachview'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p><?php esc_html_e('Stel hier aangepaste kopteksten in voor deelnemer en pedagogisch medewerker/contactpersoon', 'coachview'); ?></p>
        <label for="cv_form_participant_header"><?php esc_html_e('Deelnemer titel', 'coachview'); ?></label>
        <input type="text"
               id="cv_form_participant_header"
               name="cv_form_participant_header"
               placeholder="Deelnemer aan..."
               class="widefat" value="<?php echo esc_attr($participantHeaderValue); ?>">

        <label for="cv_form_contact_person_header"><?php esc_html_e('Contactpersoon titel', 'coachview'); ?></label>
        <input type="text"
               id="cv_form_contact_person_header"
               name="cv_form_contact_person_header"
               placeholder="Pedagogisch medewerker die je gaat coachen"
               class="widefat" value="<?php echo esc_attr($contactPersonHeaderValue); ?>">
        <?php
    }

    public function render_payment_types_metabox($post)
    {
        ?>
        <p><?php _e('Selecteer welke betaalwijzen ondersteund worden voor dit product.', 'coachview'); ?></p>

        <label for="cv_payment_methods"><?php esc_html_e('Betaalwijzen', 'coachview'); ?></label>

        <?php
        $savedMethods = get_post_meta($post->ID, 'cv_payment_methods', true);
        foreach (coachview_get_payment_methods() as $method)
        {
            $checked = '';
            if (is_array($savedMethods) && in_array($method['id'], $savedMethods, true)) {
                $checked = 'checked';
            }
            ?>
            <div>
                <label>
                    <input type="checkbox" name="cv_payment_methods[]" value="<?php echo esc_attr($method['id']); ?>" <?php echo $checked; ?>>
                    <?php echo esc_html($method['name']); ?>
                </label>
            </div>
            <?php
        }
    }

    public function render_hide_option_metabox($post)
    {
        if (!get_post_meta($post->ID, 'coachview_id', true)) {
            return;
        }
        $value = get_post_meta($post->ID, 'cv_hide_from_search', true) ?: 'no';
        ?>
        <p><?php esc_html_e('Verberg deze training van de zoekresultaten', 'coachview'); ?></p>
        <label>
            <input type="radio" name="cv_hide_from_search" value="yes" <?php checked($value, 'yes'); ?>>
            <?php esc_html_e('Ja', 'coachview'); ?>
        </label><br>
        <label>
            <input type="radio" name="cv_hide_from_search" value="no" <?php checked($value, 'no'); ?>>
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

        if (isset($_POST['cv_form_type'])) {
            update_post_meta($post_id, 'cv_form_type', sanitize_text_field($_POST['cv_form_type']));
        } else {
            delete_post_meta($post_id, 'cv_form_type');
        }

        if (isset($_POST['cv_form_participant_header'])) {
            update_post_meta($post_id, 'cv_form_participant_header', sanitize_text_field($_POST['cv_form_participant_header']));
        } else {
            delete_post_meta($post_id, 'cv_form_participant_header');
        }

        if (isset($_POST['cv_form_contact_person_header'])) {
            update_post_meta($post_id, 'cv_form_contact_person_header', sanitize_text_field($_POST['cv_form_contact_person_header']));
        } else {
            delete_post_meta($post_id, 'cv_form_contact_person_header');
        }

        if (isset($_POST['cv_hide_from_search']) && $_POST['cv_hide_from_search'] === 'yes') {
            update_post_meta($post_id, 'cv_hide_from_search', 'yes');
        } else {
            delete_post_meta($post_id, 'cv_hide_from_search');
        }

        if (isset($_POST['cv_payment_methods']) && is_array($_POST['cv_payment_methods'])) {
            $methods = array_map('sanitize_text_field', $_POST['cv_payment_methods']);
            update_post_meta($post_id, 'cv_payment_methods', $methods);
        } else {
            delete_post_meta($post_id, 'cv_payment_methods');
        }

        TrainingAgenda::clear_cached_agenda_data();
    }
}
