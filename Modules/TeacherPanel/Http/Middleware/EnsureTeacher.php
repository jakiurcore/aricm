<?php

namespace Modules\TeacherPanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Teachers\Services\TeacherScope;
use Symfony\Component\HttpFoundation\Response;

/**
 * The teacher panel is for active users that have an active teacher record. Everyone else gets a 403.
 */
class EnsureTeacher
{
    public function __construct(protected TeacherScope $scope) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $this->scope->teacherFor($user), 403);

        return $next($request);
    }
}
