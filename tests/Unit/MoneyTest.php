<?php

namespace Tests\Unit;

use App\Domain\Pricing\Exceptions\CurrencyMismatchException;
use App\Domain\Pricing\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_add_keeps_precision(): void
    {
        $a = Money::of(1_000_000, 'IDR');
        $b = Money::of(250_000, 'IDR');

        $this->assertSame(1_250_000, $a->add($b)->amountMinor);
    }

    public function test_subtract_keeps_precision(): void
    {
        $a = Money::of(1_000_000, 'IDR');
        $b = Money::of(250_000, 'IDR');

        $this->assertSame(750_000, $a->subtract($b)->amountMinor);
    }

    public function test_different_currencies_cannot_be_added(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::of(100, 'IDR')->add(Money::of(100, 'SAR'));
    }

    public function test_different_currencies_cannot_be_subtracted(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::of(100, 'IDR')->subtract(Money::of(100, 'SAR'));
    }

    public function test_multiply_by_basis_points_never_uses_float(): void
    {
        // 25% of 84,250,000 = 21,062,500 — exact, no float involved.
        $cost = Money::of(84_250_000, 'IDR');

        $this->assertSame(21_062_500, $cost->multiplyByBasisPoints(2500)->amountMinor);
    }

    public function test_multiply_by_basis_points_truncates_consistently(): void
    {
        // 1% of 99 minor units = 0.99 -> truncated to 0, not rounded up.
        $cost = Money::of(99, 'IDR');

        $this->assertSame(0, $cost->multiplyByBasisPoints(100)->amountMinor);
    }

    public function test_zero_is_zero_in_given_currency(): void
    {
        $zero = Money::zero('SAR');

        $this->assertSame(0, $zero->amountMinor);
        $this->assertSame('SAR', $zero->currency);
    }

    public function test_currency_code_is_normalised_uppercase(): void
    {
        $this->assertSame('IDR', Money::of(1, 'idr')->currency);
    }

    public function test_equals_compares_amount_and_currency(): void
    {
        $this->assertTrue(Money::of(500, 'IDR')->equals(Money::of(500, 'IDR')));
        $this->assertFalse(Money::of(500, 'IDR')->equals(Money::of(500, 'SAR')));
        $this->assertFalse(Money::of(500, 'IDR')->equals(Money::of(501, 'IDR')));
    }

    public function test_is_negative(): void
    {
        $this->assertTrue(Money::of(-1, 'IDR')->isNegative());
        $this->assertFalse(Money::of(0, 'IDR')->isNegative());
    }
}
