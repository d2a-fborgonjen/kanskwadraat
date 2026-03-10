<?php

namespace Coachview\Models;

abstract class FormElement {

    public function __construct($rules){
    }

    public function canShow(RegistrationFormType $formType, RegistrationType $registrationType): bool {
        $onlyForType = isset($this->rules['onlyForType']) ? RegistrationType::from($this->rules['onlyForType']) : false;
        $excludeType = isset($this->rules['excludeType']) ? RegistrationType::from($this->rules['excludeType']) : false;
        $onlyForForm = isset($this->rules['onlyForForm']) ? RegistrationFormType::from($this->rules['onlyForForm']) : false;
        $excludeForm = isset($this->rules['excludeForm']) ? RegistrationFormType::from($this->rules['excludeForm']) : false;
        return (!$onlyForForm || $onlyForForm == $formType) &&
            (!$excludeForm || $excludeForm != $formType) &&
            (!$onlyForType || $onlyForType == $registrationType) &&
            (!$excludeType || $excludeType != $registrationType);
    }
}