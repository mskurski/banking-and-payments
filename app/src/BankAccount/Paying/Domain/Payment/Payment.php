<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

use App\BankAccount\Paying\Domain\Payment\Exception\PaymentException;
use App\BankAccount\Paying\Domain\Payment\PaymentFeePolicy\PaymentFeePolicy;

final class Payment
{
    private Money $debitMoney;
    private Money $creditMoney;

    public function __construct(
        public readonly PaymentId $id,
        public readonly Money $money,
        public readonly Account $fromAccount,
        public readonly Account $toAccount,
        public readonly \DateTimeImmutable $date,
    ) {
        if (
            !$this->money->inSameCurrencyAs($this->payerAccountBalance())
            || !$this->money->inSameCurrencyAs($this->receiverAccountBalance())
        ) {
            throw new PaymentException('Payment is allowed only in same currencies.');
        }

        if ($this->money->amount === 0) {
            throw new PaymentException('Payment amount should be higher than 0.');
        }

        if ($this->payerAccountBalance()->lessThan($this->money)) {
            throw new PaymentException('Not enough balance to debit account.');
        }

        $this->debitMoney = $this->money;
        $this->creditMoney = $this->money;
    }

    public function payerAccountBalance(): Money
    {
        return $this->fromAccount->balance;
    }

    public function receiverAccountBalance(): Money
    {
        return $this->toAccount->balance;
    }

    public function debitMoney(): Money
    {
        return $this->debitMoney;
    }

    public function creditMoney(): Money
    {
        return $this->creditMoney;
    }

    public function applyPaymentFeePolicy(PaymentFeePolicy $paymentFeePolicy): void
    {
        $this->debitMoney = $this->debitMoney->add($paymentFeePolicy->apply($this->money));
        if ($this->payerAccountBalance()->lessThan($this->debitMoney)) {
            throw new PaymentException('Not enough balance to debit account.');
        }
    }

    public function execute(): void
    {
        $this->fromAccount->debit($this->debitMoney);
        $this->toAccount->credit($this->creditMoney);
    }
}
