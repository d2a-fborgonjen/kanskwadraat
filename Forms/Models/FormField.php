<?php

namespace Coachview\Forms\Models;

use Coachview\Presentation\Enums\RegistrationType;

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
        return '<div class="elementor-field-group elementor-field-type-' . $this->type . '">'
            . $this->render_field()
            . '</div>';
    }

    public function render_field(): string
    {
//        error_log("Rendering field: {$this->field}, type: {$this->type}, required: {$this->required}, placeholder: {$this->placeholder}");
        switch ($this->type) {
            case 'text':
            case 'number':
            case 'date':
            case 'email':
                return $this->render_input();
            case 'textarea':
                return $this->render_textarea();
            case 'select':
                return $this->render_select();
            case 'checkbox':
                return $this->render_checkbox();
            case 'radio':
                return $this->render_radio();
            default:
                return 'Unknown field type: ' . esc_html($this->type);
        }
    }

    public function render_input(): string
    {
        $required = $this->required ? ' required' : '';
        $attributes = '';
        foreach ($this->attributes as $key => $value) {
            $attributes .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        return '
            <input class="elementor-field elementor-size-sm  elementor-field-textual"
            placeholder="' . esc_attr($this->get_placeholder()) . '"
            type="' . esc_attr($this->type) . '" 
            name="' . esc_attr($this->field) . '" 
            id="' . esc_attr($this->field) . '" 
            ' . $required . '
            ' . $attributes .
            '>';
    }

    private function render_textarea(): string
    {
        $required = $this->required ? ' required' : '';
        return '<textarea
            name="' . esc_attr($this->field) . '" 
            id="' . esc_attr($this->field) . '" 
            placeholder="' . esc_attr($this->get_placeholder()) . '"
            class="form-control"' . $required . '></textarea>';
    }

    private function render_select(): string
    {
        $required = $this->required ? ' required' : '';
        $html = '<select name="' . esc_attr($this->field) . '" id="' . esc_attr($this->field) . '" class="form-control"' . $required . '>';
        foreach ($this->options as $option) {
            $html .= '<option value="' . esc_attr($option['value']) . '">' . esc_html($option['label']) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    private function render_checkbox(): string
    {
        $required = $this->required ? 'required' : '';
        $html = '<div class="form-checkbox-group">';
        $html .= '<input type="checkbox" name="' . esc_attr($this->field) . '" id="' . esc_attr($this->field) . '" value="1" ' . $required . '>';
        $html .= '<label for="' . esc_attr($this->field) . '">' . esc_html($this->label) . '</label>';
        $html .= '</div>';
        return $html;
    }

    private function render_radio(): string
    {
        $required = $this->required ? ' required' : '';
        $html = '<div class="form-radio-group">';
        foreach ($this->options as $option) {
            $html .= '<div class="form-radio-item">';
            $html .= '<input type="radio" name="' . esc_attr($this->field) . '" id="' . esc_attr($this->field) . '_' . esc_attr($option['value']) . '" value="' . esc_attr($option['value']) . '"' . $required . '>';
            $html .= '<label for="' . esc_attr($this->field) . '_' . esc_attr($option['value']) . '">' . esc_html($option['label']) . '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function get_placeholder(): string
    {
        return esc_attr($this->placeholder ?: $this->label . ($this->required ? ' *' : ''));
    }
}
