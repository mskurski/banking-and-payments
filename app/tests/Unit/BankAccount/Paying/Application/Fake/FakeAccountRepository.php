<?php

declare(strict_types=1);

namespace Tests\Unit\BankAccount\Paying\Application\Fake;

use App\BankAccount\Paying\Domain\Payment\Account;
use App\BankAccount\Paying\Domain\Payment\AccountId;
use App\BankAccount\Paying\Domain\Payment\AccountRepository;
use App\BankAccount\Paying\Domain\Payment\Payment;

final class FakeAccountRepository implements AccountRepository
{
    /**
     * @var Account[]
     */
    private array $accounts = [];

    public function reset(Account ...$accounts): void
    {
        foreach ($accounts as $account) {
            $this->accounts[$account->id->toString()] = $account;
        }
    }

    public function findAccount(AccountId $accountId): ?Account
    {
        return $this->accounts[$accountId->toString()] ?? null;
    }

    public function savePayment(Payment $payment): void
    {
        // to be implemented
    }

    public function countPaymentsByDate(AccountId $accountId, \DateTimeImmutable $date): int
    {
        // to be implemented
        return 0;
    }
}