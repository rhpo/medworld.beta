<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController
{
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Create the response with the token
        $response = response()->json([
            'message' => 'Login successful',
            'user' => $user->load(['doctor', 'patient']),
            'token' => $token,
        ], 200);

        // Add the auth token cookie
        $cookie = new Cookie(
            name: 'auth_token',
            value: $token,
            expire: time() + (60 * 60 * 24 * 7), // 7 days
            path: '/',
            domain: null,
            secure: false,
            httpOnly: true,
            raw: false,
            sameSite: 'lax'
        );

        return $response->withCookie($cookie);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone_number' => $validated['phone_number'] ?? null,
            'address' => $validated['address'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'type' => $validated['type'],
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        // Create the response with the token
        $response = response()->json([
            'message' => 'Registration successful',
            'user' => $user->load(['doctor', 'patient']),
            'token' => $token,
        ], 201);

        // Add the auth token cookie
        $cookie = new Cookie(
            name: 'auth_token',
            value: $token,
            expire: time() + (60 * 60 * 24 * 7), // 7 days
            path: '/',
            domain: null,
            secure: false,
            httpOnly: true,
            raw: false,
            sameSite: 'lax'
        );

        return $response->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful'], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['doctor', 'patient']),
        ], 200);
    }
}
