<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    /**
     * Handle the secure biometric and structural registration package stream.
     */
    public function register(Request $request)
    {
        // 1. Rigorously validate the incoming text parameters
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'university_id' => 'required|string|unique:users',
            'department' => 'required|string',
            'sex' => 'required|string|max:10',
            'age' => 'required|integer',
            'biometric_mesh' => 'required|array' // Array of 468 floating mathematical coordinate vectors
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Anti-Spoofing & Duplicate Prevention Engine
        // Pull all existing face signatures from the system to compare geometric distance ratios
        $existingUsers = User::whereNotNull('biometric_signature')->get();
        $incomingMesh = $request->input('biometric_mesh');

        foreach ($existingUsers as $existingUser) {
            $savedMesh = json_decode($existingUser->biometric_signature, true);
            
            // Calculate mathematical similarity score between the two face vectors
            $similarity = $this->calculateMeshSimilarity($incomingMesh, $savedMesh);
            
            // If the geometric face match ratio exceeds 96%, flag a fraudulent double-registration attempt
            if ($similarity > 0.96) {
                return response()->json([
                    'status' => 'security_lockout',
                    'message' => 'Biometric conflict detected: This physical facial matrix structure matches an existing passport identity system user.'
                ], 409);
            }
        }

        // 3. Persist the records down to the database matrix layer
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'university_id' => $request->university_id,
            'department' => $request->department,
            'sex' => $request->sex,
            'age' => $request->age,
        ]);

        // Save the raw vector coordinates safely inside the User structure
        $user->biometric_signature = json_encode($incomingMesh);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Identity authenticated. Vault Passport created.',
            'data' => [
                'vault_id' => $user->vault_id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ], 201);
    }

    /**
     * Compute Cosine Similarity between two multi-dimensional facial landmark matrices.
     */
    private function calculateMeshSimilarity(array $meshA, array $meshB): float
    {
        // Simple dot product variance checking over vector coordinates
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        
        $limit = min(count($meshA), count($meshB));
        for ($i = 0; $i < $limit; $i++) {
            $valA = (float)$meshA[$i];
            $valB = (float)$meshB[$i];
            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }
        
        if ($normA == 0 || $normB == 0) return 0.0;
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}