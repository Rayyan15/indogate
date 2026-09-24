<?php

namespace Tests\Unit;

use App\Casts\MoneyCast;
use App\Domain\Pricing\Models\PaymentChannelCost;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyCastTest extends TestCase
{
    public function test_float_is_rejected_and_int_passes(): void
    {
        $cast = new MoneyCast;
        $model = new PaymentChannelCost;

        $this->assertSame(['x' => 5], $cast->set($model, 'x', 5, []));

        $this->expectException(InvalidArgumentException::class);
        $cast->set($model, 'x', 12.5, []);
    }
}
