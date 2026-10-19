<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Notices\Services\NoticeDirectory;
use Modules\TeacherPanel\Http\Requests\ChangePasswordRequest;
use Modules\TeacherPanel\Support\PanelFormat;
use Modules\Teachers\Models\TeacherAssignment;
use Modules\Teachers\Services\TeacherScope;

class ProfileController
{
    public function show(Request $request, TeacherScope $scope, NoticeDirectory $notices): View
    {
        $user = $request->user();
        $teacher = $scope->teacherFor($user);

        $assignments = $scope->assignments($user)->sortBy(fn (TeacherAssignment $assignment): string => sprintf('%05d-%05d-%05d', $assignment->schoolClass->numeric_order, $assignment->section_id, $assignment->subject_id ?? 0))
            ->map(fn (TeacherAssignment $assignment): array => [
                'label' => PanelFormat::section($assignment->schoolClass->displayName(), $assignment->section->displayName()).($assignment->subject ? ' – '.$assignment->subject->displayName() : ''),
                'class_teacher' => $assignment->is_class_teacher,
            ])->values();

        return view('teacher-panel::pages.profile', ['teacher' => $teacher, 'user' => $user, 'assignments' => $assignments, 'notices' => $notices->latest(4)]);
    }

    public function editPassword(): View
    {
        return view('teacher-panel::pages.password');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill(['password' => $request->input('password')])->save();

        return redirect()->route('teacher.profile')->with('status', __('teacher-panel::messages.password.changed'));
    }
}
