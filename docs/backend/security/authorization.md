# Authorization System

## Overview

The Carfuse application implements a straightforward role-based access control (RBAC) system that governs what actions authenticated users can perform. Authorization is enforced through:

1. **Middleware-based authentication verification**
2. **Role checks in controllers and services**
3. **Service-level authorization logic**
4. **UI element visibility control based on user roles**

This documentation covers the authorization mechanisms and provides guidance on implementing authorization checks in new code.

## Role-Based Access Control System

The authorization system uses a hierarchical role structure stored directly in the user record. Each user is assigned a single role that determines their permissions throughout the application. Role assignments are managed by administrators through the admin interface.

### Implementation Details

- Roles are stored in the `role` field of the user record
- Role checks occur primarily at the controller/service level
- Some routes are protected by role-specific middleware
- No separate permissions table - capabilities are tied directly to roles
- Admin-specific routes use the `AdminService->validateAdmin()` method as a gatekeeper

## Available Roles and Capabilities

| Role        | Capabilities | Description |
|-------------|--------------|-------------|
| `super_admin` | All system functions<br>User management<br>System configuration<br>Cannot be deleted | Highest level of access for system owners |
| `admin` | User management<br>Booking management<br>Payment operations<br>Dashboard access<br>Audit log access | Administrative staff managing day-to-day operations |
| `manager` | Limited user management<br>Vehicle management<br>Booking approvals<br>Reports access | Staff supervising specific operational areas |
| `user` | Personal profile management<br>Vehicle bookings<br>Payment processing<br>Viewing own data | Standard customer account |
| `guest` | Public page access<br>Registration<br>Login | Unauthenticated visitor |

## Permission Checking Methodology

Permissions in Carfuse are checked through several mechanisms:

### 1. Middleware-Based Authentication Checks

`RequireAuthMiddleware` ensures a user is authenticated before accessing protected routes:

```php
// Enforces authentication but not specific roles
public function process(Request $request, RequestHandler $handler): Response
{
    $user = $request->getAttribute('user');
    
    if (!$user) {
        $this->logger->warning("Access attempt to protected route without authentication");
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => 'Authentication required',
            'status' => 401
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
    
    return $handler->handle($request);
}
```

### 2. Role-Based Access Control in Controllers

Controllers check user roles to authorize specific actions:

```php
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
        
        // Additional logic...
        if ($userRole === 'super_admin') {
            return ['error' => 'Super admins cannot be deleted'];
        }
        
        // Proceed with deletion...
    }
    catch (\Exception $e) {
        // Error handling...
    }
}
```

### 3. Service-Level Validation

Services like `AdminService` provide role validation helpers:

```php
/**
 * Validate admin token and return admin data
 */
public function validateAdmin(ServerRequestInterface $request): ?array
{
    // Extract token
    $token = $this->tokenService->extractToken($request);
    
    if (empty($token)) {
        return null;
    }
    
    // Validate token and fetch admin details
    $admin = $this->adminModel->findByToken($token);
        
    if (empty($admin) || $admin['role'] !== 'admin') {
        return null;
    }
    
    return $admin;
}
```

### 4. SecurityHelper Role Checks

The `SecurityHelper` class provides utility methods for role checking:

```php
// Get the logged-in user's role
public function getUserRole()
{
    return isset($_SESSION['user_id']) ? ($_SESSION['user_role'] ?? 'guest') : 'guest';
}
```

## How to Enforce Permissions in New Code

### 1. Using Middleware for Route Protection

When creating new routes, apply the appropriate middleware stack:

```php
// For routes requiring authentication but no specific role
$app->get('/user/profile', UserController::class . ':viewProfile')
    ->add(RequireAuthMiddleware::class);

// For routes requiring admin role
$app->get('/admin/users', AdminController::class . ':getAllUsers')
    ->add(AdminAuthMiddleware::class);
```

### 2. Controller-Level Role Checks

Implement role checks in your controller methods:

```php
public function performAdminAction(Request $request, Response $response)
{
    $user = $request->getAttribute('user');
    
    // Check if user has admin role
    if (!$user || $user['role'] !== 'admin') {
        return $this->jsonResponse($response, [
            'error' => 'Permission denied',
            'message' => 'This action requires administrator privileges'
        ], 403);
    }
    
    // Admin-only logic here
    // ...
    
    return $this->jsonResponse($response, ['status' => 'success']);
}
```

### 3. Using Admin/Role Validation Services

For admin-specific functionality, use the AdminService:

```php
public function manageUsers(Request $request, Response $response)
{
    try {
        // Validate admin access
        $admin = $this->adminService->validateAdmin($request);
        if (!$admin) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Access denied'
            ], 403);
        }
        
        // Admin functionality here
        // ...
        
    } catch (\Exception $e) {
        // Error handling
    }
}
```

### 4. Creating Role-Specific Functionality

When implementing features with role-based differences:

```php
public function viewDashboard(Request $request, Response $response)
{
    $user = $request->getAttribute('user');
    $data = [];
    
    // Base dashboard data for all users
    $data['userStats'] = $this->userService->getUserStats($user['id']);
    
    // Add role-specific data
    if ($user['role'] === 'admin' || $user['role'] === 'manager') {
        $data['systemStats'] = $this->statsService->getSystemStats();
    }
    
    if ($user['role'] === 'admin') {
        $data['adminControls'] = $this->adminService->getAdminControlData();
    }
    
    return $this->jsonResponse($response, $data);
}
```

## Best Practices

1. **Defense in Depth**: Implement authorization checks at multiple levels (route, controller, service)

2. **Least Privilege**: Assign users the minimum role needed for their functionality

3. **Consistency**: Use established patterns for permission checking:
   ```php
   // Preferred approach for simple role checks
   if ($user['role'] !== 'admin') {
       return $this->unauthorized();
   }
   
   // For more complex authorization
   if (!$this->authorizationService->canPerformAction($user, 'delete_booking', $bookingId)) {
       return $this->forbidden();
   }
   ```

4. **Audit Trail**: Log all important authorization decisions:
   ```php
   $this->auditService->logEvent(
       'authorization',
       'Permission denied',
       [
           'user_id' => $user['id'],
           'action' => 'delete_user',
           'target_id' => $userId
       ],
       $user['id'],
       null,
       'security'
   );
   ```

5. **Centralize Logic**: Create helper methods for common authorization checks rather than duplicating logic

## Extending the Authorization System

The current authorization system is role-based. If more granular permissions are needed, consider:

1. Creating a permissions table associating roles with specific capabilities
2. Implementing a policy-based approach with dedicated authorization classes
3. Adding context-aware permission checks (e.g., resource ownership verification)
