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
    public function me()
    {
        return response()->json([
            'user' => new UserResource(Auth::user()->load('userType')),
        ]);
    }

    public function update(Request $request)
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

    public function closeAccount(Request $request)
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
