<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Infrastructure\Payment\Model;

enum AccountTransactionType: string
{
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
}