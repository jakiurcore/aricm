<?php

use Modules\Core\Services\Settings;
use Modules\Core\Support\Bangla;

if (! function_exists('bn_digits')) {
    function bn_digits(string|int|float|null $value, ?bool $force = null): string
    {
        return Bangla::digits($value, $force);
    }
}

if (! function_exists('bn_number')) {
    function bn_number(int|float|string|null $value, int $decimals = 0): string
    {
        return Bangla::number($value, $decimals);
    }
}

if (! function_exists('bn_date')) {
    function bn_date(DateTimeInterface|string|null $date, ?bool $force = null): string
    {
        return Bangla::date($date, $force);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('localized')) {
    /**
     * Pick the *_bn / *_en column of a model for the current locale, falling back to Bangla.
     */
    function localized(object $model, string $field): ?string
    {
        $primary = $model->{$field.'_'.app()->getLocale()} ?? null;

        return filled($primary) ? $primary : ($model->{$field.'_bn'} ?? null);
    }
}
