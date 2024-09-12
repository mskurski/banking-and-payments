<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Application;

use App\BankAccount\Paying\Domain\Payment\AccountId;
use App\BankAccount\Paying\Domain\Payment\AccountRepository;
use App\BankAccount\Paying\Domain\Payment\Currency;
use App\BankAccount\Paying\Domain\Payment\Money;
use App\BankAccount\Paying\Domain\Payment\Payment;
use App\BankAccount\Paying\Domain\Payment\PaymentId;
use App\BankAccount\Paying\Domain\Payment\PaymentService;

final readonly class MakePaymentService
{
    public function __construct(
        private AccountRepository $accountRepository,
        private PaymentService $paymentService,
    ) {
    }

    public function makePayment(
        string $paymentId,
        string $fromAccountId,
        string $toAccountId,
        int $moneyAmount,
        string $moneyCurrency,
        \DateTimeImmutable $paymentDate = new \DateTimeImmutable(),
    ): void {
        $fromAccount = $this->accountRepository->findAccount(AccountId::fromString($fromAccountId));
        if ($fromAccount === null) {
            throw new \Exception(sprintf('Account with ID "%s" does not exist.', $fromAccountId));
        }

        $toAccount = $this->accountRepository->findAccount(AccountId::fromString($toAccountId));
        if ($toAccount === null) {
            throw new \Exception(sprintf('Account with ID "%s" does not exist.', $toAccountId));
        }

        if ($fromAccount->id->toString() === $toAccount->id->toString()) {
            throw new \Exception('Payer and receiver shouldn\'t be the same account');
        }

        try {
            $payment = new Payment(
                id: PaymentId::fromString($paymentId),
                money: new Money($moneyAmount, Currency::from($moneyCurrency)),
                fromAccount: $fromAccount,
                toAccount: $toAccount,
                date: $paymentDate,
            );

            $this->paymentService->makePayment($payment);

            // here we can publish some integration events
        } catch (\Exception $exception) {
            // here we can publish some integration events
            // log exception

            throw new \Exception($exception->getMessage());
        }
    }
}