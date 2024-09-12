<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

use App\BankAccount\Paying\Domain\Payment\Exception\PaymentException;
use App\BankAccount\Paying\Domain\Payment\PaymentFeePolicy\PaymentFeePolicy;

class PaymentService
{
    private const int MAX_ACCOUNT_DAILY_PAYMENTS = 3;

    /**
     * @var PaymentFeePolicy[]
     */
    private array $paymentFeePolicies;

    public function __construct(
        private AccountRepository $accountRepository,
        PaymentFeePolicy ...$paymentFeePolicies
    ) {
        $this->paymentFeePolicies = $paymentFeePolicies;
    }

    /**
     * @throws PaymentException
     */
    public function makePayment(Payment $payment): void
    {
        $this->validateDailyPaymentsLimit($payment);

        $toDebit = $this->calculatePaymentWithFees($payment->money);

        // validate enough balance
        if ($payment->payerAccountBalance()->lessThen($toDebit)) {
            throw new PaymentException('Not enough balance to debit account.');
        }

        try {
            $payment->fromAccount->debit($toDebit);
            $payment->toAccount->credit($payment->money);
        } catch (\DomainException $e) {
            throw new PaymentException($e->getMessage());
        }

        $this->accountRepository->savePayment(
            paymentId: $payment->id,
            debitAccountId: $payment->fromAccount->id,
            debitMoney: $toDebit,
            creditAccountId: $payment->toAccount->id,
            creditMoney: $payment->money,
            date: $payment->date,
        );

        // here we can additionally publish some domain events
    }

    private function calculatePaymentWithFees(Money $money): Money
    {
        $paymentWithFees = $money;
        foreach ($this->paymentFeePolicies as $paymentFeePolicy) {
            $paymentWithFees = $paymentFeePolicy->apply($paymentWithFees);
        }

        return $paymentWithFees;
    }

    /**
     * @throws PaymentException
     */
    private function validateDailyPaymentsLimit(Payment $payment): void
    {
        $dailyTransactionsCount = $this->accountRepository->countPaymentsByDate($payment->fromAccount->id, $payment->date);
        if ($dailyTransactionsCount === self::MAX_ACCOUNT_DAILY_PAYMENTS) {
            throw new PaymentException(
                sprintf(
                    'Max daily payments limit of %s payments already reached.',
                    self::MAX_ACCOUNT_DAILY_PAYMENTS
                )
            );
        }
    }
}
