<?php
namespace Coachview\Sync\Models;

use Illuminate\Support\Collection;
use Coachview\Presentation\Enums\CourseFormat;

class TrainingType {
    public function __construct(
        public string     $id,
        public string     $code,
        public string     $name,
        public string     $goal,
        public string     $description,
        public int        $price,
        public int        $num_components,
        public string     $num_half_days,
        public array      $categories,
        public Collection $trainings, /** Collection of Trainings */
        public Collection $training_type_components /** Collection of TrainingTypeComponents */
    ) {
         error_log("TrainingType::__construct called with id: {$this->id}, code: {$this->code}, name: {$this->name}");
         error_log("Locations: " . implode(', ', $this->get_locations()));
         error_log("Categories: " . implode(', ', $this->categories));
         error_log("Course Format: " . $this->get_course_format()->value);
    }

    public static function from_array(array $data,
                                      array $categories,
                                      Collection $trainings,
                                      Collection $training_type_components): self {

        return new self(
            id: $data['id'],
            code: $data['code'],
            name: $data['naam'] ?? $data['code'],
            goal: $data['doel'] ?? '',
            description: $data['omschrijvingInhoud'] ?? '',
            price: $data['totaalBedragExclBtwGepubliceerdeVerkoopregels'] ?? $data['vanafPrijs'] ?? 0,
            num_components: $data['aantalOnderdelen'] ?? 0,
            num_half_days: $data['vrijvelddagdelen'] ?? '0',
            categories: $categories,
            trainings: $trainings,
            training_type_components: $training_type_components
        );
    }

    public function get_locations(): array {
        return $this->trainings->pluck('locations')->flatten()->unique()->toArray();
    }

    public function get_course_format(): CourseFormat {
        $course_formats = $this->training_type_components->pluck('course_format')->unique()->toArray();
        if (count($course_formats) === 1) {
            return $course_formats[0];
        } else if (count($course_formats) > 1){
            return CourseFormat::BLENDED;
        } else {
            return CourseFormat::E_LEARNING;
        }
    }

}