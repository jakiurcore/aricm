<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Accounts\Http\Requests\StudentPasswordRequest;

class StudentPasswordController
{
    public function edit(): View
    {
        return view('accounts::student.password', ['forced' => (bool) auth('student')->user()->must_change_password]);
    }

    public function update(StudentPasswordRequest $request): RedirectResponse
    {
        $request->user('student')->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        return redirect(config('modules.accounts.home.student'))->with('status', __('accounts::messages.student.password_changed'));
    }
}
