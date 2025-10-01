<?php

namespace Coachview\Forms\Models;

use Coachview\Presentation\Enums\RegistrationType;

class FormGroup extends FormElement
{

    public function __construct(
        public array $fields = [],
        public array $rules = [])
    {
        parent::__construct($rules);
    }

    public static function create($field_data): FormGroup
    {
        return new FormGroup(
            fields: collect($field_data['fields'] ?? [])
                ->map(function ($field) {
                    return FormField::create($field);
                })->toArray(),
            rules: $field_data['rules'] ?? []);
    }

    public function render(string $form_type, RegistrationType $registration_type): string
    {
        if (!$this->canShow($form_type, $registration_type)) {
            return '';
        }
        $output = "<div class='elementor-form-fields-wrapper cv-form-group'>";

        foreach ($this->fields as $field) {
            if ($field->canShow($form_type, $registration_type)) {
                $output .= $field->render($form_type, $registration_type);
            }
        }
        $output .= "</div>";
        return $output;
    }
}