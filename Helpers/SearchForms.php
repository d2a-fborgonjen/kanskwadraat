<?php

namespace Coachview\Helpers;

use Coachview\Constants;

class SearchForms
{
    public static function get_all(): array
    {
        $forms = get_option(Constants::OPTION_SEARCH_FORMS, []);
        return is_array($forms) ? $forms : [];
    }

    public static function get_by_name(string $formName): ?array
    {
        $forms = self::get_all();
        foreach ($forms as $form) {
            if (isset($form['name']) && $form['name'] === $formName) {
                return $form;
            }
        }
        return null;
    }
}
