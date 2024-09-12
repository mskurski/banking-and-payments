<?php

declare(strict_types=1);

namespace App\BankAccount\Paying\Domain\Payment;

use Symfony\Component\Uid\Uuid;

final readonly class AccountId
{
    private function __construct(
        public string $id,
    ) {
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }

    public static function fromString(string $id): self
    {
        if (Uuid::isValid($id) === false) {
            throw new \DomainException(
                sprintf('Account id "%s" is not valid.', $id)
            );
        }

        return new self($id);
    }

    public function toString(): string
    {
        return $this->id;
    }
}
