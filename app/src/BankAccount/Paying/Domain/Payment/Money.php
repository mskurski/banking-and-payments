<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

use DomainException;

/**
 * Amount stored in lowest nominal in given currency so 1$ will be 100 (cents)
 */
final readonly class Money
{
    public function __construct(public int $amount, public Currency $currency)
    {
        if ($this->amount < 0) {
            throw new DomainException('Amount must be greater than 0');
        }
    }

    public function add(Money $money): Money
    {
        $this->validateCurrency($money);

        return new Money($this->amount + $money->amount, $this->currency);
    }

    public function subtract(Money $money): Money
    {
        $this->validateCurrency($money);

        if ($this->lessThen($money)) {
            throw new DomainException('Not enough money to subtract.');
        }

        return new Money($this->amount - $money->amount, $this->currency);
    }

    public function inSameCurrencyAs(Money $money): bool
    {
        return $this->currency->isSameAs($money->currency);
    }

    public function lessThen(Money $money): bool
    {
        if (!$this->inSameCurrencyAs($money)) {
            throw new DomainException('Not able to compare amounts in different currencies.');
        }

        return $this->amount < $money->amount;
    }

    /**
     * @throws DomainException
     */
    private function validateCurrency(Money $money): void
    {
        if (!$this->inSameCurrencyAs($money)) {
            throw new DomainException('Adding/subtracting money allowed only on same currency.');
        }
    }
}