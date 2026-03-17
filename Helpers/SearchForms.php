<?php

namespace Coachview\Helpers;

use Coachview\Constants;

class SearchForms
{
    public static function getAll(): array
    {
        $forms = get_option(Constants::OPTION_SEARCH_FORMS, []);
        return is_array($forms) ? $forms : [];
    }

    public static function getByName(string $formName): ?array
    {
        $forms = self::getAll();
        foreach ($forms as $form) {
            if (isset($form['name']) && $form['name'] === $formName) {
                return $form;
            }
        }
        return null;
    }
}
