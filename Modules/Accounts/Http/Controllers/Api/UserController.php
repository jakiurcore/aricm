<?php

namespace Modules\Accounts\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Accounts\Http\Requests\UserRequest;
use Modules\Accounts\Http\Resources\UserResource;
use Modules\Accounts\Models\User;

class UserController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query
                ->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->string('role')->toString(), fn ($query, string $role) => $query->role($role))
            ->orderBy('name')
            ->paginate(15);

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->except(['roles']));
        $user->syncRoles($request->roleNames());

        return (new UserResource($user->load('roles')))->response()->setStatusCode(201);
    }

    public function update(UserRequest $request, User $user): UserResource
    {
        $data = $request->safe()->except(['roles', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        // An admin must not lock themselves out.
        if ($user->is($request->user())) {
            unset($data['is_active']);
        }

        $user->update($data);

        if ($request->has('roles') && ! $user->is($request->user())) {
            $user->syncRoles($request->roleNames());
        }

        return new UserResource($user->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return response()->json(null, 204);
    }
}
