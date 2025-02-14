<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // Use Laravel's auth guard to get the authenticated user
        $user = Auth::guard('api')->check() ? Auth::guard('api')->user() : Auth::user();

        if (!$user) {
            Log::channel('security')->warning('Unauthorized access attempt: No authenticated user found.');
            abort(401, 'Unauthorized');
        }

        // If "admin" is required, use Gate for role-based access
        if (in_array('admin', $guards) && !Gate::allows('admin-action')) {
            Log::channel('security')->warning("User {$user->id} attempted admin access without privileges.");
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
