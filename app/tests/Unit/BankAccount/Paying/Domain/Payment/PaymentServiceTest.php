<?php

declare(strict_types=1);

namespace Unit\BankAccount\Paying\Domain\Payment;

use App\BankAccount\Paying\Domain\Payment\Account;
use App\BankAccount\Paying\Domain\Payment\AccountId;
use App\BankAccount\Paying\Domain\Payment\AccountRepository;
use App\BankAccount\Paying\Domain\Payment\Currency;
use App\BankAccount\Paying\Domain\Payment\Exception\PaymentException;
use App\BankAccount\Paying\Domain\Payment\Money;
use App\BankAccount\Paying\Domain\Payment\Payment;
use App\BankAccount\Paying\Domain\Payment\PaymentFeePolicy\PaymentFeePolicy;
use App\BankAccount\Paying\Domain\Payment\PaymentFeePolicy\PercentagePaymentFeePolicy;
use App\BankAccount\Paying\Domain\Payment\PaymentId;
use App\BankAccount\Paying\Domain\Payment\PaymentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentServiceTest extends TestCase
{
    private AccountRepository&MockObject $accountRepositoryMock;
    /** @var PaymentFeePolicy[] */
    private array $paymentFeePolicies;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepositoryMock = $this->createMock(AccountRepository::class);
        $this->paymentFeePolicies = [
            new PercentagePaymentFeePolicy(),
        ];
        $this->paymentService = new PaymentService(
            $this->accountRepositoryMock,
            ...$this->paymentFeePolicies
        );
    }

    public static function provideDataForPaymentSuccess(): iterable
    {
        $account1 = self::createAccount(200, Currency::USD);
        $account2 = self::createAccount(100, Currency::USD);

        yield 'Use case for single 1$ payment' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(100, Currency::USD),
            'paymentsPerDay' => 0,
            'expectedPayerBalance' => new Money(99, Currency::USD),
            'expectedReceiverBalance' => new Money(200, Currency::USD),
        ];

        $account1 = self::createAccount(503, Currency::USD);
        $account2 = self::createAccount(0, Currency::USD);

        yield 'Use case for clearing balance' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(500, Currency::USD),
            'paymentsPerDay' => 0,
            'expectedPayerBalance' => new Money(0, Currency::USD),
            'expectedReceiverBalance' => new Money(500, Currency::USD),
        ];

        $account1 = self::createAccount(503, Currency::USD);
        $account2 = self::createAccount(0, Currency::USD);

        yield 'Use case for last payment per day' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(500, Currency::USD),
            'paymentsPerDay' => 2,
            'expectedPayerBalance' => new Money(0, Currency::USD),
            'expectedReceiverBalance' => new Money(500, Currency::USD),
        ];
    }

    /**
     * @dataProvider provideDataForPaymentSuccess
     */
    public function testPaymentSuccess(
        Account $payer,
        Account $receiver,
        Money $money,
        int $paymentsPerDay,
        Money $expectedPayerBalance,
        Money $expectedReceiverBalance
    ): void {
        $payment = new Payment(
            id: PaymentId::generate(),
            money: $money,
            fromAccount: $payer,
            toAccount: $receiver,
            date: new \DateTimeImmutable()
        );

        $toDebit = $money;
        foreach ($this->paymentFeePolicies as $paymentFeePolicy) {
            $toDebit = $paymentFeePolicy->apply($money);
        }

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('savePayment')
            ->with(
                paymentId: $payment->id,
                debitAccountId: $payment->fromAccount->id,
                debitMoney: $toDebit,
                creditAccountId: $payment->toAccount->id,
                creditMoney: $payment->money,
                date: $payment->date,
            );

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('countPaymentsByDate')
            ->with($payment->fromAccount->id, $payment->date)
            ->willReturn($paymentsPerDay);

        // WHEN
        $this->paymentService->makePayment($payment);

        // THEN
        self::assertEquals($expectedPayerBalance, $payer->balance);
        self::assertEquals($expectedReceiverBalance, $receiver->balance);
    }

    public static function provideDataForPaymentError(): iterable
    {
        $account1 = self::createAccount(500, Currency::USD);
        $account2 = self::createAccount(100, Currency::USD);

        yield 'Use case for exceeding payments limit' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(100, Currency::USD),
            'paymentsPerDay' => 3,
            'expectedErrorClass' => PaymentException::class,
            'expectedErrorMessage' => 'Max daily payments limit of 3 payments already reached.',
        ];

        $account1 = self::createAccount(502, Currency::USD);
        $account2 = self::createAccount(0, Currency::USD);

        yield 'Use case for not enough balance' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(500, Currency::USD),
            'paymentsPerDay' => 0,
            'expectedErrorClass' => PaymentException::class,
            'expectedErrorMessage' => 'Not enough balance to debit account.',
        ];
    }

    /**
     * @dataProvider provideDataForPaymentError
     */
    public function testPaymentError(
        Account $payer,
        Account $receiver,
        Money $money,
        int $paymentsPerDay,
        string $expectedErrorClass,
        string $expectedErrorMessage
    ): void {
        // GIVEN
        $expectedPayerBalance = $payer->balance;
        $expectedReceiverBalance = $receiver->balance;
        $payment = new Payment(
            id: PaymentId::generate(),
            money: $money,
            fromAccount: $payer,
            toAccount: $receiver,
            date: new \DateTimeImmutable()
        );

        $this->accountRepositoryMock
            ->expects($this->never())
            ->method('savePayment');

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('countPaymentsByDate')
            ->with($payment->fromAccount->id, $payment->date)
            ->willReturn($paymentsPerDay);

        // THEN
        $this->expectException($expectedErrorClass);
        $this->expectExceptionMessage($expectedErrorMessage);

        // WHEN
        $this->paymentService->makePayment($payment);

        // THEN
        self::assertEquals($expectedPayerBalance, $payer->balance);
        self::assertEquals($expectedReceiverBalance, $receiver->balance);
    }

    private static function createAccount(int $amount, Currency $currency): Account
    {
        return new Account(
            AccountId::generate(),
            $currency,
            new Money($amount, $currency),
        );
    }
}