<?php

namespace App\Http\Controllers;

use Auth;
use Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'user_type_id'     => ['nullable', 'integer', 'exists:user_types,id'],
            'name'             => ['required', 'string'],
            'middle_name'      => ['nullable', 'string'],
            'first_last_name'  => ['nullable', 'string'],
            'second_last_name' => ['nullable', 'string'],
            'email'            => ['email', 'required', Rule::unique(User::class, 'email')],
            'password'         => ['string', 'required'],
        ]);

        $user = User::create($data);

        return response()->json([
            'user' => new UserResource($user->load('userType')),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'email|required',
            'password' => 'string|required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(
                ['message' => 'Invalid credentials'],
                401
            );
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource(Auth::user()->load('userType'))
        ]);
    }

    public function tokenLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('userType')),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me()
    {
        return response()->json([
            'user' => new UserResource(Auth::user()->load('userType'))
        ]);
    }

    public function closeAccount()
    {
        $user = Auth::user();
        $user->delete();

        return response()->json([
            'message' => 'Account closed'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}
