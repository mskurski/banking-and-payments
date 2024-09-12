<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Infrastructure\Payment;

use App\BankAccount\Paying\Domain\Payment\Account;
use App\BankAccount\Paying\Domain\Payment\AccountId;
use App\BankAccount\Paying\Domain\Payment\AccountRepository;
use App\BankAccount\Paying\Domain\Payment\Currency;
use App\BankAccount\Paying\Domain\Payment\Money;
use App\BankAccount\Paying\Domain\Payment\PaymentId;
use App\BankAccount\Paying\Infrastructure\Payment\Model\AccountTransaction;
use App\BankAccount\Paying\Infrastructure\Payment\Model\AccountTransactionType;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccountRepository implements AccountRepository
{
    private array $accountTransactions = [];

    public function findAccount(AccountId $accountId): ?Account
    {
        // read account details from storage (use projection for account balance or calculate it from transactions)
        $accountCurrency = Currency::USD;

        $account = new Account(
            $accountId,
            $accountCurrency,
            new Money(0, $accountCurrency)
        );

        // here is example of rebuilding account balance from transactions
        $accountTransactions = $this->findTransactions($accountId);
        foreach ($accountTransactions as $accountTransaction) {
            switch ($accountTransaction->type) {
                case AccountTransactionType::DEBIT:
                    $account->debit(
                        new Money($accountTransaction->amount, Currency::from($accountTransaction->currency))
                    );
                    break;
                case AccountTransactionType::CREDIT:
                    $account->credit(
                        new Money($accountTransaction->amount, Currency::from($accountTransaction->currency))
                    );
                    break;
                default:
                    throw new \RuntimeException(
                        sprintf('Not supported transaction type: %s', $accountTransaction->type->value)
                    );
            }
        }
    }

    public function savePayment(
        PaymentId $paymentId,
        AccountId $debitAccountId,
        Money $debitMoney,
        AccountId $creditAccountId,
        Money $creditMoney,
        \DateTimeImmutable $date
    ): void {
        if (!isset($this->accountTransactions[$creditAccountId->toString()])) {
            $this->accountTransactions[$creditAccountId->toString()] = [];
        }

        $this->accountTransactions[$debitAccountId->toString()][] = new AccountTransaction(
            id: Uuid::v4()->toRfc4122(),
            type: AccountTransactionType::DEBIT,
            accountId: $debitAccountId->toString(),
            amount: $debitMoney->amount,
            currency: $debitMoney->currency->name,
            date: $date,
            relatedPaymentId: $paymentId->toString(),
            relatedAccountId: $creditAccountId->toString(),
        );

        $this->accountTransactions[$creditAccountId->toString()][] = new AccountTransaction(
            id: Uuid::v4()->toRfc4122(),
            type: AccountTransactionType::CREDIT,
            accountId: $creditAccountId->toString(),
            amount: $creditMoney->amount,
            currency: $creditMoney->currency->name,
            date: $date,
            relatedPaymentId: $paymentId->toString(),
            relatedAccountId: $debitAccountId->toString(),
        );

        // here we can publish some EventSourcing events so we can make some projections with account balance
    }

    public function countPaymentsByDate(AccountId $accountId, \DateTimeImmutable $date): int
    {
        $allTransactions = $this->findTransactionsByDate($accountId, $date);
        $debitTransactions = array_filter(
            $allTransactions,
            function (AccountTransaction $transaction) {
                return $transaction->type === AccountTransactionType::DEBIT;
            }
        );

        return count($debitTransactions);
    }

    /**
     * @return AccountTransaction[]
     */
    private function findTransactions(AccountId $accountId): array
    {
        if (!array_key_exists($accountId->toString(), $this->accountTransactions)) {
            return [];
        }

        return $this->accountTransactions[$accountId->toString()];
    }

    /**
     * @return AccountTransaction[]
     */
    private function findTransactionsByDate(AccountId $accountId, \DateTimeImmutable $date): array
    {
        $accountTransactions = $this->findTransactions($accountId);

        return array_filter(
            $accountTransactions,
            function (AccountTransaction $transaction) use ($date) {
                return $transaction->date->format('Y-m-d') === $date->format('Y-m-d');
            }
        );
    }
}