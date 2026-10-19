<?php

namespace Modules\Core\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * One navigation entry, shared by the student portal and the teacher panel.
 */
final readonly class MenuItem
{
    /**
     * @param  string  $label  Already translated text.
     * @param  string  $route  Named route.
     * @param  string  $icon  Icon key understood by the layout.
     * @param  Closure(Authenticatable): (int|null)|null  $badge
     */
    public function __construct(
        public string $label,
        public string $route,
        public string $icon,
        public int $sortOrder = 0,
        public bool $inBottomNav = false,
        public ?Closure $badge = null,
    ) {}

    public function badgeFor(Authenticatable $user): ?int
    {
        $count = $this->badge ? ($this->badge)($user) : null;

        return $count !== null && $count > 0 ? $count : null;
    }
}
