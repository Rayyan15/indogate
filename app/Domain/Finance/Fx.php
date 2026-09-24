<?php

namespace App\Domain\Finance;

use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Domain\Pricing\Money;
use App\Enums\PaymentChannel;

/**
 * The one place finance code converts between a currency's minor units and
 * IDR. Rate = IDR (major) per 1 major unit of the currency, same convention
 * as App\Domain\Pricing\Converter. Previously copy-pasted (with drift) in
 * PaymentService, ManualTransferProvider, MarginReportService and the UI.
 */
final class Fx
{
    /** Memo lifetime; bounds staleness in long-lived workers (queue, Octane). */
    private const TTL = 60;

    /** @var array<string, array{0: int, 1: mixed}> key => [expiresAt, value] */
    private static array $memo = [];

    /** Call after currency/rate writes and between tests. */
    public static function flush(): void
    {
        self::$memo = [];
    }

    private static function remember(string $key, \Closure $resolve): mixed
    {
        $hit = self::$memo[$key] ?? null;
        if ($hit && $hit[0] >= time()) {
            return $hit[1];
        }

        $value = $resolve();
        self::$memo[$key] = [time() + self::TTL, $value];

        return $value;
    }

    public static function decimals(string $currency): int
    {
        $currency = strtoupper($currency);

        return self::remember("dec:{$currency}", fn () => (int) (Currency::where('code', $currency)->value('decimal_places') ?? ($currency === 'IDR' ? 0 : 2)));
    }

    /**
     * A missing rate throws: silently using 1.0 made a USD payment worth
     * ~16,000x too little in IDR (bug-review BF-05).
     */
    public static function rate(string $currency): float
    {
        $currency = strtoupper($currency);
        if ($currency === 'IDR') {
            return 1.0;
        }

        $rate = self::remember('rate:'.$currency.':'.now()->toDateString(), fn () => ExchangeRate::currentFor($currency)?->rate);
        if ($rate === null) {
            throw new ExchangeRateNotFoundException("Kurs untuk {$currency} belum tersedia. Tambahkan kurs terlebih dahulu.");
        }

        return (float) $rate;
    }

    /** Display: minor units -> "1.234.567" (IDR) / "1,234.56" style per currency decimals. */
    public static function format(int $amountMinor, string $currency): string
    {
        $decimals = self::decimals($currency);

        return number_format($amountMinor / (10 ** $decimals), $decimals, ',', '.');
    }

    public static function toIdrMinor(int $amountMinor, string $currency, float $rate): int
    {
        if (strtoupper($currency) === 'IDR') {
            return $amountMinor;
        }

        return (int) round($amountMinor * $rate / (10 ** self::decimals($currency)));
    }

    public static function fromIdrMinor(int $idrMinor, string $currency, float $rate): int
    {
        if (strtoupper($currency) === 'IDR') {
            return $idrMinor;
        }

        return $rate > 0 ? (int) round($idrMinor / $rate * (10 ** self::decimals($currency))) : 0;
    }

    /**
     * Channel fee in the payment's currency. The flat fee is stored in its
     * own currency (default IDR) and is converted before adding.
     */
    public static function channelFeeMinor(string $channel, int $amountMinor, string $currency, float $rate): int
    {
        $paymentChannel = PaymentChannel::tryFrom($channel);
        $cost = $paymentChannel ? PaymentChannelCost::where('channel', $paymentChannel)->first() : null;
        if (! $cost) {
            return 0;
        }

        $percentageFee = (int) round(($amountMinor * $cost->percent_fee) / 10000);

        $flat = $cost->flat_fee_minor instanceof Money ? $cost->flat_fee_minor->amountMinor : (int) $cost->flat_fee_minor;
        $flatCurrency = strtoupper($cost->currency ?? 'IDR');

        if ($flat > 0 && $flatCurrency !== strtoupper($currency)) {
            $flatIdr = self::toIdrMinor($flat, $flatCurrency, self::rate($flatCurrency));
            $flat = self::fromIdrMinor($flatIdr, $currency, $rate);
        }

        return $percentageFee + $flat;
    }
}
