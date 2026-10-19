<?php

namespace Modules\Teachers\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Models\TeacherAssignment;
use Modules\Teachers\Services\TeacherAssignments;

class AssignmentController
{
    public function index(Request $request, AcademicDirectory $academic): JsonResponse
    {
        abort_unless($request->user()->can('teacher_assignments.manage'), 403);

        $sessionId = $request->integer('session_id') ?: $academic->currentSession()?->id;

        $rows = TeacherAssignment::query()->with(['teacher:id,name_bn,name_en', 'schoolClass', 'section', 'subject'])
            ->where('session_id', $sessionId)
            ->when($request->integer('teacher_id'), fn ($query, int $id) => $query->where('teacher_id', $id))
            ->when($request->integer('class_id'), fn ($query, int $id) => $query->where('class_id', $id))
            ->get()->sortBy(fn (TeacherAssignment $a): string => sprintf('%05d-%05d-%d', $a->schoolClass->numeric_order, $a->section_id, $a->is_class_teacher ? 0 : 1))->values();

        return response()->json(['data' => $rows->map(fn (TeacherAssignment $a): array => [
            'id' => $a->id, 'teacher_id' => $a->teacher_id, 'teacher' => $a->teacher->displayName(), 'session_id' => $a->session_id, 'class_id' => $a->class_id, 'class' => $a->schoolClass->displayName(),
            'section_id' => $a->section_id, 'section' => $a->section->displayName(), 'subject_id' => $a->subject_id, 'subject' => $a->subject?->displayName(), 'is_class_teacher' => $a->is_class_teacher,
        ])]);
    }

    public function subjects(Request $request, AcademicDirectory $academic): JsonResponse
    {
        abort_unless($request->user()->can('teacher_assignments.manage'), 403);

        $classId = $request->validate(['class_id' => ['required', 'integer', 'exists:classes,id']])['class_id'];

        return response()->json(['data' => $academic->subjectsOf($classId)->map(fn ($subject): array => ['id' => $subject->id, 'name' => $subject->displayName()])->values()]);
    }

    public function store(Request $request, TeacherAssignments $assignments, AcademicDirectory $academic): JsonResponse
    {
        abort_unless($request->user()->can('teacher_assignments.manage'), 403);

        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'], 'session_id' => ['nullable', 'integer', 'exists:academic_sessions,id'],
            'class_id' => ['required', 'integer', 'exists:classes,id'], 'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id', 'required_without:is_class_teacher'], 'is_class_teacher' => ['sometimes', 'boolean'],
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);
        $sessionId = $data['session_id'] ?? $academic->currentSession()?->id;
        abort_unless($sessionId, 422);

        $assignment = ($data['is_class_teacher'] ?? false)
            ? $assignments->setClassTeacher($teacher, $sessionId, $data['class_id'], $data['section_id'])
            : $assignments->assignSubject($teacher, $sessionId, $data['class_id'], $data['section_id'], $data['subject_id']);

        return response()->json(['data' => ['id' => $assignment->id]], 201);
    }

    public function destroy(Request $request, TeacherAssignment $assignment, TeacherAssignments $assignments): JsonResponse
    {
        abort_unless($request->user()->can('teacher_assignments.manage'), 403);

        $assignments->remove($assignment);

        return response()->json(null, 204);
    }

    public function copy(Request $request, TeacherAssignments $assignments): JsonResponse
    {
        abort_unless($request->user()->can('teacher_assignments.manage'), 403);

        $data = $request->validate(['from_session_id' => ['required', 'integer', 'exists:academic_sessions,id'], 'to_session_id' => ['required', 'integer', 'different:from_session_id', 'exists:academic_sessions,id']]);
        AcademicSession::findOrFail($data['to_session_id']);

        return response()->json(['data' => ['created' => $assignments->copy($data['from_session_id'], $data['to_session_id'])]]);
    }
}
