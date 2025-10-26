<?php

namespace Coachview\Sync\Dataloaders;

use Coachview\Api\ApiClient;
use Coachview\Api\QueryBuilder;
use Exception;
use function Coachview\Sync\log_cv_exception;

class CategoryDataloader
{
    public static function load_categories(int $take): array {
        $query = (new QueryBuilder())
            ->where('publicatieWebsite', 'true')
            ->where('inactief', 'false')
            ->includeFreeFields()
            ->includeExtraFields()
            ->includeDirectRelations()
            ->take($take)
            ->build();
        try {
            $rawData = collect(ApiClient::categories()->get($query));

            error_log("Loaded " . $rawData->count() . " categories");
            return $rawData->groupBy('opleidingssoortcategoriegroep.naam')
                ->map(fn($items) => $items->pluck('naam')->all())
                ->toArray();

        } catch (Exception $e) {
            log_cv_exception('Load[Categories]', $e);
            return [];
        }
    }
}