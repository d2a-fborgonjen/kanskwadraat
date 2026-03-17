<?php

namespace Coachview\Helpers;

use Coachview\Constants;
use Coachview\MetaHelpers;
use WC_Product;

class Payment
{
    public static function getPaymentMethods(): array
    {
        return get_option(Constants::OPTION_PAYMENT_METHODS, []);
    }

    public static function getDefaultPaymentMethodIds(): array
    {
        $saved_method_ids = get_option(Constants::OPTION_DEFAULT_PAYMENT_METHOD_IDS, []);
        if (is_string($saved_method_ids)) {
            $saved_method_ids = json_decode($saved_method_ids, true) ?? [];
        }
        return $saved_method_ids;
    }

    public static function getProductPaymentMethods(WC_Product $product): array
    {
        $all_methods        = self::getPaymentMethods();
        $default_method_ids = self::getDefaultPaymentMethodIds();
        $product_method_ids = MetaHelpers::payment_methods($product->get_id());

        if (is_array($product_method_ids)) {
            return array_filter($all_methods, static fn($method) => in_array($method['id'], $product_method_ids, true));
        }

        if (!empty($default_method_ids)) {
            return array_filter($all_methods, static fn($method) => in_array($method['id'], $default_method_ids, true));
        }

        return $all_methods;
    }
}

