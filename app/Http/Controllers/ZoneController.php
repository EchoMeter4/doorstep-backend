<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\ZoneResource;

class ZoneController extends Controller
{
    public function index()
    {
        return response()->json([
            'zones' => ZoneResource::collection(Zone::with(['roles', 'passes'])->get()),
        ]);
    }

    public function show(Zone $zone)
    {
        $zone->load(['roles', 'passes']);

        return response()->json([
            'zone' => new ZoneResource($zone),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'name'            => ['required', 'string', 'min:2', 'max:100'],
            'description'     => ['nullable', 'string', 'max:255'],
            'type'            => ['nullable', Rule::in(['pedestrian', 'vehicular'])],
            'enabled'         => ['nullable', 'boolean'],
            'role_ids'        => ['nullable', 'array'],
            'role_ids.*'      => ['integer', Rule::exists('roles', 'id')],
        ]);

        $zone = Zone::create([
            'organization_id' => $data['organization_id'],
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'type'            => $data['type'] ?? null,
            'enabled'         => $data['enabled'] ?? true,
        ]);

        $zone->roles()->sync($data['role_ids'] ?? []);

        return response()->json([
            'zone' => new ZoneResource($zone->fresh()->load(['roles', 'passes'])),
        ], 201);
    }

    public function update(Request $request, Zone $zone)
    {
        $data = $request->validate([
            'organization_id' => ['sometimes', 'integer', Rule::exists('organizations', 'id')],
            'name'            => ['sometimes', 'string', 'min:2', 'max:100'],
            'description'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'type'            => ['sometimes', 'nullable', Rule::in(['pedestrian', 'vehicular'])],
            'enabled'         => ['sometimes', 'boolean'],
            'role_ids'        => ['sometimes', 'array'],
            'role_ids.*'      => ['integer', Rule::exists('roles', 'id')],
        ]);

        $zoneData = collect($data)->except('role_ids')->toArray();

        $zone->update($zoneData);

        if (array_key_exists('role_ids', $data)) {
            $zone->roles()->sync($data['role_ids']);
        }

        return response()->json([
            'zone' => new ZoneResource($zone->fresh()->load(['roles', 'passes'])),
        ]);
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();

        return response()->json([
            'message' => 'Zone deleted',
        ]);
    }
}