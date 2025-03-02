<?php

namespace App\Controllers;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
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
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        AuditService $auditService,
        ResponseFactoryInterface $responseFactory,
    ) {
        parent::__construct($logger);
        $this->auditService = $auditService;
        $this->responseFactory = $responseFactory;
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
     * Validate admin token and return admin data
     */
    protected function validateAdmin(): ?array
    {
        $this->logger->debug("Validating admin token using secure database");
        $authHeader = $this->request->getHeader('Authorization')[0] ?? '';
        $token = preg_replace('/^Bearer\s+/', '', $authHeader);
        
        if (empty($token)) {
            $this->logger->info("No authorization token provided");
            return null;
        }
        
        // Validate token and fetch admin details - using secure database
        $adminData = DatabaseHelper::select(
            "SELECT id, email, role FROM admins WHERE token = ? AND token_expiry > NOW()", 
            [$token],
            true // Explicitly using secure database
        );
            
        if (empty($adminData) || $adminData[0]['role'] !== 'admin') {
            $this->logger->info("Invalid admin token or insufficient permissions");
            return null;
        }
        
        $this->logger->info("Admin validated successfully", ['admin_id' => $adminData[0]['id']]);
        return $adminData[0];
    }

    /**
     * ✅ Get a paginated list of all users with their roles.
     */
    public function getAllUsers(): ResponseInterface
    {
        $admin = $this->validateAdmin();
        if (!$admin) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $this->logger->debug("Fetching users with pagination using application database");
        
        // Get pagination parameters
        $page = (int) ($this->request->getQueryParams()['page'] ?? 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        // Get users with pagination - using application database
        $users = DatabaseHelper::select(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             ORDER BY u.created_at DESC 
             LIMIT ? OFFSET ?",
            [$perPage, $offset],
            false // Explicitly using application database
        );
        
        // Get total count for pagination
        $totalUsers = DatabaseHelper::select(
            "SELECT COUNT(*) as count FROM users", 
            [],
            false // Explicitly using application database
        )[0]['count'];
        
        $this->auditService->logEvent(
            'user_list_viewed',
            'Admin viewed user list',
            ['admin_id' => $admin['id'], 'page' => $page],
            $admin['id'],
            null,
            'admin'
        );
        
        return $this->jsonResponse([
            'status' => 'success', 
            'message' => 'User list retrieved successfully', 
            'data' => [
                'users' => $users,
                'pagination' => [
                    'total' => $totalUsers,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($totalUsers / $perPage)
                ]
            ]
        ]);
    }

    /**
     * ✅ Update a user's role.
     */
    public function updateUserRole($userId): ResponseInterface
    {
        $admin = $this->validateAdmin();
        if (!$admin) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $data = $this->request->getParsedBody();
        $role = $data['role'] ?? '';
        $allowedRoles = ['user', 'admin', 'manager'];
        if (!$role || !in_array($role, $allowedRoles)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid role'
            ], 400);
        }
        
        $this->logger->debug("Fetching user data for role update using application database", [
            'user_id' => $userId
        ]);
        
        // Get user and their current role - using application database
        $user = DatabaseHelper::select(
            "SELECT id, role FROM users WHERE id = ?", 
            [(int)$userId],
            false // Explicitly using application database
        );
        
        if (empty($user)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        
        $oldRole = $user[0]['role'];
        
        $this->logger->debug("Updating user role using application database", [
            'user_id' => $userId,
            'old_role' => $oldRole,
            'new_role' => $role
        ]);
        
        // Update role - using application database
        DatabaseHelper::update(
            "users", 
            ["role" => $role], 
            ["id" => (int)$userId],
            false // Explicitly using application database
        );
        
        $this->auditService->logEvent(
            'user_role_updated',
            "User role updated from {$oldRole} to {$role}",
            [
                'user_id' => $userId,
                'old_role' => $oldRole,
                'new_role' => $role,
                'admin_id' => $admin['id']
            ],
            $admin['id'],
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
        $admin = $this->validateAdmin();
        if (!$admin) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $this->logger->debug("Fetching user data for deletion using application database", [
            'user_id' => $userId
        ]);
        
        // Get user data for audit log - using application database
        $user = DatabaseHelper::select(
            "SELECT id, email, role FROM users WHERE id = ?", 
            [(int)$userId],
            false // Explicitly using application database
        );
        
        if (empty($user)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
        
        $userEmail = $user[0]['email'];
        $userRole = $user[0]['role'];
        
        // Check if user is a super admin
        if ($userRole === 'super_admin') {
            $this->logger->info("Attempted to delete a super_admin account", [
                'user_id' => $userId,
                'admin_id' => $admin['id']
            ]);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Super admins cannot be deleted'
            ], 403);
        }
        
        $this->logger->debug("Soft deleting user using application database", [
            'user_id' => $userId,
            'user_email' => $userEmail
        ]);
        
        // Soft delete by setting deleted_at timestamp - using application database
        DatabaseHelper::update(
            "users", 
            ["deleted_at" => date('Y-m-d H:i:s')], 
            ["id" => (int)$userId],
            false // Explicitly using application database
        );
        
        $this->auditService->logEvent(
            'user_deleted',
            "User {$userEmail} was deleted",
            [
                'user_id' => $userId,
                'user_email' => $userEmail,
                'admin_id' => $admin['id']
            ],
            $admin['id'],
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
        $admin = $this->validateAdmin();
        if (!$admin) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $this->logger->debug("Fetching dashboard statistics using application database");
        
        // Get total users count - using application database
        $totalUsers = DatabaseHelper::select(
            "SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL", 
            [],
            false // Explicitly using application database
        )[0]['count'];
        
        // Get total bookings count - using application database
        $totalBookings = DatabaseHelper::select(
            "SELECT COUNT(*) as count FROM bookings", 
            [],
            false // Explicitly using application database
        )[0]['count'];
        
        // Get total revenue - using application database
        $totalRevenue = DatabaseHelper::select(
            "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'", 
            [],
            false // Explicitly using application database
        )[0]['total'] ?? 0;
        
        // Get latest 5 users - using application database
        $latestUsers = DatabaseHelper::select(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.deleted_at IS NULL 
             ORDER BY u.created_at DESC 
             LIMIT 5",
            [],
            false // Explicitly using application database
        );
        
        // Get latest 5 transactions - using application database
        $latestTransactions = DatabaseHelper::select(
            "SELECT * FROM transaction_logs ORDER BY created_at DESC LIMIT 5",
            [],
            false // Explicitly using application database
        );
        
        $dashboardData = [
            'total_users' => $totalUsers,
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'latest_users' => $latestUsers,
            'latest_transactions' => $latestTransactions,
        ];
        
        $this->auditService->logEvent(
            'dashboard_viewed',
            'Admin viewed dashboard',
            ['admin_id' => $admin['id']],
            $admin['id'],
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
        $admin = $this->validateAdmin();
        if (!$admin) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid token or insufficient permissions'
            ], 401);
        }

        $data = $this->request->getParsedBody();
        
        // Validate input
        if (!isset($data['name'], $data['email'], $data['password']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            strlen($data['password']) < 8
        ) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Invalid input. Email must be valid and password must be at least 8 characters'
            ], 400);
        }
        
        $this->logger->debug("Checking for existing admin email using secure database", [
            'email' => $data['email']
        ]);
        
        // Check if email already exists - using secure database
        $existingAdmin = DatabaseHelper::select(
            "SELECT id FROM admins WHERE email = ?", 
            [$data['email']],
            true // Explicitly using secure database
        );
        
        if (!empty($existingAdmin)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Email already in use'
            ], 400);
        }
        
        // Create new admin - using secure database
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $this->logger->debug("Creating new admin user in secure database");
        
        // Insert new admin record - using secure database
        $newAdminId = DatabaseHelper::insert(
            "admins", 
            [
                "name" => $data['name'],
                "email" => $data['email'],
                "password" => $hashedPassword,
                "role" => 'admin',
                "created_at" => date('Y-m-d H:i:s')
            ],
            true // Explicitly using secure database
        );
        
        if (!$newAdminId) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to create admin user'
            ], 500);
        }
        
        $this->logger->debug("Fetching created admin details from secure database", [
            'new_admin_id' => $newAdminId
        ]);
        
        // Get created admin details for response - using secure database
        $newAdmin = DatabaseHelper::select(
            "SELECT id, name, email, role, created_at FROM admins WHERE id = ?", 
            [$newAdminId],
            true // Explicitly using secure database
        )[0];
        
        $this->auditService->logEvent(
            'admin_created',
            "New admin user created: {$data['email']}",
            [
                'created_by' => $admin['id'],
                'new_admin_id' => $newAdminId,
                'new_admin_email' => $data['email']
            ],
            $admin['id'],
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
