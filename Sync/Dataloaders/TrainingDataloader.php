<?php

namespace Coachview\Sync\Dataloaders;

use Coachview\Api\ApiClient;
use Coachview\Api\QueryBuilder;
use Coachview\Sync\Models\Training;
use Coachview\Sync\Models\TrainingComponent;
use Coachview\Sync\Models\TrainingTypeComponent;
use Illuminate\Support\Collection;
use Coachview\Sync\Models\TrainingType;
use Exception;

class TrainingDataloader
{
    public static function load_training_types(int $take, $progress = null): Collection {
        $query = (new QueryBuilder())
            ->where('publicatieWebsite', 'true')
            ->where('inactief', 'false')
            ->includeFreeFields()
            ->includeExtraFields()
            ->includeDirectRelations()
            ->take($take)
            ->build();
        try {
            $rawData = collect(ApiClient::training_types()->get($query));
            $total = $rawData->count();
            $result = [];

            foreach ($rawData as $index => $data) {
                try {
                    $trainings = self::__load_trainings($data['id']);
                    $components = self::__load_training_type_components($data['id']);
                    $categories = self::__load_training_type_categories($data['id']);
                    $result[] = TrainingType::from_array($data, $categories, $trainings, $components);
                } catch (Exception $e) {
                    error_log("Error loading training types: " . $e->getMessage());
                }
                if ($progress) {
                    $progress($index + 1, $total);
                }
            }
            return collect($result);
        } catch (Exception $e) {
            error_log("Error loading training types: " . $e->getMessage());
            return collect();
        }
    }

    public static function __load_training_type_components(string $training_type_id): Collection {
        $query = (new QueryBuilder())
            ->where('OpleidingssoortId', $training_type_id)
            ->includeFreeFields()
            ->includeExtraFields()
            ->build();
        return collect(ApiClient::training_type_components()->get($query))->map(function($data) {
            return TrainingTypeComponent::from_array($data);
        });
    }


    public static function __load_training_type_categories(string $training_type_id): array {
        $query = (new QueryBuilder())
            ->where('OpleidingssoortId', $training_type_id)
            ->where('publicatieWebsite', 'true')
            ->includeExtraFields()
            ->build();
        return collect(ApiClient::training_type_categories()->get($query))->map(function($data) {
            return $data['naam'];
        })->unique()->toArray();
    }

    public static function __load_trainings(string $training_type_id): Collection {
        $query = (new QueryBuilder())
            ->where('opleidingssoortId', $training_type_id)
            // TODO: Uncomment this line on production
//            ->where('publicatie', 'true')
            ->where('opleidingStatusId', 'TeStarten,Definitief')
            ->where('startDatum', date('d-m-Y'), '>=')
            ->take(100)
            ->includeDirectRelations()
            ->includeExtraFields()
            ->build();
        $trainings = collect(ApiClient::trainings()->get($query));
        return $trainings
            ->map(function($data) {
                $training_components = self::__load_training_components($data['id']);
                $training = Training::from_array($data, $training_components);
                return $training;
            });
    }

    private static function __load_training_components(string $training_id) : Collection
    {
        $query = (new QueryBuilder())
            ->where('OpleidingId', $training_id)
//            ->where('publicatie', 'true')
            ->order_by('datumTijdVan')
            ->includeFreeFields()
            ->includeDirectRelations()
            ->build();
        return collect(ApiClient::training_components()->get($query))->map(function($data) {
            return TrainingComponent::from_array($data);
        });
    }


//    private static function load_related_sales_rules($training_type_id): ?array {
//        $query = (new QueryBuilder())
//            ->where('OpleidingssoortId', $training_type_id)
//            ->where('inactief', 'false')
//            ->build();
//        $sales_rules = ApiClient::sales_rules()->get($query);
//        return empty($sales_rules) ? [] : $sales_rules[0];
//    }
}