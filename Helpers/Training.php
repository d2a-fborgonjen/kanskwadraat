<?php

namespace Coachview\Helpers;

use Coachview\Constants;
use Coachview\Models\Enums\CourseFormat;

class Training
{
    /**
     * Prepares the planning data for a training (product variation)
     * @param $variation - The product variation
     * @return array
     */
    public static function prepare_planning_data($variation): array
    {
        $planningJson = MetaHelpers::get_string($variation->get_id(), Constants::META_PLANNING);
        $planningEvents = json_decode($planningJson, true) ?? [];
        $planningEvents = array_filter($planningEvents, function($event) {
            return $event['course_format'] != CourseFormat::E_LEARNING->value;
        });

        $first_date = collect($planningEvents)->pluck('date')->filter()->sort()->first();
        return array_map(function($event) use ($first_date) {
            $entry = [];
            $entry['course_format'] = $event['course_format'];
            $entry['name'] = $event['name'];

            if (!empty($event['start_time'])) {
                $entry['time'] = date_i18n('H:i', strtotime($event['start_time']));
                if (!empty($event['end_time'])) {
                    $entry['time'] .= ' - ' . date_i18n('H:i', strtotime($event['end_time']));
                }
            }

            if (!empty($event['date'])) {
                $entry['formatted_date'] = date_i18n('D. j M. Y', strtotime($event['date']));
            }
            return $entry;
        }, $planningEvents);
    }
}

