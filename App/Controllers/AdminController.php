<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Psr\Log\LoggerInterface;
use App\Services\AuthService;
use App\Helpers\JsonResponse;
use App\Helpers\TokenValidator;

/**
 * AdminController - Handles admin user management and dashboard operations.
 */
class AdminController extends Controller
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $adminLogger)
    {
        parent::__construct($adminLogger);
        $this->logger = $adminLogger;
    }

    /**
     * ✅ Get a paginated list of all users with their roles.
     */
    public function getAllUsers()
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return JsonResponse::unauthorized('Invalid token or insufficient permissions');
        }

        $users = User::with('roles')->latest()->paginate(10);
        return JsonResponse::success('User list retrieved successfully', $users);
    }

    /**
     * ✅ Update a user's role.
     */
    public function updateUserRole($userId)
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return JsonResponse::unauthorized('Invalid token or insufficient permissions');
        }

        // Replace Laravel validation with native checks.
        $role = $_POST['role'] ?? '';
        $allowedRoles = ['user', 'admin', 'manager'];
        if (!$role || !in_array($role, $allowedRoles)) {
            return JsonResponse::error('Invalid role', 400);
        }
        $user = User::findOrFail($userId);
        $user->update(['role' => $role]);
        $this->logger->info("AUDIT: User role updated: {$user->email} to {$role}");
        return JsonResponse::success('User role updated successfully');
    }

    /**
     * ✅ Delete a user (Soft delete).
     */
    public function deleteUser($userId)
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return JsonResponse::unauthorized('Invalid token or insufficient permissions');
        }

        $user = User::findOrFail($userId);
        $user->delete();
        $this->logger->info("AUDIT: User deleted: {$user->email}");
        return JsonResponse::success('User deleted successfully');
    }

    /**
     * ✅ Fetch admin dashboard statistics.
     */
    public function getDashboardData()
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return JsonResponse::unauthorized('Invalid token or insufficient permissions');
        }

        $dashboardData = Cache::remember('admin_dashboard', 60, function () {
            return [
                'total_users' => User::count(),
                'total_bookings' => Booking::count(),
                'total_revenue' => Payment::sum('amount'),
                'latest_users' => User::with('roles')->latest()->limit(5)->get(),
                'latest_transactions' => TransactionLog::latest()->limit(5)->get(),
            ];
        });
        return JsonResponse::success('Dashboard data retrieved successfully', $dashboardData);
    }

    /**
     * ✅ Create a new admin user.
     */
    public function createAdmin()
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return JsonResponse::unauthorized('Invalid token or insufficient permissions');
        }

        // Use native PHP POST handling.
        $data = $_POST;
        // Basic native validation.
        if (!isset($data['name'], $data['email'], $data['password']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            strlen($data['password']) < 8
        ) {
            return JsonResponse::error('Invalid input', 400);
        }

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin'
        ]);
        $this->logger->info("AUDIT: New admin created: {$admin->email}");
        return JsonResponse::success('Admin created successfully', $admin, 201);
    }
}
