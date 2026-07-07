<?php

namespace App\Modules\Nachhilfe\Domain\ValueObjects;

use InvalidArgumentException;

class Money
{
    private function __construct(
        private readonly float $amount,
        private readonly string $currency = 'EUR'
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException("Money amount cannot be negative.");
        }
    }

    public static function of(float $amount, string $currency = 'EUR'): self
    {
        return new self($amount, $currency);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
