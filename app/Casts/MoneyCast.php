<?php

namespace App\Casts;

use App\Domain\Pricing\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Exposes a `{amount_minor}` column paired with a currency column as a
 * Money value object. Usage: `protected function casts(): array { return
 * ['cost_minor' => MoneyCast::class.':currency']; }`
 *
 * @implements CastsAttributes<Money, Money>
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(private readonly string $currencyColumn = 'currency') {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::of((int) $value, $attributes[$this->currencyColumn] ?? 'IDR');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value instanceof Money) {
            return [
                $key => $value->amountMinor,
                $this->currencyColumn => $value->currency,
            ];
        }

        if ($value !== null && ! is_int($value) && ! (is_string($value) && preg_match('/^-?\d+$/', $value))) {
            throw new InvalidArgumentException('Money column only accepts int minor units or Money, got '.get_debug_type($value));
        }

        return [$key => $value];
    }
}
