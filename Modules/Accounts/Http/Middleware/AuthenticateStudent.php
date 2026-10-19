<?php

namespace Modules\Accounts\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the student portal: signed in on the student guard, and past the forced first-login password change.
 */
class AuthenticateStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('student');

        if (! $guard->check()) {
            return redirect()->guest(route('student.login'));
        }

        // From here on auth()->user() and $request->user() are the student.
        Auth::shouldUse('student');

        if ($guard->user()->must_change_password && ! $request->routeIs('student.password.*', 'student.logout')) {
            return redirect()->route('student.password.edit');
        }

        return $next($request);
    }
}
