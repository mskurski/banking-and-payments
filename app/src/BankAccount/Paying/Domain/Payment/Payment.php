<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

use App\BankAccount\Paying\Domain\Payment\Exception\PaymentException;

final readonly class Payment
{
    public function __construct(
        public PaymentId $id,
        public Money $money,
        public Account $fromAccount,
        public Account $toAccount,
        public \DateTimeImmutable $date,
    ) {
        if (
            !$this->money->inSameCurrencyAs($this->fromAccount->balance)
            || !$this->money->inSameCurrencyAs($this->toAccount->balance)
        ) {
            throw new PaymentException('Payment is allowed only in same currencies');
        }

        if ($this->money->amount === 0) {
            throw new PaymentException('Payment amount should be higher than 0');
        }
    }

    public function payerAccountBalance(): Money
    {
        return $this->fromAccount->balance;
    }
}
