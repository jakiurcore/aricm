<?php

namespace Modules\Fees\Services;

use Illuminate\Support\Facades\DB;
use Modules\Fees\Models\FeeSequence;

/**
 * Gap-free yearly counters for INV-2026-000123 and RCPT-2026-000045. Call inside a transaction so the row lock holds
 * until the number is used.
 */
class DocumentNumbers
{
    public function invoice(?int $year = null): string
    {
        return $this->next('INV', $year);
    }

    public function receipt(?int $year = null): string
    {
        return $this->next('RCPT', $year);
    }

    protected function next(string $name, ?int $year): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($name, $year): string {
            $sequence = FeeSequence::query()->where(['name' => $name, 'year' => $year])->lockForUpdate()->first()
                ?? FeeSequence::query()->create(['name' => $name, 'year' => $year, 'last' => 0]);

            $sequence->increment('last');

            return sprintf('%s-%d-%06d', $name, $year, $sequence->last);
        });
    }
}
