<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Accounts\Http\Requests\StudentLoginRequest;

class StudentLoginController
{
    public function create(): View
    {
        return view('accounts::student.login');
    }

    public function store(StudentLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(config('modules.accounts.home.student'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }
}
