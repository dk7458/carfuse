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
        $users = User::with('roles')->latest()->paginate(10);
        return $this->jsonResponse(['success' => true, 'data' => $users]);
    }

    /**
     * ✅ Update a user's role.
     */
    public function updateUserRole($userId)
    {
        // Replace Laravel validation with native checks.
        $role = $_POST['role'] ?? '';
        $allowedRoles = ['user', 'admin', 'manager'];
        if (!$role || !in_array($role, $allowedRoles)) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid role'], 400);
        }
        $user = User::findOrFail($userId);
        $user->update(['role' => $role]);
        $this->logger->info("AUDIT: User role updated: {$user->email} to {$role}");
        return $this->jsonResponse(['success' => true, 'message' => 'User role updated successfully']);
    }

    /**
     * ✅ Delete a user (Soft delete).
     */
    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        $this->logger->info("AUDIT: User deleted: {$user->email}");
        return $this->jsonResponse(['success' => true, 'message' => 'User deleted successfully']);
    }

    /**
     * ✅ Fetch admin dashboard statistics.
     */
    public function getDashboardData()
    {
        $dashboardData = Cache::remember('admin_dashboard', 60, function () {
            return [
                'total_users' => User::count(),
                'total_bookings' => Booking::count(),
                'total_revenue' => Payment::sum('amount'),
                'latest_users' => User::with('roles')->latest()->limit(5)->get(),
                'latest_transactions' => TransactionLog::latest()->limit(5)->get(),
            ];
        });
        return $this->jsonResponse(['success' => true, 'data' => $dashboardData]);
    }

    /**
     * ✅ Create a new admin user.
     */
    public function createAdmin()
    {
        // Use native PHP POST handling.
        $data = $_POST;
        // Basic native validation.
        if (!isset($data['name'], $data['email'], $data['password']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            strlen($data['password']) < 8
        ) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
        }

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin'
        ]);
        $this->logger->info("AUDIT: New admin created: {$admin->email}");
        return $this->jsonResponse(['success' => true, 'message' => 'Admin created successfully', 'admin' => $admin], 201);
    }

    private function jsonResponse(array $data, int $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
