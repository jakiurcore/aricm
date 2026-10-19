<?php

namespace Modules\Teachers\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Accounts\Models\User;
use Modules\Core\Services\ImageUploader;
use Modules\Core\Services\Sms\SmsManager;
use Modules\Teachers\Models\Teacher;

/**
 * Creates teachers and, optionally, the teacher-role login that goes with them.
 */
class TeacherRegistrar
{
    public function __construct(protected ImageUploader $images, protected SmsManager $sms) {}

    /**
     * @param  array{name_bn: string, name_en?: string|null, designation?: string|null, phone?: string|null, email?: string|null, joined_on?: string|null, status?: string, is_active?: bool}  $data
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

    /**
     * A throwaway password for a login the admin creates or resets without choosing one: easy to read out over the phone.
     */
    public function temporaryPassword(): string
    {
        return Str::password(8, symbols: false);
    }

    /**
     * Tell the teacher their sign-in over SMS. Returns whether the gateway accepted it; no phone number means nothing is sent.
     */
    public function sendCredentials(Teacher $teacher, string $email, string $password): bool
    {
        if (! $teacher->phone) {
            return false;
        }

        return $this->sms->send($teacher->phone, __('teachers::messages.credentials_sms', ['email' => $email, 'password' => $password]));
    }

    public function storePhoto(Teacher $teacher, UploadedFile $photo): void
    {
        $old = $teacher->photo;
        $teacher->update(['photo' => $this->images->store($photo, 'teachers', 600)]);

        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }
}
