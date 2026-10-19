<?php

namespace Modules\Teachers\Services;

use Illuminate\Support\Facades\DB;
use Modules\Accounts\Models\User;
use Modules\Teachers\Models\Teacher;

/**
 * Creates teachers and, optionally, the teacher-role login that goes with them.
 */
class TeacherRegistrar
{
    /**
     * @param  array{name_bn: string, name_en?: string|null, designation?: string|null, phone?: string|null, is_active?: bool}  $data
     * @param  array{email: string, password: string}|null  $login
     */
    public function register(array $data, ?array $login = null): Teacher
    {
        return DB::transaction(function () use ($data, $login): Teacher {
            $teacher = Teacher::create(['is_active' => true, ...$data]);

            if ($login) {
                $this->createLogin($teacher, $login['email'], $login['password']);
            }

            return $teacher;
        });
    }

    public function createLogin(Teacher $teacher, string $email, string $password): User
    {
        $user = User::create(['name' => $teacher->name_bn, 'email' => $email, 'phone' => $teacher->phone, 'password' => $password, 'is_active' => $teacher->is_active]);
        $user->assignRole('teacher');
        $teacher->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Keep the login's name, phone and active flag in step with the teacher record.
     */
    public function syncLogin(Teacher $teacher): void
    {
        $teacher->user?->update(['name' => $teacher->name_bn, 'phone' => $teacher->phone, 'is_active' => $teacher->is_active]);
    }
}
