<?php

namespace Modules\Exams\Support;

use Illuminate\Support\Facades\Route;

/**
 * The answer of an eligibility provider (admit card now, results later via their own contract).
 */
final readonly class Eligibility
{
    private function __construct(public bool $allowed, public ?string $reason = null, public ?string $actionRoute = null) {}

    public static function allow(): self
    {
        return new self(true);
    }

    /**
     * @param  string  $reason  Already translated, shown to the student.
     * @param  string|null  $actionRoute  Optional named route the student can follow to fix it (e.g. pay fees).
     */
    public static function deny(string $reason, ?string $actionRoute = null): self
    {
        return new self(false, $reason, $actionRoute);
    }

    /**
     * @return array{allowed: bool, reason: string|null, action_url: string|null}
     */
    public function toArray(): array
    {
        return ['allowed' => $this->allowed, 'reason' => $this->reason, 'action_url' => $this->actionRoute && Route::has($this->actionRoute) ? route($this->actionRoute) : null];
    }
}
