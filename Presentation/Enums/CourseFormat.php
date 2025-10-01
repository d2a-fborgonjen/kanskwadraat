<?php

namespace Coachview\Presentation\Enums;

enum CourseFormat: string
{
    case E_LEARNING = 'e-learning';
    case CLASSROOM = 'classroom';
    case VIRTUAL_CLASSROOM = 'virtual classroom';
    case BLENDED = 'blended';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'klassikaal' => self::CLASSROOM,
            'virtual classroom' => self::VIRTUAL_CLASSROOM,
            default => self::E_LEARNING
        };
    }
}