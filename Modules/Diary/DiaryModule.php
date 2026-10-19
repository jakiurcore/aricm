<?php

namespace Modules\Diary;

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
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Accounts\Models\User;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Contracts\ProvidesPortalMenu;
use Modules\Core\Support\MenuItem;
use Modules\Diary\Console\SendDailySummaryCommand;
use Modules\Diary\Database\Seeders\QuickPhraseSeeder;
use Modules\Diary\Services\DiaryFeed;
use Modules\Students\Models\Student;
use Modules\Teachers\Contracts\ProvidesTeacherMenu;
use Modules\Teachers\Services\TeacherScope;

/**
 * Diary: what each teacher taught and assigned, per subject per day. Teachers write it from the teacher panel,
 * students and guardians read it in the portal, the office watches compliance. Depends on Core, Accounts, Academic,
 * Students and Teachers.
 */
class DiaryModule implements Module, ProvidesApiRoutes, ProvidesCommands, ProvidesConfig, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesPortalMenu, ProvidesRoutes, ProvidesSeeders, ProvidesTeacherMenu, ProvidesTranslations, ProvidesViteEntries
{
    public function name(): string
    {
        return 'diary';
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
        return [QuickPhraseSeeder::class];
    }

    public function commands(): array
    {
        return [SendDailySummaryCommand::class];
    }

    public function config(): array
    {
        return ['modules.diary' => 'config/diary.php'];
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
        return ['diary.write', 'diary.monitor', 'diary.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return ['teacher' => ['diary.write']];
    }

    public function portalMenu(Authenticatable $student): array
    {
        return [new MenuItem(__('diary::messages.menu'), 'portal.diary', 'book', 15, true, fn (Authenticatable $user): int => $user instanceof Student ? app(DiaryFeed::class)->unseenCount($user) : 0)];
    }

    public function teacherMenu(User $teacher): array
    {
        $items = [new MenuItem(__('diary::messages.teacher_menu.diary'), 'teacher.diary', 'book', 10, true)];

        if (collect(app(TeacherScope::class)->sections($teacher))->contains('is_class_teacher', true)) {
            $items[] = new MenuItem(__('diary::messages.teacher_menu.class_day'), 'teacher.class-day', 'users', 20, true);
        }

        return $items;
    }
}
