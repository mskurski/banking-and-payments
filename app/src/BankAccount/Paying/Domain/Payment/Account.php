<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

final class Account
{
    public function __construct(
        public readonly AccountId $id,
        public readonly Currency $currency,
        public Money $balance
    ) {
        if (!$this->currency->isSameAs($this->balance->currency)) {
            throw new \DomainException('Balance currency should be same as account currency.');
        }
    }

    public function credit(Money $money): void
    {
        if (!$this->currency->isSameAs($money->currency)) {
            throw new \DomainException('Can not credit account with different currency than account currency.');
        }

        $this->balance = $this->balance->add($money);
    }

    public function debit(Money $money): void
    {
        if (!$this->currency->isSameAs($money->currency)) {
            throw new \DomainException('Can not debit account with different currency than account currency.');
        }

        if ($this->balance->lessThan($money)) {
            throw new \DomainException('Can not debit account with higher amount than account balance.');
        }

        $this->balance = $this->balance->subtract($money);
    }
}
