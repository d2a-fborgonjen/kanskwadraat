<?php

namespace Coachview\Sync;

use Coachview\Constants;
use Coachview\Sync\Dataloaders\PaymentMethodDataloader;

class PaymentMethodSync
{
    public static function run(): void
    {
        log_cv_info('Starting payment method synchronization.');
        $payment_methods = PaymentMethodDataloader::load_payment_methods(100);
        update_option(Constants::OPTION_PAYMENT_METHODS, $payment_methods);
        log_cv_info('Payment method synchronization completed.');
    }
}