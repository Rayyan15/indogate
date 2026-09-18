<?php

namespace App\Domain\Pricing;

use InvalidArgumentException;

/**
 * PRD M4: a manual price override must always carry a reason. Validated
 * here in the constructor, not just in the form layer.
 */
final class Override
{
    public function __construct(
        public readonly Money $amount,
        public readonly string $reason,
    ) {
        if (trim($this->reason) === '') {
            throw new InvalidArgumentException('Override price must include a reason.');
        }
    }
}
