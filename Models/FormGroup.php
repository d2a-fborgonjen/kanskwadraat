<?php

namespace Coachview\Models;

use Coachview\Presentation\TemplateEngine;

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
        
        $templateEngine = new TemplateEngine();
        
        // Filter fields that should be shown and prepare their rendered content
        $renderedFields = [];
        foreach ($this->fields as $field) {
            if ($field->canShow($form_type, $registration_type)) {
                $renderedFields[] = $field->render($form_type, $registration_type);
            }
        }
        
        $data = ['fields' => $renderedFields];
        return $templateEngine->render('form-group', $data);
    }
}