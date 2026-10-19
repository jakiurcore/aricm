<?php

namespace Modules\Fees\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\Fees\Support\Money;

/**
 * unsignedBigInteger poisha column ⇄ Money. Setting accepts a Money or an int of poisha, never a float.
 *
 * @implements CastsAttributes<Money, Money|int>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): Money
    {
        return Money::poisha((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return match (true) {
            $value instanceof Money => $value->poisha,
            is_int($value) => $value,
            default => throw new InvalidArgumentException("[{$key}] must be a Money or whole poisha."),
        };
    }
}
