<?php

namespace Modules\Students\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Students\Http\Requests\StudentRequest;
use Modules\Students\Http\Resources\StudentResource;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentDirectory;
use Modules\Students\Services\StudentPasswords;
use Modules\Students\Services\StudentRegistrar;
use Modules\Students\Services\StudentSummaryCollector;

class StudentController
{
    public function index(Request $request, StudentDirectory $directory): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Student::class);

        $students = $directory->query($request->only(['session_id', 'class_id', 'section_id', 'status', 'search']))
            ->with(['schoolClass', 'section'])
            ->orderBy('class_id')->orderByRaw('roll is null')->orderBy('roll')->orderBy('id')
            ->paginate(20);

        return StudentResource::collection($students);
    }

    public function show(Student $student, StudentSummaryCollector $summary): JsonResponse
    {
        Gate::authorize('view', $student);

        $student->load(['schoolClass', 'section', 'session', 'enrollments.session', 'enrollments.schoolClass', 'enrollments.section']);

        return response()->json([
            'data' => (new StudentResource($student))->resolve(),
            'enrollments' => $student->enrollments->map(fn ($enrollment): array => [
                'session' => $enrollment->session->name,
                'class' => $enrollment->schoolClass->displayName(),
                'section' => $enrollment->section?->displayName(),
                'roll' => $enrollment->roll,
                'status' => $enrollment->status,
            ]),
            'summary' => collect($summary->for($student))->map->toArray()->values(),
        ]);
    }

    public function store(StudentRequest $request, StudentRegistrar $registrar): JsonResponse
    {
        $student = $registrar->register($request->safe()->except('photo'), $request->file('photo'));

        return (new StudentResource($student->load(['schoolClass', 'section'])))->response()->setStatusCode(201);
    }

    public function update(StudentRequest $request, Student $student, StudentRegistrar $registrar): StudentResource
    {
        $registrar->update($student, $request->safe()->except('photo'), $request->file('photo'));

        return new StudentResource($student->load(['schoolClass', 'section']));
    }

    public function destroy(Student $student): JsonResponse
    {
        Gate::authorize('delete', $student);

        $student->delete();

        return response()->json(null, 204);
    }

    public function resetPassword(Student $student, StudentPasswords $passwords): JsonResponse
    {
        Gate::authorize('resetPassword', $student);

        $passwords->reset($student);

        return response()->json(['message' => __('students::messages.password_reset')]);
    }
}
