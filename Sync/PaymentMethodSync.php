<?php

namespace Coachview\Sync;

use Coachview\Constants;
use Coachview\Helpers\Logger;
use Coachview\Sync\Dataloaders\PaymentMethodDataloader;

class PaymentMethodSync
{
    public static function run(): void
    {
        Logger::info('Starting payment method synchronization.', 'sync');
        $payment_methods = PaymentMethodDataloader::load_payment_methods(100);
        update_option(Constants::OPTION_PAYMENT_METHODS, $payment_methods);
        Logger::info('Payment method synchronization completed.', 'sync');
    }
}