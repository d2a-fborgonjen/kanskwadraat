<?php

namespace Coachview\Helpers;

use Automattic\WooCommerce\Enums\ProductStatus;
use Coachview\Constants;
use Coachview\Models\Enums\CourseFormat;
use Coachview\Models\Enums\RegistrationFormType;
use Coachview\Models\Enums\RegistrationType;
use WC_Product;
use WC_Product_Variable;

class Registration
{
    public static function get_register_form_type(int $trainingTypeId): RegistrationFormType
    {
        $type = MetaHelpers::get_string($trainingTypeId, Constants::META_TRAINING_TYPE_FORM_TYPE, RegistrationFormType::DEFAULT->value);
        return RegistrationFormType::from($type);
    }

    public static function get_course_format(int $trainingTypeId): CourseFormat
    {
        $format = MetaHelpers::get_string($trainingTypeId, Constants::META_TRAINING_TYPE_CATEGORY, CourseFormat::BLENDED->value);
        return CourseFormat::from($format);
    }

    public static function get_registration_type(WC_Product $trainingType): RegistrationType
    {
        $training_type_category = get_post_meta($trainingType->get_id(), Constants::META_TRAINING_TYPE_CATEGORY, true);

        if ($trainingType->get_status() == ProductStatus::PUBLISH &&
            (get_post_meta($trainingType->get_id(), '_yoast_wpseo_meta-robots-noindex', true) === '1'
                || MetaHelpers::get_string($trainingType->get_id(), Constants::META_TRAINING_TYPE_HIDE_FROM_SEARCH, 'no') === 'yes')) {
            return RegistrationType::IN_COMPANY;
        }

        if ($training_type_category === CourseFormat::E_LEARNING->value) {
            return RegistrationType::OPEN_ENROLLMENT;
        }

        if ($trainingType instanceof WC_Product_Variable && count($trainingType->get_available_variations()) === 0) {
            return RegistrationType::ENLIST;
        }

        return RegistrationType::DEFAULT;
    }

    public static function get_success_message(RegistrationFormType $formType, CourseFormat $courseFormat): string
    {
        $success_message_type = 'default';
        if (in_array($formType, [RegistrationFormType::PARTOU, RegistrationFormType::PARTOU_BABY], true)) {
            $success_message_type = 'partou';
        } elseif ($courseFormat === CourseFormat::E_LEARNING) {
            $success_message_type = 'elearning';
        }

        $option_name = Constants::OPTION_REGISTER_SUCCESS_MESSAGE_PREFIX . $success_message_type;
        $default = esc_html__('Dankjewel voor je aanmelding.', Constants::TEXT_DOMAIN);
        return (string) get_option($option_name, $default);
    }

    public static function get_redirect_success_message(): string
    {
        $fallback_message = esc_html__(
            'Dankjewel voor je aanmelding. Je wordt over enkele ogenblikken doorgestuurd naar de betaalpagina.',
            Constants::TEXT_DOMAIN
        );
        return (string) get_option(Constants::OPTION_REGISTER_SUCCESS_REDIRECT_MESSAGE, $fallback_message);
    }
}

