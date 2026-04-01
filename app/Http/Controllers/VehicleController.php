<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\VehicleResource;

class VehicleController extends Controller
{
    public function index()
    {
        return response()->json([
            'vehicles' => VehicleResource::collection(Vehicle::with('users')->get()),
        ]);
    }

    public function show(Vehicle $vehicle)
    {
        return response()->json([
            'vehicle' => new VehicleResource($vehicle->load('users')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->whereNull('deleted_at')],
            'make'         => ['nullable', 'string', 'max:100'],
            'model'        => ['nullable', 'string', 'max:100'],
            'year'         => ['nullable', 'integer'],
            'color'        => ['nullable', 'string', 'max:50'],
            'type'         => ['nullable', 'string', 'max:50'],
            'user_ids'     => ['nullable', 'array'],
            'user_ids.*'   => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]);

        $vehicle = Vehicle::create($data);

        if ($request->has('user_ids')) {
            $vehicle->users()->sync($data['user_ids'] ?? []);
        }

        return response()->json([
            'vehicle' => new VehicleResource($vehicle->load('users')),
        ], 201);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'plate_number' => ['sometimes', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($vehicle->id)->whereNull('deleted_at')],
            'make'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'model'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'year'         => ['sometimes', 'nullable', 'integer'],
            'color'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'type'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'user_ids'     => ['sometimes', 'nullable', 'array'],
            'user_ids.*'   => ['integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]);

        $vehicle->update($data);

        if ($request->has('user_ids')) {
            $vehicle->users()->sync($data['user_ids'] ?? []);
        }

        return response()->json([
            'vehicle' => new VehicleResource($vehicle->fresh()->load('users')),
        ]);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted']);
    }
}
