<?php

namespace Coachview\Forms\Models;

use Coachview\Sync\Models\Enums\RegistrationType;
use Coachview\Presentation\TemplateEngine;

class FormField extends FormElement
{

    public function __construct(
        public string $field,
        public string $type,
        public string $label = '',
        public string $placeholder = '',
        public bool   $required = false,
        public array  $options = [],
        public array  $rules = [],
        public array  $attributes = [])
    {
        parent::__construct($rules);
    }

    public static function create($field_data): FormField
    {
        return new FormField(
            field: $field_data['field'],
            type: $field_data['type'] ?? 'text',
            label: $field_data['label'] ?? '',
            placeholder: $field_data['placeholder'] ?? '',
            required: $field_data['required'] ?? false,
            options: $field_data['options'] ?? [],
            rules: $field_data['rules'] ?? []
        );
    }

    public function render(string $form_type, RegistrationType $registration_type): string
    {
        if (!$this->canShow($form_type, $registration_type)) {
            return '';
        }
        
        $templateEngine = new TemplateEngine();
        
        $data = [
            'field' => $this->field,
            'type' => $this->type,
            'label' => $this->label,
            'placeholder' => $this->get_placeholder(),
            'required' => $this->required,
            'options' => $this->options,
            'attributes' => $this->attributes
        ];
        
        return $templateEngine->render('form-field', $data);
    }


    private function get_placeholder(): string
    {
        return esc_attr($this->placeholder ?: $this->label . ($this->required ? ' *' : ''));
    }
}
