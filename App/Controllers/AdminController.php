<?php

namespace App\Controllers;

use App\Services\AdminService;
use App\Services\AuditService;
use App\Services\Auth\TokenService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

/**
 * AdminController - Handles admin user management and dashboard operations.
 */
class AdminController extends Controller
{
    private AdminService $adminService;
    private ResponseFactoryInterface $responseFactory;
    protected LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        AdminService $adminService,
        ResponseFactoryInterface $responseFactory,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->adminService = $adminService;
        $this->responseFactory = $responseFactory;
        $this->exceptionHandler = $exceptionHandler;
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
        try {
            $admin = $this->adminService->validateAdmin($this->request);
            if (!$admin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token or insufficient permissions'
                ], 401);
            }

            // Get pagination parameters
            $page = (int) ($this->request->getQueryParams()['page'] ?? 1);
            
            $userData = $this->adminService->getAllUsers($page, $admin['id']);
            
            return $this->jsonResponse([
                'status' => 'success', 
                'message' => 'User list retrieved successfully', 
                'data' => $userData
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to retrieve users'], 500);
        }
    }

    /**
     * ✅ Update a user's role.
     */
    public function updateUserRole($userId): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
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
            
            $result = $this->adminService->updateUserRole((int)$userId, $role, $admin['id']);
            
            if (!$result) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'User role updated successfully'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to update user role'], 500);
        }
    }

    /**
     * ✅ Delete a user (Soft delete).
     */
    public function deleteUser($userId): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
            if (!$admin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token or insufficient permissions'
                ], 401);
            }
            
            $result = $this->adminService->deleteUser((int)$userId, $admin['id']);
            
            if ($result === null) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            
            if (isset($result['error'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => $result['error']
                ], 403);
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to delete user'], 500);
        }
    }

    /**
     * ✅ Fetch admin dashboard statistics.
     */
    public function getDashboardData(): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
            if (!$admin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token or insufficient permissions'
                ], 401);
            }
            
            $dashboardData = $this->adminService->getDashboardData($admin['id']);
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => $dashboardData
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to retrieve dashboard data'], 500);
        }
    }

    /**
     * ✅ Create a new admin user.
     */
    public function createAdmin(): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
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
            
            $newAdmin = $this->adminService->createAdmin($data, $admin['id']);
            
            if (!$newAdmin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Email already in use or failed to create admin'
                ], 400);
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Admin created successfully',
                'data' => $newAdmin
            ], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to create admin user'], 500);
        }
    }
}
