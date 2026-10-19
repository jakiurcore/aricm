<?php

namespace Modules\Accounts\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Accounts\Http\Requests\RoleRequest;
use Modules\Accounts\Http\Resources\RoleResource;
use Modules\Accounts\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return RoleResource::collection(Role::query()->with('permissions')->withCount('users')->orderBy('id')->get());
    }

    /**
     * Every permission modules have declared, grouped by the prefix before the dot.
     */
    public function permissions(): JsonResponse
    {
        Gate::authorize('viewAny', Role::class);

        $grouped = Permission::query()->orderBy('name')->pluck('name')
            ->groupBy(fn (string $name): string => explode('.', $name)[0])
            ->map(fn ($names) => $names->values());

        return response()->json(['data' => $grouped]);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->input('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->input('permissions', []));

        return (new RoleResource($role->load('permissions')))->response()->setStatusCode(201);
    }

    public function update(RoleRequest $request, Role $role): RoleResource
    {
        if (! $role->isSystem() && $request->filled('name')) {
            $role->update(['name' => $request->input('name')]);
        }

        $role->syncPermissions($request->input('permissions', []));

        return new RoleResource($role->load('permissions'));
    }

    public function destroy(Role $role): JsonResponse
    {
        Gate::authorize('delete', $role);

        $role->delete();

        return response()->json(null, 204);
    }
}
