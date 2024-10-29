<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (! auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user = User::whereEmail($request->email)->first();

        return response()->json([
            'token' => $user->createToken('authToken')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();
        $token = $request->user()->createToken('authToken')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json(['token' => $token], 201);
    }
}
