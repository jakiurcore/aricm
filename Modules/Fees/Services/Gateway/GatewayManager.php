<?php

namespace Modules\Fees\Services\Gateway;

use Closure;
use InvalidArgumentException;
use Modules\Fees\Contracts\PaymentGateway;
use Modules\Fees\Services\FeeSettings;

/**
 * Resolves gateway drivers by name. Which one is active (and whether online payment is on at all) is a fee setting; the
 * credentials are in .env.
 */
class GatewayManager
{
    /** @var array<string, Closure(): PaymentGateway> Extra or replacement drivers; static because the manager is resolved fresh each time. */
    protected static array $extensions = [];

    public function __construct(protected FeeSettings $settings) {}

    /**
     * Add or replace a driver (tests register a fake one here).
     *
     * @param  Closure(): PaymentGateway  $factory
     */
    public function extend(string $name, Closure $factory): void
    {
        self::$extensions[$name] = $factory;
    }

    public static function forgetExtensions(): void
    {
        self::$extensions = [];
    }

    public function driver(string $name): PaymentGateway
    {
        $drivers = self::$extensions + [
            'manual' => fn () => new ManualGateway,
            'sslcommerz' => fn () => new SslCommerzGateway,
            'bkash' => fn () => new BkashGateway,
        ];

        return ($drivers[$name] ?? throw new InvalidArgumentException("Unknown payment gateway [{$name}]."))();
    }

    /**
     * The gateway students can pay through right now, or null: online payment off, driver "manual", or credentials missing.
     */
    public function active(): ?PaymentGateway
    {
        if (! $this->settings->gatewayEnabled()) {
            return null;
        }

        $driver = $this->driver((string) $this->settings->get('gateway_driver'));

        return $driver->isConfigured() ? $driver : null;
    }
}
