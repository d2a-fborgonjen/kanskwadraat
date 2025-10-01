<?php

namespace Coachview\Sync\Models;
use Coachview\Presentation\Enums\CourseFormat;
use function Coachview\Sync\minutes_to_time_string;

class TrainingTypeComponent {

    public function __construct(
        public string       $id,
        public string       $code,
        public string       $name,
        public CourseFormat $course_format,         // e.g. 'Klassikaal', 'Elearning', 'Virtual classroom' -> CourseFormat
        public float        $study_hours,           // e.g. 7.5
        public ?string      $start_time,            // e.g. '09:30'
        public ?string      $end_time,              // e.g. '17:00'
        public ?int         $min_seats,             // e.g. 5
        public ?int         $max_seats,             // e.g. 12
        public ?int         $study_load,            // e.g. 22
        public ?bool        $attendance_required,
        public ?bool        $passing_required
    ) {
    }

    public static function from_array(array $data): self {
//        error_log("TrainingTypeComponent::from_array called with data: ");
//        error_log(print_r($data, true)); // Debugging line to log the data structure
        return new self(
            id: $data['id'],
            code: $data['code'],
            name: $data['naam'],
            course_format: CourseFormat::fromString($data['lesvormId'] ?? ''),
            study_hours: floatval($data['aantalUur'] ?? 0),
            start_time: minutes_to_time_string($data['tijdVan'] ?? 0),
            end_time: minutes_to_time_string($data['tijdTot'] ?? 0),
            min_seats: $data['minCursisten'],
            max_seats: $data['maxCursisten'],
            study_load: $data['studielast'] ?? null,
            attendance_required: boolval($data['aanwezigheidVerplicht'] ?? false),
            passing_required: boolval($data['slagenVerplicht'] ?? false));
    }
}