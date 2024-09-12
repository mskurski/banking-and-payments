<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment\PaymentFeePolicy;

use App\BankAccount\Paying\Domain\Payment\Money;

interface PaymentFeePolicy
{
    public function apply(Money $money): Money;
}
