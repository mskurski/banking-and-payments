<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment\PaymentFeePolicy;

use App\BankAccount\Paying\Domain\Payment\Money;

final class PercentagePaymentFeePolicy implements PaymentFeePolicy
{
    private const float PERCENTAGE_FEE = 0.5;

    public function apply(Money $money): Money
    {
        return $money->add(
            new Money((int)round($money->amount * self::PERCENTAGE_FEE / 100), $money->currency)
        );
    }
}
