<?php

namespace Modules\Results;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesHomepageSections;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Contracts\ProvidesPortalMenu;
use Modules\Core\Contracts\ProvidesVerification;
use Modules\Core\Support\HomepageSection;
use Modules\Core\Support\MenuItem;
use Modules\Exams\Models\Exam;
use Modules\Results\Database\Seeders\GradingScaleSeeder;
use Modules\Results\Services\MarksheetVerification;
use Modules\Results\Services\ResultDirectory;
use Modules\Students\Contracts\ProvidesStudentSummary;
use Modules\Students\Models\Student;
use Modules\Students\Support\StudentSummaryItem;

/**
 * Results: grading scales, the marks grid, the result engine (Services\Engine, no database), processing with
 * positions, publish/unpublish, public result search and marksheet QR verification.
 * Defines Contracts\ProvidesResultEligibility (Fees implements it). Depends on Core, Accounts, Academic, Students,
 * Exams (and Teachers once teacher marks entry is switched on).
 */
class ResultsModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesHomepageSections, ProvidesMigrations, ProvidesPermissions, ProvidesPortalMenu, ProvidesRoutes, ProvidesSeeders, ProvidesStudentSummary, ProvidesTranslations, ProvidesVerification, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'results';
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
        return [GradingScaleSeeder::class];
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function translations(): string
    {
        return 'lang';
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
        return ['results.view', 'results.enter_marks', 'results.process', 'results.publish', 'results.grading'];
    }

    public function defaultRolePermissions(): array
    {
        return [];
    }

    public function homepageSections(): array
    {
        $exams = Exam::query()->with('classes')->where('result_published', true)->orderByDesc('start_date')->get();

        return [new HomepageSection('results::home.widget', [
            'exams' => $exams,
            'classMap' => $exams->mapWithKeys(fn (Exam $exam): array => [$exam->id => $exam->classes->map(fn ($class): array => ['id' => $class->id, 'name' => $class->displayName()])->values()]),
        ], 60)];
    }

    public function portalMenu(Authenticatable $student): array
    {
        return [new MenuItem(__('results::messages.menu'), 'portal.results', 'chart', 40, true)];
    }

    public function verificationHandlers(): array
    {
        return ['marksheet' => new MarksheetVerification];
    }

    public function studentSummary(Student $student): array
    {
        $directory = app(ResultDirectory::class);
        $exam = $directory->publishedExamsFor($student)->first();
        $result = $exam ? $directory->publishedResult($exam, $student) : null;

        if (! $result || ! $directory->eligibility($exam, $student)->allowed) {
            return [];
        }

        return [new StudentSummaryItem(__('results::messages.summary.latest_gpa', ['exam' => $exam->displayName()]), bn_number($result->gpa, 2).' ('.$result->grade.')', $result->is_passed ? 'success' : 'danger', 'portal.results', 20)];
    }
}
