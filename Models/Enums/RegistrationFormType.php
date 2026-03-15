<?php

namespace Coachview\Models\Enums;

enum RegistrationFormType: string
{
    case DEFAULT = 'default';
    case PARTOU = 'partou';
    case PARTOU_BABY = 'partou-baby';
    case COACHED_EMPLOYEE = 'coached-employee';

    public function label(): string
    {
        return match($this) {
            self::DEFAULT => 'Standaard formulier',
            self::PARTOU => 'Partou',
            self::PARTOU_BABY => 'Partou baby',
            self::COACHED_EMPLOYEE => 'Gecoachte medewerker (kijk je rijk)',
        };
    }
}