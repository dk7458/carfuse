<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use App\Services\AuditService;
use Illuminate\Support\Facades\Hash;
use App\Services\AuthService;
use App\Helpers\JsonResponse;
use App\Helpers\TokenValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * AdminController - Handles admin user management and dashboard operations.
 */
class AdminController extends Controller
{
    private AuditService $auditService;
    private ResponseFactoryInterface $responseFactory;
    private LoggerInterface $logger;

    public function __construct(
        AuditService $auditService,
        ResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->auditService = $auditService;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * Create standardized PSR-7 JSON response
     */
    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * ✅ Get a paginated list of all users with their roles.
     */
    public function getAllUsers(): ResponseInterface
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $users = User::with('roles')->latest()->paginate(10);
        
        $this->auditService->logEvent(
            'user_list_viewed',
            'Admin viewed user list',
            ['admin_id' => $admin->id, 'page' => $users->currentPage()],
            $admin->id,
            null,
            'admin'
        );
        
        return $this->jsonResponse([
            'status' => 'success', 
            'message' => 'User list retrieved successfully', 
            'data' => $users
        ]);
    }

    /**
     * ✅ Update a user's role.
     */
    public function updateUserRole($userId): ResponseInterface
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        // Replace Laravel validation with native checks.
        $role = $_POST['role'] ?? '';
        $allowedRoles = ['user', 'admin', 'manager'];
        if (!$role || !in_array($role, $allowedRoles)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid role'
            ], 400);
        }
        
        $user = User::findOrFail($userId);
        $oldRole = $user->role;
        $user->update(['role' => $role]);
        
        $this->auditService->logEvent(
            'user_role_updated',
            "User role updated from {$oldRole} to {$role}",
            [
                'user_id' => $userId,
                'old_role' => $oldRole,
                'new_role' => $role,
                'admin_id' => $admin->id
            ],
            $admin->id,
            null,
            'admin'
        );
        
        return $this->jsonResponse([
            'status' => 'success',
            'message' => 'User role updated successfully'
        ]);
    }

    /**
     * ✅ Delete a user (Soft delete).
     */
    public function deleteUser($userId): ResponseInterface
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $user = User::findOrFail($userId);
        $userEmail = $user->email; // Save for audit log
        $user->delete();
        
        $this->auditService->logEvent(
            'user_deleted',
            "User {$userEmail} was deleted",
            [
                'user_id' => $userId,
                'user_email' => $userEmail,
                'admin_id' => $admin->id
            ],
            $admin->id,
            null,
            'admin'
        );
        
        return $this->jsonResponse([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * ✅ Fetch admin dashboard statistics.
     */
    public function getDashboardData(): ResponseInterface
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $dashboardData = [
            'total_users' => User::count(),
            'total_bookings' => Booking::count(),
            'total_revenue' => Payment::sum('amount'),
            'latest_users' => User::with('roles')->latest()->limit(5)->get(),
            'latest_transactions' => TransactionLog::latest()->limit(5)->get(),
        ];
        
        $this->auditService->logEvent(
            'dashboard_viewed',
            'Admin viewed dashboard',
            ['admin_id' => $admin->id],
            $admin->id,
            null,
            'admin'
        );
        
        return $this->jsonResponse([
            'status' => 'success',
            'message' => 'Dashboard data retrieved successfully',
            'data' => $dashboardData
        ]);
    }

    /**
     * ✅ Create a new admin user.
     */
    public function createAdmin(): ResponseInterface
    {
        $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$admin || !$admin->isAdmin()) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        // Use native PHP POST handling.
        $data = $_POST;
        // Basic native validation.
        if (!isset($data['name'], $data['email'], $data['password']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            strlen($data['password']) < 8
        ) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid input'
            ], 400);
        }

        $newAdmin = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin'
        ]);
        
        $this->auditService->logEvent(
            'admin_created',
            "New admin user created: {$newAdmin->email}",
            [
                'created_by' => $admin->id,
                'new_admin_id' => $newAdmin->id,
                'new_admin_email' => $newAdmin->email
            ],
            $admin->id,
            null,
            'admin'
        );
        
        return $this->jsonResponse([
            'status' => 'success',
            'message' => 'Admin created successfully',
            'data' => $newAdmin
        ], 201);
    }
}
