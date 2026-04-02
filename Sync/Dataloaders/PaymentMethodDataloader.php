<?php

namespace Coachview\Sync\Dataloaders;

use Coachview\Api\ApiClient;
use Coachview\Api\QueryBuilder;
use Coachview\Helpers\Logger;
use Exception;

class PaymentMethodDataloader
{
    public static function load_payment_methods(int $take): array {
        $query = (new QueryBuilder())
            ->take($take)
            ->build();
        try {
            $rawData = collect(ApiClient::payment_methods()->get($query));

            return $rawData
                ->map(fn($item) => [
                    'id'   => $item['id'],
                    'code' => $item['code'],
                    'name' => $item['naam'],
                ])
                ->toArray();

        } catch (Exception $e) {
            Logger::error('Load[PaymentMethods]: ' . $e->getMessage(), 'sync', [
                'exception' => get_class($e),
                'trace'     => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}