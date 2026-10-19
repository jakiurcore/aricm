<?php

namespace Modules\Fees\Console;

use Illuminate\Console\Command;
use Modules\Fees\Services\LateFineApplier;

class ApplyLateFinesCommand extends Command
{
    protected $signature = 'fees:apply-late-fines';

    protected $description = 'Add the late fine to overdue unpaid invoices (idempotent; does nothing while the fine is zero).';

    public function handle(LateFineApplier $applier): int
    {
        $this->components->info($applier->apply().' invoice(s) updated.');

        return self::SUCCESS;
    }
}
