<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'user_type_id'     => ['nullable', 'integer', Rule::exists('user_types', 'id')],
            'name'             => ['required', 'string', 'min:2', 'max:100'],
            'middle_name'      => ['nullable', 'string', 'min:2', 'max:100'],
            'first_last_name'  => ['nullable', 'string', 'min:2', 'max:100'],
            'second_last_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'email'            => ['required', 'email', Rule::unique(User::class, 'email')->whereNull('deleted_at')],
            'password'         => [
                'required',
                Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        $user = User::create($data);

        return response()->json([
            'user' => new UserResource($user->load('userType')),
        ], 201);
    }

    // Session-based login (web)
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource(Auth::user()->load('userType')),
        ]);
    }

    // Token-based login (API)
    public function tokenLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !\Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'       => new UserResource($user->load('userType')),
            'token'      => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke the current Sanctum token (API clients)
        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Clear session (web clients)
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
