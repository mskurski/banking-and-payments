<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Infrastructure\Payment\Model;

final readonly class AccountTransaction
{
    public function __construct(
        public string $id,
        public AccountTransactionType $type,
        public string $accountId,
        public int $amount,
        public string $currency,
        public \DateTimeImmutable $date,
        public string $relatedPaymentId,
        public string $relatedAccountId,
    ) {
    }
}