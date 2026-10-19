<?php

namespace Modules\Fees\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\InvoiceGenerator;

class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'fees:generate-monthly {--month= : Month to bill as YYYY-MM (default: the current month)} {--force : Run even when automatic generation is switched off}';

    protected $description = 'Create the monthly fee invoices for every active student (safe to run again).';

    public function handle(InvoiceGenerator $generator, FeeSettings $settings): int
    {
        if (! $this->option('force') && ! $settings->autoGenerate()) {
            $this->components->info('Automatic monthly generation is off; use --force to run it anyway.');

            return self::SUCCESS;
        }

        $month = $this->option('month') ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth() : now()->startOfMonth();
        $summary = $generator->generateMonthly($month);

        $this->components->info("{$month->format('Y-m')}: {$summary->created} created, {$summary->skippedExisting} already billed, {$summary->skippedPreAdmission} before admission, {$summary->skippedNoFee} without a fee.");

        return self::SUCCESS;
    }
}
