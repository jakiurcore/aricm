<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Academic\Http\Controllers\Api\Concerns\GuardsDeletion;
use Modules\Academic\Http\Requests\SessionRequest;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Services\SessionManager;

class SessionController
{
    use GuardsDeletion;

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', AcademicSession::class);

        return response()->json(['data' => AcademicSession::query()->orderByDesc('name')->get()]);
    }

    public function store(SessionRequest $request, SessionManager $manager): JsonResponse
    {
        $session = AcademicSession::create($request->safe()->except('is_current'));

        if ($request->boolean('is_current') || AcademicSession::count() === 1) {
            $manager->makeCurrent($session);
        }

        return response()->json(['data' => $session->fresh()], 201);
    }

    public function update(SessionRequest $request, AcademicSession $session, SessionManager $manager): JsonResponse
    {
        $session->update($request->safe()->except('is_current'));

        if ($request->boolean('is_current')) {
            $manager->makeCurrent($session);
        }

        return response()->json(['data' => $session->fresh()]);
    }

    public function destroy(AcademicSession $session): JsonResponse
    {
        Gate::authorize('delete', $session);

        abort_if($session->is_current, 422, __('academic::messages.current_session_locked'));

        return $this->deleteUnlessInUse($session);
    }
}
