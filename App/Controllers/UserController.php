<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private UserService $userService;
    private LoggerInterface $logger;
    private Validator $validator;
    private RateLimiter $rateLimiter;
    private AuditService $auditService;
    private NotificationService $notificationService;

    public function __construct(
        LoggerInterface $logger,
        Validator $validator,
        RateLimiter $rateLimiter,
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->logger = $logger;
        $this->validator = $validator;
        $this->rateLimiter = $rateLimiter;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->userService = new UserService();
    }

    /**
     * 🔹 Update user profile
     */
    public function updateProfile()
    {
        $user = Auth::user();
        $data = request()->validate([
            'name'    => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
        ]);
        
        $user->update($data);
        Log::channel('audit')->info('User profile updated', ['user_id' => $user->id]);
        
        return response()->json(['status' => 'success', 'message' => 'Profile updated successfully'], 200);
    }

    /**
     * 🔹 Get user profile
     */
    public function getProfile()
    {
        return response()->json(Auth::user(), 200);
    }

    /**
     * 🔹 Request password reset
     */
    public function requestPasswordReset()
    {
        $email = request('email');
        if (!$email) {
            abort(400, 'Invalid input');
        }
        
        $token = Str::random(60);
        \App\Models\PasswordReset::create([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => now()->addHour(),
        ]);
        
        return response()->json(['status' => 'success', 'message' => 'Password reset requested'], 200);
    }

    /**
     * 🔹 User dashboard access
     */
    public function userDashboard()
    {
        $user = Auth::user();
        return view('dashboard.user', compact('user'));
    }
}
