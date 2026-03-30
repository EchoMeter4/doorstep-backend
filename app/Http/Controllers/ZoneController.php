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
            'zones' => ZoneResource::collection(Zone::all()),
        ]);
    }

    public function show(Zone $zone)
    {
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
            'type'            => ['nullable', Rule::in(['pedestrian', 'vehicular', 'mixed'])],
            'enabled'         => ['nullable', 'boolean'],
        ]);

        $zone = Zone::create($data);

        return response()->json([
            'zone' => new ZoneResource($zone->fresh()),
        ], 201);
    }

    public function update(Request $request, Zone $zone)
    {
        $data = $request->validate([
            'organization_id' => ['sometimes', 'integer', Rule::exists('organizations', 'id')],
            'name'            => ['sometimes', 'string', 'min:2', 'max:100'],
            'description'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'type'            => ['sometimes', Rule::in(['pedestrian', 'vehicular', 'mixed'])],
            'enabled'         => ['sometimes', 'boolean'],
        ]);

        $zone->update($data);

        return response()->json([
            'zone' => new ZoneResource($zone->fresh()),
        ]);
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();

        return response()->json(['message' => 'Zone deleted']);
    }
}
