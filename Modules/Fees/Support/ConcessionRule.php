<?php

namespace Modules\Fees\Support;

use Carbon\CarbonInterface;
use Modules\Fees\Enums\ConcessionKind;
use Modules\Fees\Models\StudentConcession;

/**
 * A concession as the calculator sees it: plain data.
 */
final readonly class ConcessionRule
{
    /**
     * @param  int  $value  Percent (1–100) for a percent concession, poisha for a fixed one.
     */
    public function __construct(
        public ConcessionKind $kind,
        public int $value,
        public CarbonInterface $validFrom,
        public ?CarbonInterface $validTo = null,
        public ?int $headId = null,
    ) {}

    public static function fromModel(StudentConcession $concession): self
    {
        return new self($concession->kind, $concession->value, $concession->valid_from_month, $concession->valid_to_month, $concession->fee_head_id);
    }

    /**
     * Applies to this head and this billing month (months are compared as whole months, both ends inclusive).
     */
    public function appliesTo(int $headId, CarbonInterface $month): bool
    {
        $month = $month->copy()->startOfMonth();

        return ($this->headId === null || $this->headId === $headId)
            && $month->gte($this->validFrom->copy()->startOfMonth())
            && ($this->validTo === null || $month->lte($this->validTo->copy()->startOfMonth()));
    }
}
