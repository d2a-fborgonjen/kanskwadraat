<?php

namespace Coachview\Admin\Products;

use Coachview\Constants;
use Coachview\Helpers\Payment;
use Coachview\Models\Enums\RegistrationFormType;
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
        if (!get_post_meta($post->ID, Constants::META_COACHVIEW_ID, true)) {
            return;
        }
        $value = get_post_meta($post->ID, Constants::META_TRAINING_TYPE_FORM_TYPE, true);
        $participantHeaderValue = get_post_meta($post->ID, Constants::META_FORM_PARTICIPANT_HEADER, true);
        $contactPersonHeaderValue = get_post_meta($post->ID, Constants::META_FORM_CONTACT_PERSON_HEADER, true);
        wp_nonce_field('cv_save_meta', 'cv_meta_nonce');
        ?>
        <p><?php _e('Gebruik een aangepaste of versimpelde inschrijfformulier voor deze cursus.', 'coachview'); ?></p>

        <label for="<?php echo Constants::META_TRAINING_TYPE_FORM_TYPE; ?>"><?php esc_html_e('Formulier variatie', 'coachview'); ?></label>
        <select name="<?php echo Constants::META_TRAINING_TYPE_FORM_TYPE; ?>" id="<?php echo Constants::META_TRAINING_TYPE_FORM_TYPE; ?>" class="widefat">
            <option value=""><?php esc_html_e('Kies een optie', 'coachview'); ?></option>
            <?php foreach (RegistrationFormType::cases() as $type): ?>
                <option value="<?php echo esc_attr($type->value); ?>" <?php selected($value, $type->value); ?>>
                    <?php esc_html_e($type->label(), 'coachview'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p><?php esc_html_e('Stel hier aangepaste kopteksten in voor deelnemer en pedagogisch medewerker/contactpersoon', 'coachview'); ?></p>
        <label for="<?php echo Constants::META_FORM_PARTICIPANT_HEADER; ?>"><?php esc_html_e('Deelnemer titel', 'coachview'); ?></label>
        <input type="text"
               id="<?php echo Constants::META_FORM_PARTICIPANT_HEADER; ?>"
               name="<?php echo Constants::META_FORM_PARTICIPANT_HEADER; ?>"
               placeholder="Deelnemer aan..."
               class="widefat" value="<?php echo esc_attr($participantHeaderValue); ?>">

        <label for="<?php echo Constants::META_FORM_CONTACT_PERSON_HEADER; ?>"><?php esc_html_e('Contactpersoon titel', 'coachview'); ?></label>
        <input type="text"
               id="<?php echo Constants::META_FORM_CONTACT_PERSON_HEADER; ?>"
               name="<?php echo Constants::META_FORM_CONTACT_PERSON_HEADER; ?>"
               placeholder="Pedagogisch medewerker die je gaat coachen"
               class="widefat" value="<?php echo esc_attr($contactPersonHeaderValue); ?>">
        <?php
    }

    public function render_payment_types_metabox($post)
    {
        ?>
        <p><?php _e('Selecteer welke betaalwijzen ondersteund worden voor dit product.', 'coachview'); ?></p>

        <label for="<?php echo Constants::META_PRODUCT_PAYMENT_METHODS; ?>"><?php esc_html_e('Betaalwijzen', 'coachview'); ?></label>

        <?php
        $savedMethods = get_post_meta($post->ID, Constants::META_PRODUCT_PAYMENT_METHODS, true);
        foreach (Payment::getPaymentMethods() as $method)
        {
            $checked = '';
            if (is_array($savedMethods) && in_array($method['id'], $savedMethods, true)) {
                $checked = 'checked';
            }
            ?>
            <div>
                <label>
                    <input type="checkbox" name="<?php echo Constants::META_PRODUCT_PAYMENT_METHODS; ?>[]" value="<?php echo esc_attr($method['id']); ?>" <?php echo $checked; ?>>
                    <?php echo esc_html($method['name']); ?>
                </label>
            </div>
            <?php
        }
    }

    public function render_hide_option_metabox($post)
    {
        if (!get_post_meta($post->ID, Constants::META_COACHVIEW_ID, true)) {
            return;
        }
        $value = get_post_meta($post->ID, Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH, true) ?: 'no';
        ?>
        <p><?php esc_html_e('Verberg deze training van de zoekresultaten', 'coachview'); ?></p>
        <label>
            <input type="radio" name="<?php echo Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH; ?>" value="yes" <?php checked($value, 'yes'); ?>>
            <?php esc_html_e('Ja', 'coachview'); ?>
        </label><br>
        <label>
            <input type="radio" name="<?php echo Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH; ?>" value="no" <?php checked($value, 'no'); ?>>
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

        if (isset($_POST[Constants::META_TRAINING_TYPE_FORM_TYPE])) {
            update_post_meta($post_id, Constants::META_TRAINING_TYPE_FORM_TYPE, sanitize_text_field($_POST[Constants::META_TRAINING_TYPE_FORM_TYPE]));
        } else {
            delete_post_meta($post_id, Constants::META_TRAINING_TYPE_FORM_TYPE);
        }

        if (isset($_POST[Constants::META_FORM_PARTICIPANT_HEADER])) {
            update_post_meta($post_id, Constants::META_FORM_PARTICIPANT_HEADER, sanitize_text_field($_POST[Constants::META_FORM_PARTICIPANT_HEADER]));
        } else {
            delete_post_meta($post_id, Constants::META_FORM_PARTICIPANT_HEADER);
        }

        if (isset($_POST[Constants::META_FORM_CONTACT_PERSON_HEADER])) {
            update_post_meta($post_id, Constants::META_FORM_CONTACT_PERSON_HEADER, sanitize_text_field($_POST[Constants::META_FORM_CONTACT_PERSON_HEADER]));
        } else {
            delete_post_meta($post_id, Constants::META_FORM_CONTACT_PERSON_HEADER);
        }

        if (isset($_POST[Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH]) && $_POST[Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH] === 'yes') {
            update_post_meta($post_id, Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH, 'yes');
        } else {
            delete_post_meta($post_id, Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH);
        }

        if (isset($_POST[Constants::META_PRODUCT_PAYMENT_METHODS]) && is_array($_POST[Constants::META_PRODUCT_PAYMENT_METHODS])) {
            $methods = array_map('sanitize_text_field', $_POST[Constants::META_PRODUCT_PAYMENT_METHODS]);
            update_post_meta($post_id, Constants::META_PRODUCT_PAYMENT_METHODS, $methods);
        } else {
            delete_post_meta($post_id, Constants::META_PRODUCT_PAYMENT_METHODS);
        }

        TrainingAgenda::clear_cached_agenda_data();
    }
}
