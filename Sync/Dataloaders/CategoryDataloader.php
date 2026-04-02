<?php

namespace Coachview\Sync\Dataloaders;

use Coachview\Api\ApiClient;
use Coachview\Api\QueryBuilder;
use Coachview\Helpers\Logger;
use Exception;

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

            return $rawData->groupBy('opleidingssoortcategoriegroep.naam')
                ->map(fn($items) => $items->pluck('naam')->all())
                ->toArray();

        } catch (Exception $e) {
            Logger::error('Load[Categories]: ' . $e->getMessage(), 'sync', [
                'exception' => get_class($e),
                'trace'     => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}