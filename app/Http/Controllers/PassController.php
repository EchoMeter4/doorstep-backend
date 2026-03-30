<?php

namespace App\Http\Controllers;

use App\Models\Pass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\PassResource;

class PassController extends Controller
{
    public function index()
    {
        return response()->json([
            'passes' => PassResource::collection(Pass::with(['visitor', 'zones'])->get()),
        ]);
    }

    public function show(Pass $pass)
    {
        return response()->json([
            'pass' => new PassResource($pass->load(['visitor', 'zones'])),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'visitor_id'  => ['required', 'integer', Rule::exists('visitors', 'id')->whereNull('deleted_at')],
            'valid_from'  => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:valid_from'],
            'status'      => ['nullable', Rule::in(['active', 'expired', 'revoked'])],
            'zone_ids'    => ['nullable', 'array'],
            'zone_ids.*'  => ['integer', Rule::exists('zones', 'id')],
        ]);

        $data['created_by'] = auth()->id();

        $pass = Pass::create($data);

        if ($request->has('zone_ids')) {
            $pass->zones()->sync($data['zone_ids'] ?? []);
        }

        return response()->json([
            'pass' => new PassResource($pass->load(['visitor', 'zones'])),
        ], 201);
    }

    public function update(Request $request, Pass $pass)
    {
        $data = $request->validate([
            'visitor_id'  => ['sometimes', 'integer', Rule::exists('visitors', 'id')->whereNull('deleted_at')],
            'valid_from'  => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'date', 'after:valid_from'],
            'status'      => ['sometimes', 'nullable', Rule::in(['active', 'expired', 'revoked'])],
            'zone_ids'    => ['sometimes', 'nullable', 'array'],
            'zone_ids.*'  => ['integer', Rule::exists('zones', 'id')],
        ]);

        $pass->update($data);

        if ($request->has('zone_ids')) {
            $pass->zones()->sync($data['zone_ids'] ?? []);
        }

        return response()->json([
            'pass' => new PassResource($pass->fresh()->load(['visitor', 'zones'])),
        ]);
    }

    public function destroy(Pass $pass)
    {
        $pass->delete();

        return response()->json(['message' => 'Pass deleted']);
    }
}
