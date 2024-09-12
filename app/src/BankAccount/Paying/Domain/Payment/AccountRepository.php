<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

interface AccountRepository
{
    public function findAccount(AccountId $accountId): ?Account;

    public function savePayment(Payment $payment): void;

    public function countPaymentsByDate(AccountId $accountId, \DateTimeImmutable $date): int;
}
