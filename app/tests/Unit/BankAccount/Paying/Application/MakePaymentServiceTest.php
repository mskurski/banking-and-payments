<?php

declare(strict_types=1);

namespace Unit\BankAccount\Paying\Application;

use App\BankAccount\Paying\Application\MakePaymentService;
use App\BankAccount\Paying\Domain\Payment\Account;
use App\BankAccount\Paying\Domain\Payment\AccountId;
use App\BankAccount\Paying\Domain\Payment\AccountRepository;
use App\BankAccount\Paying\Domain\Payment\Currency;
use App\BankAccount\Paying\Domain\Payment\Money;
use App\BankAccount\Paying\Domain\Payment\Payment;
use App\BankAccount\Paying\Domain\Payment\PaymentId;
use App\BankAccount\Paying\Domain\Payment\PaymentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Unit\BankAccount\Paying\Application\Fake\FakeAccountRepository;

final class MakePaymentServiceTest extends TestCase
{
    private PaymentService&MockObject $paymentServiceMock;
    private FakeAccountRepository $fakeAccountRepository;
    private MakePaymentService $makePaymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        $this->fakeAccountRepository = new FakeAccountRepository();

        $this->makePaymentService = new MakePaymentService(
            $this->fakeAccountRepository,
            $this->paymentServiceMock
        );
    }

    public function testPaymentSuccess(): void {
        // GIVEN
        $paymentId = PaymentId::generate()->toString();
        $paymentDate = new \DateTimeImmutable();
        $payer = self::createAccount(1200, Currency::USD);
        $receiver = self::createAccount(0, Currency::USD);
        $paymentMoney = new Money(1000, Currency::USD);

        $this->fakeAccountRepository->reset($payer, $receiver);

        $this->paymentServiceMock
            ->expects(self::once())
            ->method('makePayment')
            ->with(new Payment(
                id: PaymentId::fromString($paymentId),
                money: $paymentMoney,
                fromAccount: $payer,
                toAccount: $receiver,
                date: $paymentDate,
            ));

        // WHEN
        $this->makePaymentService->makePayment(
            $paymentId,
            $payer->id->toString(),
            $receiver->id->toString(),
            $paymentMoney->amount,
            $paymentMoney->currency->toString(),
            $paymentDate
        );
    }

    public function testPaymentErrorForNotExistingPayerAccount(): void
    {
        // GIVEN
        $paymentId = PaymentId::generate()->toString();
        $payer = self::createAccount(1200, Currency::USD);
        $receiver = self::createAccount(0, Currency::USD);
        $paymentMoney = new Money(1000, Currency::USD);

        $this->fakeAccountRepository->reset($receiver);

        // THEN
        self::expectException(\Exception::class);
        self::expectExceptionMessage(sprintf('Account with ID "%s" does not exist.', $payer->id->toString()));

        // WHEN
        $this->makePaymentService->makePayment(
            $paymentId,
            $payer->id->toString(),
            $receiver->id->toString(),
            $paymentMoney->amount,
            $paymentMoney->currency->toString(),
        );
    }

    public function testPaymentErrorForNotExistingReceiverAccount(): void
    {
        // GIVEN
        $paymentId = PaymentId::generate()->toString();
        $payer = self::createAccount(1200, Currency::USD);
        $receiver = self::createAccount(0, Currency::USD);
        $paymentMoney = new Money(1000, Currency::USD);

        $this->fakeAccountRepository->reset($payer);

        // THEN
        self::expectException(\Exception::class);
        self::expectExceptionMessage(sprintf('Account with ID "%s" does not exist.', $receiver->id->toString()));

        // WHEN
        $this->makePaymentService->makePayment(
            $paymentId,
            $payer->id->toString(),
            $receiver->id->toString(),
            $paymentMoney->amount,
            $paymentMoney->currency->toString(),
        );
    }

    public function testPaymentErrorForSamePayerAndReceiverAccount(): void
    {
        // GIVEN
        $paymentId = PaymentId::generate()->toString();
        $payer = $receiver = self::createAccount(1200, Currency::USD);
        $paymentMoney = new Money(1000, Currency::USD);

        $this->fakeAccountRepository->reset($payer, $receiver);

        // THEN
        self::expectException(\Exception::class);
        self::expectExceptionMessage('Payer and receiver shouldn\'t be the same account');

        // WHEN
        $this->makePaymentService->makePayment(
            $paymentId,
            $payer->id->toString(),
            $receiver->id->toString(),
            $paymentMoney->amount,
            $paymentMoney->currency->toString(),
        );
    }

    public static function provideDataForPaymentError(): iterable
    {
        $account1 = self::createAccount(1000, Currency::USD);
        $account2 = self::createAccount(0, Currency::USD);

        yield 'Use case for invalid payment amount' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(0, Currency::USD),
            'expectedExceptionClass' => \Exception::class,
            'expectedExceptionMessage' => 'Payment amount should be higher than 0',
        ];

        $account1 = self::createAccount(100, Currency::USD);
        $account2 = self::createAccount(0, Currency::USD);

        yield 'Use case for invalid payment currency' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(20, Currency::EUR),
            'expectedExceptionClass' => \Exception::class,
            'expectedExceptionMessage' => 'Payment is allowed only in same currencies',
        ];

        $account1 = self::createAccount(100, Currency::EUR);
        $account2 = self::createAccount(0, Currency::USD);

        yield 'Use case for invalid payer currency' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(20, Currency::USD),
            'expectedExceptionClass' => \Exception::class,
            'expectedExceptionMessage' => 'Payment is allowed only in same currencies',
        ];

        $account1 = self::createAccount(100, Currency::USD);
        $account2 = self::createAccount(0, Currency::EUR);

        yield 'Use case for invalid receiver currency' => [
            'payer' => $account1,
            'receiver' => $account2,
            'money' => new Money(20, Currency::USD),
            'expectedExceptionClass' => \Exception::class,
            'expectedExceptionMessage' => 'Payment is allowed only in same currencies',
        ];
    }

    /**
     * @dataProvider provideDataForPaymentError
     */
    public function testPaymentError(
        Account $payer,
        Account $receiver,
        Money $money,
        string $expectedExceptionClass,
        string $expectedExceptionMessage
    ): void {
        // GIVEN
        $this->fakeAccountRepository->reset($payer, $receiver);


        // THEN
        self::expectException($expectedExceptionClass);
        self::expectExceptionMessage($expectedExceptionMessage);

        // WHEN
        $this->makePaymentService->makePayment(
            PaymentId::generate()->toString(),
            $payer->id->toString(),
            $receiver->id->toString(),
            $money->amount,
            $money->currency->toString(),
        );
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
