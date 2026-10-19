<?php

namespace Modules\Accounts\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Accounts\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Accounts\Models\User;

class CreateAdminCommand extends Command
{
    protected $signature = 'accounts:create-admin {email : Login email} {--name= : Display name} {--password= : Password (asked for when left out)} {--role=super_admin : super_admin or admin}';

    protected $description = 'Create an admin login (the first one after a fresh install), or reset the password of an existing one';

    public function handle(RolesAndPermissionsSeeder $roles): int
    {
        $email = (string) $this->argument('email');
        $role = (string) $this->option('role');
        $password = $this->option('password') ?: $this->secret('Password (at least 8 characters)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'role' => $role],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(8)], 'role' => ['required', Rule::in(['super_admin', 'admin'])]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $roles->run();

        $user = User::query()->firstOrNew(['email' => $email]);
        $existed = $user->exists;
        $user->fill(['name' => $this->option('name') ?: ($user->name ?: $email), 'password' => $password, 'is_active' => true])->save();
        $user->syncRoles([$role]);

        $this->info(($existed ? 'Updated' : 'Created')." {$role}: {$email}");

        return self::SUCCESS;
    }
}
