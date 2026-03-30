<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\CredentialResource;

class CredentialController extends Controller
{
    public function index()
    {
        return response()->json([
            'credentials' => CredentialResource::collection(Credential::with('user')->get()),
        ]);
    }

    public function show(Credential $credential)
    {
        return response()->json([
            'credential' => new CredentialResource($credential->load('user')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'         => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'credential_type' => ['required', 'string', 'max:100'],
            'credential_code' => ['required', 'string', Rule::unique('credentials', 'credential_code')->whereNull('deleted_at')],
            'is_active'       => ['nullable', 'boolean'],
            'issued_at'       => ['nullable', 'date'],
        ]);

        $credential = Credential::create($data);

        return response()->json([
            'credential' => new CredentialResource($credential->load('user')),
        ], 201);
    }

    public function update(Request $request, Credential $credential)
    {
        $data = $request->validate([
            'user_id'         => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'credential_type' => ['sometimes', 'string', 'max:100'],
            'credential_code' => ['sometimes', 'string', Rule::unique('credentials', 'credential_code')->ignore($credential->id)->whereNull('deleted_at')],
            'is_active'       => ['sometimes', 'boolean'],
            'issued_at'       => ['sometimes', 'nullable', 'date'],
        ]);

        $credential->update($data);

        return response()->json([
            'credential' => new CredentialResource($credential->fresh()->load('user')),
        ]);
    }

    public function destroy(Credential $credential)
    {
        $credential->delete();

        return response()->json(['message' => 'Credential deleted']);
    }
}
