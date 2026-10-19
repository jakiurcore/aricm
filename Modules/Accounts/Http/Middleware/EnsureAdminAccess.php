<?php

namespace Modules\Accounts\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for /admin: signed-in users with the admin.access permission only. Teachers and students get a 403.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->can('admin.access'), 403);

        return $next($request);
    }
}
