<?php

namespace Modules\Students\Support;

final readonly class StudentSummaryItem
{
    /**
     * @param  string  $label  Already translated.
     * @param  string  $value  Already formatted (Bangla digits, currency).
     * @param  string  $tone  neutral|success|warn|danger
     * @param  string|null  $route  Optional named route for the portal card to link to.
     */
    public function __construct(
        public string $label,
        public string $value,
        public string $tone = 'neutral',
        public ?string $route = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'value' => $this->value, 'tone' => $this->tone, 'route' => $this->route ? route($this->route) : null];
    }
}
