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
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(['users' => $users]);
        exit;
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
            http_response_code(400);
            echo json_encode(['message' => 'Invalid role']);
            exit;
        }
        $user = User::findOrFail($userId);
        $user->update(['role' => $role]);
        $this->logger->info("AUDIT: User role updated: {$user->email} to {$role}");
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(['message' => 'User role updated successfully']);
        exit;
    }

    /**
     * ✅ Delete a user (Soft delete).
     */
    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        $this->logger->info("AUDIT: User deleted: {$user->email}");
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode(['message' => 'User deleted successfully']);
        exit;
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
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode($dashboardData);
        exit;
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
            http_response_code(400);
            echo json_encode(['message' => 'Invalid input']);
            exit;
        }

        $admin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin'
        ]);
        $this->logger->info("AUDIT: New admin created: {$admin->email}");
        header('Content-Type: application/json');
        http_response_code(201);
        echo json_encode(['message' => 'Admin created successfully', 'admin' => $admin]);
        exit;
    }
}
