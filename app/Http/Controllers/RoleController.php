<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\RoleResource;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['zones', 'users'])->get();

        return response()->json([
            'roles' => RoleResource::collection($roles),
        ]);
    }

    public function show(Role $role)
    {
        return response()->json([
            'role' => new RoleResource($role->load('zones')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')],
            'name'            => ['required', 'string', 'min:2', 'max:100', Rule::unique('roles', 'name')->whereNull('deleted_at')],
            'description'     => ['nullable', 'string', 'max:255'],
            'enabled'         => ['nullable', 'boolean'],
            'zone_ids'        => ['nullable', 'array'],
            'zone_ids.*'      => ['integer', Rule::exists('zones', 'id')],
        ]);

        $role = Role::create($data);

        if ($request->has('zone_ids')) {
            $role->zones()->sync($data['zone_ids'] ?? []);
        }

        return response()->json([
            'role' => new RoleResource($role->load('zones')),
        ], 201);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'organization_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organizations', 'id')],
            'name'            => ['sometimes', 'string', 'min:2', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)->whereNull('deleted_at')],
            'description'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'enabled'         => ['sometimes', 'boolean'],
            'zone_ids'        => ['sometimes', 'nullable', 'array'],
            'zone_ids.*'      => ['integer', Rule::exists('zones', 'id')],
        ]);

        $role->update($data);

        if ($request->has('zone_ids')) {
            $role->zones()->sync($data['zone_ids'] ?? []);
        }

        return response()->json([
            'role' => new RoleResource($role->fresh()->load('zones')),
        ]);
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json(['message' => 'Role deleted']);
    }
}
