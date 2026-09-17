<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\Exceptions\CurrencyMismatchException;

/**
 * PRD M4: BIGINT minor-unit money, no float at any point in the calculation
 * path. NOT App\Support\Localization\Money (that one just formats an
 * already-computed number for display — see its docblock).
 */
final class Money
{
    private function __construct(
        public readonly int $amountMinor,
        public readonly string $currency,
    ) {}

    public static function of(int $amountMinor, string $currency): self
    {
        return new self($amountMinor, strtoupper($currency));
    }

    public static function zero(string $currency): self
    {
        return self::of(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::of($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::of($this->amountMinor - $other->amountMinor, $this->currency);
    }

    /**
     * Applies a percentage expressed as basis points (1% = 100 bp) using
     * pure integer division — never a float multiplication. Basis points
     * themselves come from Percent::toBasisPoints(), the single documented
     * point where a stored decimal is converted to an int.
     */
    public function multiplyByBasisPoints(int $basisPoints): self
    {
        return self::of(intdiv($this->amountMinor * $basisPoints, 10000), $this->currency);
    }

    public function isSameCurrency(self $other): bool
    {
        return $this->currency === $other->currency;
    }

    public function equals(self $other): bool
    {
        return $this->amountMinor === $other->amountMinor && $this->isSameCurrency($other);
    }

    public function isNegative(): bool
    {
        return $this->amountMinor < 0;
    }

    private function assertSameCurrency(self $other): void
    {
        if (! $this->isSameCurrency($other)) {
            throw new CurrencyMismatchException("Cannot combine {$this->currency} with {$other->currency}.");
        }
    }
}
