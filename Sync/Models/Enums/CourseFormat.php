<?php

namespace Coachview\Sync\Models\Enums;

enum CourseFormat: string
{
    case E_LEARNING = 'E-learning';
    case CLASSROOM = 'Klassikaal';
    case VIRTUAL_CLASSROOM = 'Virtueel klassikaal';
    case BLENDED = 'Blended';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'blended' => self::BLENDED,
            'klassikaal' => self::CLASSROOM,
            'virtual classroom' => self::VIRTUAL_CLASSROOM,
            default => self::E_LEARNING
        };
    }
}