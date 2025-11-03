<?php

namespace Coachview\Models;

use Coachview\Presentation\TemplateEngine;

class FormSection extends FormElement {

    public function __construct(
        public string $title,
        public string $description = '',
        public array $items = [],
        public array $rules = []) {
        parent::__construct($rules);
    }

    public function render(string $form_type, RegistrationType $registration_type): string {
        if (!$this->canShow($form_type, $registration_type)) {
            return '';
        }
        
        $templateEngine = new TemplateEngine();
        
        // Prepare items with their render methods
        $renderedItems = [];
        foreach ($this->items as $item) {
            $renderedItems[] = $item->render($form_type, $registration_type);
        }
        
        $data = [
            'title' => $this->title,
            'description' => $this->description,
            'items' => $renderedItems
        ];
        
        return $templateEngine->render('form-section', $data);
    }

    public function with_title(?string $title): self {
        if ($title) {
            $this->title = $title;
        }
        return $this;
    }

    public static function load(string $filename): ?FormSection
    {
        $file_path = plugin_dir_path(__FILE__) . '../assets/forms/' . $filename;
        if (!file_exists($file_path)) {
            return null;
        }
        $file_contents = file_get_contents($file_path);
        $json_data = collect(json_decode($file_contents, true) ?? []);

        $title = $json_data->get('title');
        $description = $json_data->get('description', '');
        $rules = $json_data->get('rules', []);
        $items = collect($json_data->get('items', []))
            ->map(function($field_data) {
                return FormSection::to_section_item($field_data);
            });
        return new FormSection(
            title: $title,
            description: $description,
            items: $items->toArray(),
            rules: $rules);
    }

    private static function to_section_item(array $field_data): FormField | FormGroup
    {
        if (isset($field_data['fields'])) {
            return FormGroup::create($field_data);
        }
        return FormField::create($field_data);
    }
}