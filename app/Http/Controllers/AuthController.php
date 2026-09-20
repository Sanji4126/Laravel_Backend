<?php

namespace App\Http\Controllers;

use App\Models\RefreshToken;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function createRefreshToken(User $user)
    {
        $token = Str::random(100);
        RefreshToken::create([
            'user_id' => $user->user_id ?? $user->id,
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
            'revoked' => false,
        ]);

        return $token;
    }
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
            ]);
            $data['password'] = Hash::make($data['password']);
            $user = User::create($data);
            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $req)
    {
        try {
            $data = $req->validate([
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:8',
            ]);
            if (! $accessToken = auth('api')->attempt($data)) {
                return response()->json([
                    'message' => 'Invalid email or password'
                ], 401);
            }
            $user = auth('api')->user();
            $refreshToken = $this->createRefreshToken($user);
            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'user' => [
                    'id' => $user->user_id ?? $user->id,
                    'name' => $user->user_name ?? $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
