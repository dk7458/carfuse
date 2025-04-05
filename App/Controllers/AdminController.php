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
    protected ExceptionHandler $exceptionHandler;

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
    public function jsonResponse(ResponseInterface $response, $data, $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
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
            $role = $this->request->getQueryParams()['role'] ?? 'all';
            $status = $this->request->getQueryParams()['status'] ?? 'all';
            $search = $this->request->getQueryParams()['search'] ?? '';
            $perPage = (int) ($this->request->getQueryParams()['per_page'] ?? 10);
            
            $userData = $this->adminService->getAllUsers($page, $admin['id'], $perPage, $role, $status, $search);
            
            // Set pagination headers for HTMX
            $response = $this->responseFactory->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Pagination', json_encode([
                    'currentPage' => $page,
                    'totalPages' => $userData['meta']['total_pages'] ?? 1,
                    'totalItems' => $userData['meta']['total'] ?? 0,
                    'perPage' => $perPage
                ]));
            
            if ($this->request->getHeaderLine('HX-Request') === 'true') {
                // If this is an HTMX request, return HTML for the table rows
                $rows = '';
                foreach ($userData['data'] as $user) {
                    // Format created_at date
                    $createdAt = date('d.m.Y H:i', strtotime($user['created_at']));
                    
                    // Get user initials for avatar
                    $initials = strtoupper(substr($user['name'], 0, 1) . substr($user['surname'] ?? '', 0, 1));
                    
                    // Set role details
                    $roleLabel = match($user['role']) {
                        'admin' => 'Administrator',
                        'manager' => 'Menedżer',
                        default => 'Użytkownik'
                    };
                    
                    $roleColorClass = match($user['role']) {
                        'admin' => 'bg-red-100 text-red-800',
                        'manager' => 'bg-blue-100 text-blue-800',
                        default => 'bg-green-100 text-green-800'
                    };
                    
                    // Set status details
                    $statusLabel = $user['active'] ? 'Aktywny' : 'Nieaktywny';
                    $statusBgClass = $user['active'] ? 'bg-green-500' : 'bg-gray-300';
                    $statusTextClass = $user['active'] ? 'text-green-600' : 'text-gray-600';
                    
                    // Use the row template and replace placeholders
                    $template = file_get_contents(BASE_PATH . '/public/views/admin/templates/user-row.php');
                    
                    // Replace all placeholders
                    $replacements = [
                        '{{id}}' => $user['id'],
                        '{{name}}' => htmlspecialchars($user['name']),
                        '{{surname}}' => htmlspecialchars($user['surname'] ?? ''),
                        '{{email}}' => htmlspecialchars($user['email']),
                        '{{initials}}' => $initials,
                        '{{roleLabel}}' => $roleLabel,
                        '{{roleColorClass}}' => $roleColorClass,
                        '{{statusLabel}}' => $statusLabel,
                        '{{statusBgClass}}' => $statusBgClass,
                        '{{statusTextClass}}' => $statusTextClass,
                        '{{active}}' => $user['active'] ? 'true' : 'false',
                        '{{created_at}}' => $createdAt
                    ];
                    
                    $rows .= str_replace(array_keys($replacements), array_values($replacements), $template);
                }
                
                $response->getBody()->write($rows ?: '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Brak użytkowników do wyświetlenia</td></tr>');
                return $response;
            }
            
            // Standard JSON response
            return $this->jsonResponse($response, [
                'status' => 'success', 
                'message' => 'User list retrieved successfully', 
                'data' => $userData['data'],
                'meta' => $userData['meta']
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to retrieve users'], 500);
        }
    }

    /**
     * ✅ Get user by ID
     */
    public function getUserById($userId): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
            if (!$admin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token or insufficient permissions'
                ], 401);
            }
            
            $user = $this->adminService->getUserById((int)$userId);
            
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            
            // Log the user view in audit logs
            $this->adminService->logAdminAction(
                $admin['id'], 
                'user_view', 
                "Admin viewed user details",
                ['user_id' => $userId]
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'User retrieved successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to retrieve user'], 500);
        }
    }

    /**
     * ✅ Create a new user
     */
    public function createUser(): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
            if (!$admin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token or insufficient permissions'
                ], 401);
            }

            $data = json_decode($this->request->getBody()->getContents(), true) ?? [];
            
            // Validate input
            if (!isset($data['name'], $data['email'], $data['password']) ||
                !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
                strlen($data['password']) < 8
            ) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Nieprawidłowe dane. Email musi być prawidłowy, a hasło musi mieć min. 8 znaków.'
                ], 400);
            }
            
            // Ensure role is valid or default to user
            $data['role'] = in_array($data['role'] ?? '', ['user', 'admin', 'manager']) ? $data['role'] : 'user';
            
            // Create user using admin service
            $result = $this->adminService->createUser($data, $admin['id']);
            
            if (!$result) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Email już istnieje w systemie lub nie można utworzyć użytkownika'
                ], 400);
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Użytkownik utworzony pomyślnie',
                'data' => $result
            ], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Nie udało się utworzyć użytkownika'], 500);
        }
    }

    /**
     * ✅ Update user details
     */
    public function updateUser($userId): ResponseInterface
    {
        try {
            $admin = $this->adminService->validateAdmin($this->request);
            if (!$admin) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token or insufficient permissions'
                ], 401);
            }

            $data = json_decode($this->request->getBody()->getContents(), true) ?? [];
            
            // Ensure sensitive fields can't be updated this way
            unset($data['email'], $data['password'], $data['id']);
            
            // Ensure role is valid if provided
            if (isset($data['role']) && !in_array($data['role'], ['user', 'admin', 'manager'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Nieprawidłowa rola użytkownika'
                ], 400);
            }
            
            // Update the user
            $result = $this->adminService->updateUser((int)$userId, $data, $admin['id']);
            
            if ($result === null) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Użytkownik nie został znaleziony'
                ], 404);
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Użytkownik zaktualizowany pomyślnie',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Nie udało się zaktualizować użytkownika'], 500);
        }
    }

    /**
     * ✅ Toggle user active status
     */
    public function toggleUserStatus($userId): ResponseInterface
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
            $active = isset($data['active']) ? (bool)$data['active'] : null;
            
            if ($active === null) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Nie podano statusu aktywności'
                ], 400);
            }
            
            $result = $this->adminService->toggleUserStatus((int)$userId, $active, $admin['id']);
            
            if ($result === null) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Użytkownik nie został znaleziony'
                ], 404);
            }
            
            // For HTMX requests, return updated status component
            if ($this->request->getHeaderLine('HX-Request') === 'true') {
                $response = $this->responseFactory->createResponse(200);
                
                // Set status details
                $statusLabel = $active ? 'Aktywny' : 'Nieaktywny';
                $statusBgClass = $active ? 'bg-green-500' : 'bg-gray-300';
                $statusTextClass = $active ? 'text-green-600' : 'text-gray-600';
                
                $html = <<<HTML
                <div class="inline-flex items-center">
                    <button @click="toggleUserStatus($userId, $active)" 
                            class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 $statusBgClass">
                        <span class="inline-block h-5 w-5 rounded-full bg-white shadow transform transition ease-in-out duration-200" style="transform: translateX(<?php echo $active ? '20px' : '0px'; ?>)"></span>
                    </button>
                    <span class="ml-2 text-sm $statusTextClass">$statusLabel</span>
                </div>
                HTML;
                
                $response->getBody()->write($html);
                return $response->withHeader('Content-Type', 'text/html');
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Status użytkownika zmieniony pomyślnie',
                'data' => ['active' => $active]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Nie udało się zmienić statusu użytkownika'], 500);
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
                    'message' => 'Nieprawidłowa rola'
                ], 400);
            }
            
            $result = $this->adminService->updateUserRole((int)$userId, $role, $admin['id']);
            
            if (!$result) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Użytkownik nie został znaleziony'
                ], 404);
            }
            
            // For HTMX requests, return updated role component
            if ($this->request->getHeaderLine('HX-Request') === 'true') {
                $response = $this->responseFactory->createResponse(200);
                
                // Set role details
                $roleLabel = match($role) {
                    'admin' => 'Administrator',
                    'manager' => 'Menedżer',
                    default => 'Użytkownik'
                };
                
                $roleColorClass = match($role) {
                    'admin' => 'bg-red-100 text-red-800',
                    'manager' => 'bg-blue-100 text-blue-800',
                    default => 'bg-green-100 text-green-800'
                };
                
                $html = <<<HTML
                <div class="inline-flex items-center">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full $roleColorClass">
                      $roleLabel
                    </span>
                    <div class="relative ml-2" x-data="{ roleDropdownOpen: false }">
                      <button @click="roleDropdownOpen = !roleDropdownOpen" 
                              type="button" 
                              class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-edit text-xs"></i>
                      </button>
                      <div x-show="roleDropdownOpen" 
                           @click.away="roleDropdownOpen = false"
                           class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-20">
                        <div class="py-1">
                          <a href="#" @click.prevent="updateUserRole($userId, 'user'); roleDropdownOpen = false" 
                             class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                             Użytkownik
                          </a>
                          <a href="#" @click.prevent="updateUserRole($userId, 'manager'); roleDropdownOpen = false" 
                             class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                             Menedżer
                          </a>
                          <a href="#" @click.prevent="updateUserRole($userId, 'admin'); roleDropdownOpen = false" 
                             class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                             Administrator
                          </a>
                        </div>
                      </div>
                    </div>
                </div>
                HTML;
                
                $response->getBody()->write($html);
                return $response->withHeader('Content-Type', 'text/html');
            }
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Rola użytkownika zaktualizowana pomyślnie'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse(['status' => 'error', 'message' => 'Nie udało się zaktualizować roli użytkownika'], 500);
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

    /**
     * ✅ Create a template for user row display
     */
    public function usersPage(): void
    {
        try {
            // Authentication check should happen at route middleware level
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                header('Location: /auth/login');
                exit;
            }
            
            // Include the users management view
            include BASE_PATH . '/public/views/admin/users.php';
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
