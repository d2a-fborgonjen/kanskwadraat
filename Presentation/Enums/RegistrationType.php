<?php

namespace Coachview\Presentation\Enums;

enum RegistrationType: string
{
    case IN_COMPANY = 'in_company';
    case OPEN_ENROLLMENT = 'open_enrollment';
    case ENLIST = 'enlist';
    case DEFAULT = 'default';
}