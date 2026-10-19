<?php

namespace Modules\Fees;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesCommands;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Contracts\ProvidesPortalMenu;
use Modules\Core\Contracts\ProvidesVerification;
use Modules\Exams\Contracts\ProvidesAdmitCardEligibility;
use Modules\Exams\Models\Exam;
use Modules\Exams\Support\Eligibility;
use Modules\Fees\Console\ApplyLateFinesCommand;
use Modules\Fees\Console\GenerateMonthlyInvoicesCommand;
use Modules\Fees\Console\SendDueRemindersCommand;
use Modules\Fees\Database\Seeders\FeeHeadSeeder;
use Modules\Fees\Services\EligibilityChecker;
use Modules\Fees\Services\FeeSummary;
use Modules\Fees\Services\ReceiptVerification;
use Modules\Results\Contracts\ProvidesResultEligibility;
use Modules\Students\Contracts\ProvidesStudentSummary;
use Modules\Students\Models\Student;

/**
 * Fees: heads, structures, concessions, invoices (monthly/one-time/exam), payments (counter, manual wallet/bank,
 * optional gateway), receipts, reports, late fines. Money is whole poisha everywhere.
 *
 * Implements the contracts other modules define so they never import Fees: admit card eligibility (Exams), result
 * eligibility (Results), student summary (Students), portal menu and verification (Core).
 * Depends on Core, Accounts, Academic, Students, Exams, Results.
 */
class FeesModule implements Module, ProvidesAdmitCardEligibility, ProvidesApiRoutes, ProvidesCommands, ProvidesConfig, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesPortalMenu, ProvidesResultEligibility, ProvidesRoutes, ProvidesSeeders, ProvidesStudentSummary, ProvidesTranslations, ProvidesVerification, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'fees';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }

    public function migrations(): array
    {
        return ['Database/Migrations'];
    }

    public function seeders(): array
    {
        return [FeeHeadSeeder::class];
    }

    public function commands(): array
    {
        return [GenerateMonthlyInvoicesCommand::class, ApplyLateFinesCommand::class, SendDueRemindersCommand::class];
    }

    public function translations(): string
    {
        return 'lang';
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function config(): array
    {
        return ['modules.fees' => 'config/fees.php'];
    }

    public function viteEntries(): array
    {
        return ['resources/js/dashboard.tsx'];
    }

    public function dashboardScripts(): array
    {
        return ['resources/js/dashboard.tsx'];
    }

    public function permissions(): array
    {
        return ['fees.view', 'fees.collect', 'fees.verify', 'fees.manage_structure', 'fees.concession', 'fees.waive', 'fees.generate', 'fees.reports', 'fees.settings'];
    }

    /**
     * The accountant collects, verifies, generates and reports but cannot change the structure, grant concessions,
     * waive bills or edit settings. Teachers get nothing. Admins hold everything.
     */
    public function defaultRolePermissions(): array
    {
        return ['accountant' => ['fees.view', 'fees.collect', 'fees.verify', 'fees.generate', 'fees.reports']];
    }

    public function portalMenu(Authenticatable $student): array
    {
        return [app(FeeSummary::class)->menuItem($student)];
    }

    public function studentSummary(Student $student): array
    {
        return app(FeeSummary::class)->items($student);
    }

    public function verificationHandlers(): array
    {
        return ['receipt' => new ReceiptVerification];
    }

    public function admitCardEligibility(Exam $exam, Student $student): Eligibility
    {
        return app(EligibilityChecker::class)->admitCard($exam, $student);
    }

    public function resultEligibility(Exam $exam, Student $student): Eligibility
    {
        return app(EligibilityChecker::class)->result($exam, $student);
    }
}
