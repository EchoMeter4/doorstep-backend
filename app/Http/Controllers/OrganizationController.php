<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use App\Http\Resources\OrganizationResource;

class OrganizationController extends Controller
{
    public function index()
    {
        return response()->json([
            'organizations' => OrganizationResource::collection(Organization::all()),
        ]);
    }

    public function show(Organization $organization)
    {
        return response()->json([
            'organization' => new OrganizationResource($organization),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
        ]);

        $organization = Organization::create($data);

        return response()->json([
            'organization' => new OrganizationResource($organization),
        ], 201);
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:150'],
        ]);

        $organization->update($data);

        return response()->json([
            'organization' => new OrganizationResource($organization->fresh()),
        ]);
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return response()->json(['message' => 'Organization deleted']);
    }
}
