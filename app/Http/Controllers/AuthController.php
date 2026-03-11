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
        $credentials = $request->validate([
            'name' => ['required', 'alpha_num'],
            'email' => ['email', 'required', Rule::unique(User::class, 'email')],
            'password' => ['string', 'required']
        ]);

        $user = User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => Hash::make($credentials['password']),
        ]);

        return response()->json([
            'usuario' => new UserResource($user),
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
            'user' => new UserResource(Auth::user())
        ]);
    }

    public function me()
    {
        return response()->json([
            'user' => new UserResource(Auth::user())
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
