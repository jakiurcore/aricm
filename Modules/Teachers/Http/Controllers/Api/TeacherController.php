<?php

namespace Modules\Teachers\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Teachers\Http\Requests\TeacherRequest;
use Modules\Teachers\Http\Resources\TeacherResource;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherRegistrar;

class TeacherController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('teachers.manage') || $request->user()->can('teacher_assignments.manage'), 403);

        $teachers = Teacher::query()->with('user')->withCount('assignments')
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->where(fn ($inner) => $inner->where('name_bn', 'like', "%{$term}%")->orWhere('name_en', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name_bn')->paginate($request->integer('per_page', 20));

        return TeacherResource::collection($teachers);
    }

    public function store(TeacherRequest $request, TeacherRegistrar $registrar): JsonResponse
    {
        $login = null;
        $temporary = null;

        if ($request->filled('password')) {
            $login = ['email' => $request->string('email')->toString(), 'password' => $request->string('password')->toString()];
        } elseif ($request->boolean('create_login')) {
            $temporary = $registrar->temporaryPassword();
            $login = ['email' => $request->string('email')->toString(), 'password' => $temporary];
        }

        $teacher = $registrar->register($this->profile($request), $login);

        if ($request->file('photo')) {
            $registrar->storePhoto($teacher, $request->file('photo'));
        }

        if ($temporary !== null) {
            $registrar->sendCredentials($teacher, $login['email'], $temporary);
        }

        return (new TeacherResource($teacher->load('user')))->additional($temporary !== null ? ['temporary_password' => $temporary] : [])->response()->setStatusCode(201);
    }

    public function update(TeacherRequest $request, Teacher $teacher, TeacherRegistrar $registrar): TeacherResource
    {
        $teacher->update($this->profile($request));

        if ($request->file('photo')) {
            $registrar->storePhoto($teacher, $request->file('photo'));
        }

        $registrar->syncLogin($teacher);
        $temporary = null;

        if ($request->boolean('create_login') && ! $teacher->user_id) {
            $temporary = $registrar->temporaryPassword();
            $registrar->createLogin($teacher, $request->string('email')->toString(), $temporary);
            $registrar->sendCredentials($teacher, $request->string('email')->toString(), $temporary);
        }

        return (new TeacherResource($teacher->load('user')))->additional($temporary !== null ? ['temporary_password' => $temporary] : []);
    }

    public function destroy(Request $request, Teacher $teacher): JsonResponse
    {
        abort_unless($request->user()->can('teachers.manage'), 403);

        $teacher->user?->update(['is_active' => false]);
        $teacher->delete();

        return response()->json(null, 204);
    }

    public function createLogin(Request $request, Teacher $teacher, TeacherRegistrar $registrar): TeacherResource
    {
        abort_unless($request->user()->can('teachers.manage'), 403);
        abort_if($teacher->user_id, 422, __('teachers::messages.has_login'));

        $data = $request->validate(['email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')], 'password' => ['nullable', Password::min(8)]]);
        $email = $data['email'] ?? $teacher->email;
        abort_unless($email, 422, __('teachers::messages.email_needed'));

        $temporary = empty($data['password']) ? $registrar->temporaryPassword() : null;
        $registrar->createLogin($teacher, $email, $temporary ?? $data['password']);

        if ($temporary !== null) {
            $registrar->sendCredentials($teacher, $email, $temporary);
        }

        return (new TeacherResource($teacher->load('user')))->additional($temporary !== null ? ['temporary_password' => $temporary] : []);
    }

    public function resetPassword(Request $request, Teacher $teacher, TeacherRegistrar $registrar): JsonResponse
    {
        abort_unless($request->user()->can('teachers.manage'), 403);
        abort_unless($teacher->user, 422, __('teachers::messages.no_login'));

        $chosen = $request->validate(['password' => ['nullable', Password::min(8)]])['password'] ?? null;
        $password = $chosen ?? $registrar->temporaryPassword();
        $teacher->user->update(['password' => $password]);
        $sent = $chosen === null && $registrar->sendCredentials($teacher, $teacher->user->email, $password);

        return response()->json(['data' => ['ok' => true, 'temporary_password' => $chosen === null ? $password : null, 'sms_sent' => $sent]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(TeacherRequest $request): array
    {
        $data = $request->safe()->only(['name_bn', 'name_en', 'designation', 'phone', 'email', 'joined_on', 'status', 'is_active']);

        if (isset($data['status'])) {
            $data['is_active'] = $data['status'] !== 'inactive';
        } elseif (isset($data['is_active'])) {
            $data['status'] = $data['is_active'] ? 'active' : 'inactive';
        }

        return $data;
    }
}
