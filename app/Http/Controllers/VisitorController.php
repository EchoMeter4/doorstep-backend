<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\VisitorResource;

class VisitorController extends Controller
{
    public function index()
    {
        return response()->json([
            'visitors' => VisitorResource::collection(
                Visitor::with(['organization', 'passes'])->get()
            ),
        ]);
    }

    public function show(Visitor $visitor)
    {
        return response()->json([
            'visitor' => new VisitorResource(
                $visitor->load(['organization', 'passes'])
            ),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization_id'  => ['nullable', 'integer', Rule::exists('organizations', 'id')],
            'name'             => ['required', 'string', 'min:2', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'max:100'],
            'first_last_name'  => ['required', 'string', 'min:2', 'max:100'],
            'second_last_name' => ['nullable', 'string', 'max:100'],
            'email'            => ['required', 'email', 'max:255', Rule::unique('visitors', 'email')->whereNull('deleted_at')],
            'phone'            => ['nullable', 'string', 'max:30'],
            'company'          => ['nullable', 'string', 'max:150'],
            'enabled'          => ['nullable', 'boolean'],
        ]);

        $visitor = Visitor::create($data);

        return response()->json([
            'visitor' => new VisitorResource(
                $visitor->load(['organization', 'passes'])
            ),
        ], 201);
    }

    public function update(Request $request, Visitor $visitor)
    {
        $data = $request->validate([
            'organization_id'  => ['sometimes', 'nullable', 'integer', Rule::exists('organizations', 'id')],
            'name'             => ['sometimes', 'string', 'min:2', 'max:100'],
            'middle_name'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'first_last_name'  => ['sometimes', 'string', 'min:2', 'max:100'],
            'second_last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email'            => ['sometimes', 'email', 'max:255', Rule::unique('visitors', 'email')->ignore($visitor->id)->whereNull('deleted_at')],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:30'],
            'company'          => ['sometimes', 'nullable', 'string', 'max:150'],
            'enabled'          => ['sometimes', 'boolean'],
        ]);

        $visitor->update($data);

        return response()->json([
            'visitor' => new VisitorResource(
                $visitor->fresh()->load(['organization', 'passes'])
            ),
        ]);
    }

    public function destroy(Visitor $visitor)
    {
        $visitor->delete();

        return response()->json([
            'message' => 'Visitor deleted',
        ]);
    }
}