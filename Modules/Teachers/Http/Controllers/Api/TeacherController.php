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
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->where(fn ($inner) => $inner->where('name_bn', 'like', "%{$term}%")->orWhere('name_en', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")))
            ->when($request->boolean('active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name_bn')->paginate($request->integer('per_page', 20));

        return TeacherResource::collection($teachers);
    }

    public function store(TeacherRequest $request, TeacherRegistrar $registrar): JsonResponse
    {
        $data = $request->safe()->only(['name_bn', 'name_en', 'designation', 'phone', 'is_active']);
        $teacher = $registrar->register($data, $request->filled('password') ? ['email' => $request->string('email')->toString(), 'password' => $request->string('password')->toString()] : null);

        return (new TeacherResource($teacher->load('user')))->response()->setStatusCode(201);
    }

    public function update(TeacherRequest $request, Teacher $teacher, TeacherRegistrar $registrar): TeacherResource
    {
        $teacher->update($request->safe()->only(['name_bn', 'name_en', 'designation', 'phone', 'is_active']));
        $registrar->syncLogin($teacher);

        return new TeacherResource($teacher->load('user'));
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

        $data = $request->validate(['email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')], 'password' => ['required', Password::min(8)]]);
        $registrar->createLogin($teacher, $data['email'], $data['password']);

        return new TeacherResource($teacher->load('user'));
    }

    public function resetPassword(Request $request, Teacher $teacher): JsonResponse
    {
        abort_unless($request->user()->can('teachers.manage'), 403);
        abort_unless($teacher->user, 422, __('teachers::messages.no_login'));

        $teacher->user->update(['password' => $request->validate(['password' => ['required', Password::min(8)]])['password']]);

        return response()->json(['data' => ['ok' => true]]);
    }
}
