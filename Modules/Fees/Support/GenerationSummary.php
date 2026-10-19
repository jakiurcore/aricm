<?php

namespace Modules\Fees\Support;

final class GenerationSummary
{
    public int $created = 0;

    public int $skippedExisting = 0;

    public int $skippedPreAdmission = 0;

    public int $skippedNoFee = 0;

    public Money $totalAmount;

    public function __construct(public readonly bool $dryRun = false)
    {
        $this->totalAmount = Money::zero();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dry_run' => $this->dryRun,
            'created' => $this->created,
            'skipped_existing' => $this->skippedExisting,
            'skipped_pre_admission' => $this->skippedPreAdmission,
            'skipped_no_fee' => $this->skippedNoFee,
            'total_amount' => $this->totalAmount->poisha,
        ];
    }
}
