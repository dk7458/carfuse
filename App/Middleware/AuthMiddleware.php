<?php

namespace App\Middleware;

use App\Services\Auth\TokenService;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    protected $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token || !$this->tokenService->verifyToken($token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
