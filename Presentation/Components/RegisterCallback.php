<?php

namespace Coachview\Presentation\Components;

use Coachview\Models\Enums\CourseFormat;
use Coachview\Models\Enums\RegistrationFormType;

class RegisterCallback extends ShortCodeComponent
{
    public static function get_shortcode(): string
    {
        return 'cv_register_callback';
    }

    public function render_shortcode($atts): string
    {
        $success_message = cv_get_register_success_message(RegistrationFormType::DEFAULT, CourseFormat::BLENDED);
        if (isset($_GET['cv_webaanvraagnr'])) {
            $order_nr = $_GET['cv_webaanvraagnr'];
            $form_type = RegistrationFormType::from(get_transient('register_success_form_type_' . $order_nr, RegistrationFormType::DEFAULT->value));
            $course_format = CourseFormat::from(get_transient('register_success_course_format_' . $order_nr, CourseFormat::BLENDED->value));
            $success_message = cv_get_register_success_message($form_type, $course_format);
        }
        return '<div class="cv-register-success-message">' . $success_message . '</div>';
    }

    public function enqueue_scripts(): void
    {
    }

    public function enqueue_styles(): void
    {
    }
}