<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';

    public function isSameAs(Currency $currency): bool
    {
        return $this->value === $currency->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
