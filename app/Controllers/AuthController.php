<?php

namespace App\Controllers;

use Illuminate\Http\Request;
use App\Services\Auth\TokenService;

class AuthController extends Controller
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function login(Request $request)
    {
        // ...existing code for validating user credentials...
        $token = $this->tokenService->generateToken($user);
        $refreshToken = $this->tokenService->generateRefreshToken($user);

        return response()->json([
            'access_token' => $token,
            'refresh_token' => $refreshToken
        ]);
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        $newToken = $this->tokenService->refreshAccessToken($refreshToken);

        if ($newToken) {
            return response()->json(['access_token' => $newToken]);
        }

        return response()->json(['error' => 'Invalid refresh token'], 401);
    }

    public function logout(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        $this->tokenService->revokeToken($refreshToken);

        return response()->json(['message' => 'Logged out successfully']);
    }
}
