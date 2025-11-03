<?php

namespace Coachview\Forms\Models;

use Coachview\Sync\Models\Enums\RegistrationType;

abstract class FormElement {

    public function __construct($rules){
    }

    public function canShow(string $form, RegistrationType $type): bool {
        $onlyForType = isset($this->rules['onlyForType']) ? RegistrationType::from($this->rules['onlyForType']) : false;
        $excludeType = isset($this->rules['excludeType']) ? RegistrationType::from($this->rules['excludeType']) : false;
        $onlyForForm = $this->rules['onlyForForm'] ?? false;
        $excludeForm = $this->rules['excludeForm'] ?? false;
//        error_log("Checking visibility for form: $form, type: $type->value, rules: " . print_r($this->rules, true));
        return (!$onlyForForm || $onlyForForm == $form) &&
            (!$excludeForm || $excludeForm != $form) &&
            (!$onlyForType || $onlyForType == $type) &&
            (!$excludeType || $excludeType != $type);
    }
}