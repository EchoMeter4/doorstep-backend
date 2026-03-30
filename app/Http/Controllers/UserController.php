<?php

namespace App\Http\Controllers;

use Auth;
use Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => UserResource::collection(User::with('userType')->get()),
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => new UserResource($user->load('userType')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_type_id'     => ['nullable', 'integer', Rule::exists('user_types', 'id')],
            'name'             => ['required', 'string', 'min:2', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'min:2', 'max:100'],
            'first_last_name'  => ['required', 'string', 'min:2', 'max:100'],
            'second_last_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'email'            => ['required', 'email', Rule::unique(User::class, 'email')->whereNull('deleted_at')],
            'password'         => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            'enabled'          => ['nullable', 'boolean'],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json([
            'user' => new UserResource($user->load('userType')),
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'user_type_id'     => ['sometimes', 'integer', Rule::exists('user_types', 'id')],
            'name'             => ['sometimes', 'string', 'min:2', 'max:100'],
            'middle_name'      => ['sometimes', 'nullable', 'string', 'min:2', 'max:100'],
            'first_last_name'  => ['sometimes', 'string', 'min:2', 'max:100'],
            'second_last_name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:100'],
            'email'            => ['sometimes', 'email', Rule::unique(User::class, 'email')->ignore($user->id)->whereNull('deleted_at')],
            'password'         => ['sometimes', Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            'enabled'          => ['sometimes', 'boolean'],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'user' => new UserResource($user->fresh()->load('userType')),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    public function me()
    {
        return response()->json([
            'user' => new UserResource(Auth::user()->load('userType')),
        ]);
    }

    public function updateMe(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'user_type_id' => ['sometimes', 'integer', Rule::exists('user_types', 'id')],
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:100'],
            'first_last_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'second_last_name' => ['sometimes', 'nullable', 'string', 'min:2', 'max:100'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique(User::class, 'email')
                    ->ignore($user->id)
                    ->whereNull('deleted_at'),
            ],
            'password' => [
                'sometimes',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'user' => new UserResource($user->fresh()->load('userType')),
        ]);
    }

    public function deleteMe(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Account closed',
        ]);
    }
}
