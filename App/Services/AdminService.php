<?php

namespace App\Services;

use App\Models\Admin;
use App\Services\Auth\TokenService;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class AdminService
{
    private Admin $adminModel;
    private AuditService $auditService;
    private LoggerInterface $logger;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        Admin $adminModel,
        AuditService $auditService,
        LoggerInterface $logger,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler
    ) {
        $this->adminModel = $adminModel;
        $this->auditService = $auditService;
        $this->logger = $logger;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Validate admin token and return admin data
     */
    public function validateAdmin(ServerRequestInterface $request): ?array
    {
        $this->logger->debug("Validating admin token using secure database");
        
        // Use TokenService to extract the token
        $token = $this->tokenService->extractToken($request);
        
        if (empty($token)) {
            $this->logger->info("No authorization token provided");
            return null;
        }
        
        // Validate token and fetch admin details
        $admin = $this->adminModel->findByToken($token);
            
        if (empty($admin) || $admin['role'] !== 'admin') {
            $this->logger->info("Invalid admin token or insufficient permissions");
            return null;
        }
        
        $this->logger->info("Admin validated successfully", ['admin_id' => $admin['id']]);
        return $admin;
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers(int $page, int $adminId): array
    {
        try {
            $perPage = 10;
            
            $this->logger->debug("Fetching users with pagination");
            
            $users = $this->adminModel->getPaginatedUsers($page, $perPage);
            $totalUsers = $this->adminModel->getTotalUserCount();
            
            $this->auditService->logEvent(
                'user_list_viewed',
                'Admin viewed user list',
                ['admin_id' => $adminId, 'page' => $page],
                $adminId,
                null,
                'admin'
            );
            
            return [
                'users' => $users,
                'pagination' => [
                    'total' => $totalUsers,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($totalUsers / $perPage)
                ]
            ];
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e; // Re-throw so controller can handle response
        }
    }

    /**
     * Update a user's role
     */
    public function updateUserRole(int $userId, string $role, int $adminId): bool
    {
        try {
            $this->logger->debug("Fetching user data for role update", [
                'user_id' => $userId
            ]);
            
            // Get user and their current role
            $user = $this->adminModel->getUserById($userId);
            
            if (empty($user)) {
                return false;
            }
            
            $oldRole = $user['role'];
            
            $this->logger->debug("Updating user role", [
                'user_id' => $userId,
                'old_role' => $oldRole,
                'new_role' => $role
            ]);
            
            // Update role
            $result = $this->adminModel->updateUserRole($userId, $role);
            
            if ($result) {
                $this->auditService->logEvent(
                    'user_role_updated',
                    "User role updated from {$oldRole} to {$role}",
                    [
                        'user_id' => $userId,
                        'old_role' => $oldRole,
                        'new_role' => $role,
                        'admin_id' => $adminId
                    ],
                    $adminId,
                    null,
                    'admin'
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Delete a user (Soft delete)
     */
    public function deleteUser(int $userId, int $adminId): ?array
    {
        try {
            $this->logger->debug("Fetching user data for deletion", [
                'user_id' => $userId
            ]);
            
            // Get user data for audit log
            $user = $this->adminModel->getUserById($userId);
            
            if (empty($user)) {
                return null;
            }
            
            $userEmail = $user['email'];
            $userRole = $user['role'];
            
            // Check if user is a super admin
            if ($userRole === 'super_admin') {
                $this->logger->info("Attempted to delete a super_admin account", [
                    'user_id' => $userId,
                    'admin_id' => $adminId
                ]);
                return ['error' => 'Super admins cannot be deleted'];
            }
            
            $this->logger->debug("Soft deleting user", [
                'user_id' => $userId,
                'user_email' => $userEmail
            ]);
            
            // Soft delete by setting deleted_at timestamp
            $result = $this->adminModel->softDeleteUser($userId);
            
            if ($result) {
                $this->auditService->logEvent(
                    'user_deleted',
                    "User {$userEmail} was deleted",
                    [
                        'user_id' => $userId,
                        'user_email' => $userEmail,
                        'admin_id' => $adminId
                    ],
                    $adminId,
                    null,
                    'admin'
                );
            }
            
            return $result ? $user : null;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardData(int $adminId): array
    {
        try {
            $this->logger->debug("Fetching dashboard statistics");
            
            $dashboardData = $this->adminModel->getDashboardStatistics();
            
            $this->auditService->logEvent(
                'dashboard_viewed',
                'Admin viewed dashboard',
                ['admin_id' => $adminId],
                $adminId,
                null,
                'admin'
            );
            
            return $dashboardData;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Create a new admin user
     */
    public function createAdmin(array $data, int $adminId): ?array
    {
        try {
            $this->logger->debug("Checking for existing admin email", [
                'email' => $data['email']
            ]);
            
            // Check if email already exists
            $existingAdmin = $this->adminModel->findByEmail($data['email']);
            
            if ($existingAdmin) {
                return null;
            }
            
            // Create new admin
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $this->logger->debug("Creating new admin user");
            
            // Insert new admin record
            $newAdminId = $this->adminModel->create([
                "name" => $data['name'],
                "email" => $data['email'],
                "password" => $hashedPassword,
                "role" => 'admin',
                "created_at" => date('Y-m-d H:i:s')
            ]);
            
            if (!$newAdminId) {
                return null;
            }
            
            $this->logger->debug("Fetching created admin details", [
                'new_admin_id' => $newAdminId
            ]);
            
            // Get created admin details for response
            $newAdmin = $this->adminModel->findById($newAdminId);
            
            $this->auditService->logEvent(
                'admin_created',
                "New admin user created: {$data['email']}",
                [
                    'created_by' => $adminId,
                    'new_admin_id' => $newAdminId,
                    'new_admin_email' => $data['email']
                ],
                $adminId,
                null,
                'admin'
            );
            
            return $newAdmin;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
}
