<?php

namespace Modules\Accounts\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Accounts\Http\Requests\LoginRequest;

class LoginController
{
    public function create(): View
    {
        return view('accounts::auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $home = $user->can('admin.access')
            ? config('modules.accounts.home.admin')
            : ($user->hasRole('teacher') ? config('modules.accounts.home.teacher') : '/');

        return redirect()->intended($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
