<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Standard Login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid security identity credentials provided.'
            ], 401); // ✅ Changed 41 to 401
        }

        $role = $user->clearance_level ?? 'foundation';
        $token = $user->createToken('academia_vault_session', [$role])->plainTextToken;

        return response()->json([
            'status' => 'authenticated',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'clearance_level' => $role
            ]
        ], 200);
    }

    /**
     * Biometric Registration
     */
    public function biometricRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'university_id' => 'required|string|unique:users,university_id',
            'department' => 'required|string',
            'sex' => 'required|string',
            'age' => 'required|integer',
            'biometric_mesh' => 'required|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'university_id' => $validated['university_id'],
            'department' => $validated['department'],
            'sex' => $validated['sex'],
            'age' => $validated['age'],
            'biometric_mesh' => $validated['biometric_mesh'],
            'clearance_level' => 'foundation',
        ]);

        $token = $user->createToken('academia_vault_session', ['foundation'])->plainTextToken;

        return response()->json([
            'status' => 'registered',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'clearance_level' => $user->clearance_level
            ]
        ], 201);
    }

    /**
     * Revoke Token / Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Session token revoked cleanly.'
        ], 200);
    }
}