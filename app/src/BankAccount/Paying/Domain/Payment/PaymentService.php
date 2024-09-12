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

        foreach ($this->paymentFeePolicies as $paymentFeePolicy) {
            $payment->applyPaymentFeePolicy($paymentFeePolicy);
        }

        try {
            $payment->execute();

            $this->accountRepository->savePayment($payment);

            // here we can additionally publish some domain events like PaymentMade
        } catch (\DomainException $e) {
            // here we can additionally publish some domain event like PaymentFailed
            throw new PaymentException($e->getMessage());
        }
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
