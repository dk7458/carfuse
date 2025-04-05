# Role-Based Access Control

*Last updated: 2023-11-15*

This document explains the Role-Based Access Control (RBAC) system in CarFuse, which manages permissions and access to resources based on user roles.

## Table of Contents
- [RBAC Architecture](#rbac-architecture)
- [Role Hierarchy](#role-hierarchy)
- [Server-Side Role Checks](#server-side-role-checks)
- [Client-Side Role Checks](#client-side-role-checks)
- [Resource Access Mapping](#resource-access-mapping)
- [HTML Data Attributes](#html-data-attributes)
- [RBAC in Alpine.js](#rbac-in-alpinejs)
- [Troubleshooting](#troubleshooting)
- [Related Documentation](#related-documentation)

## RBAC Architecture

The CarFuse RBAC system consists of:

1. **Role Definitions** - Hierarchical role structure
2. **Permission Definitions** - Granular permissions that can be assigned to roles
3. **Resource Mappings** - Mapping of application resources to roles/permissions
4. **Access Control Components** - UI and server-side enforcement

## Role Hierarchy

Roles in CarFuse follow a hierarchical structure where higher-level roles inherit permissions from lower-level roles:

```
super_admin
  └─ admin
       └─ manager
            └─ editor
                 └─ viewer
                      └─ user
```

### Standard Role Constants

Use the predefined constants for roles:

```php
use App\Services\SecurityService;

// Use the role constants
SecurityService::hasRole(SecurityService::ROLE_ADMIN);
SecurityService::hasRole(SecurityService::ROLE_MANAGER);
SecurityService::hasRole(SecurityService::ROLE_USER);
```

## Server-Side Role Checks

### Basic Role Checks

```php
// Check for a specific role
if (SecurityService::hasRole('admin')) {
    // User is an admin
}

// Check for any of multiple roles
if (SecurityService::hasRole(['admin', 'manager'])) {
    // User is either an admin or manager
}

// Enforce a specific role
SecurityService::requireRole('admin');

// Enforce any of multiple roles
SecurityService::requireRole(['admin', 'manager']);
```

### Permission-Based Checks

```php
// Check for a specific permission
if (SecurityService::hasPermission('delete_users')) {
    // User can delete users
}

// Check for all of multiple permissions
if (SecurityService::hasPermissions(['create_users', 'edit_users'])) {
    // User can both create and edit users
}

// Enforce a specific permission
SecurityService::requirePermission('delete_users');

// Enforce all of multiple permissions
SecurityService::requirePermissions(['create_users', 'edit_users']);
```

### Middleware Integration

```php
// Protect a route with role-based middleware
$router->get('/admin/dashboard', 'AdminController@dashboard')
    ->middleware('SecurityMiddleware::adminOnly');

// Protect a route with custom role middleware
$router->get('/reports', 'ReportController@index')
    ->middleware('SecurityMiddleware::roleRequired:manager');

// Protect a route with permission middleware
$router->delete('/users/{id}', 'UserController@destroy')
    ->middleware('SecurityMiddleware::permissionRequired:delete_users');
```

## Client-Side Role Checks

Use the `CarFuseSecurity` object in JavaScript:

```javascript
// Check if user has a specific role
if (CarFuseSecurity.hasRole('admin')) {
    // User is an admin
    document.getElementById('admin-panel').style.display = 'block';
}

// Check if user has any of the specified roles
if (CarFuseSecurity.hasRole(['admin', 'manager'])) {
    // User is either an admin or manager
    enableManagementFeatures();
}

// Check if user has a specific permission
if (CarFuseSecurity.hasPermission('delete_users')) {
    // User can delete users
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.disabled = false;
    });
}
```

## Resource Access Mapping

Resource access mappings define which roles can access specific resources:

```php
// In config/rbac.php
return [
    'resources' => [
        'dashboard' => ['user', 'admin', 'manager'],
        'admin-panel' => ['admin'],
        'user-management' => ['admin', 'manager'],
        'reports' => ['admin', 'manager', 'analyst'],
        'settings' => ['admin']
    ]
];
```

### Checking Resource Access

```php
// Server-side
if (SecurityService::canAccessResource('user-management')) {
    // Show user management UI
}

// Client-side
if (CarFuseSecurity.canAccessResource('user-management')) {
    // Enable user management features
}
```

### Adding Custom Resource Mappings

```javascript
// Configure resource access mappings
CarFuseRBAC.configureResourceAccess({
    'analytics-dashboard': ['analyst', 'admin', 'manager'],
    'user-reports': ['manager', 'admin']
});
```

## HTML Data Attributes

The RBAC system provides data attributes for declarative access control in HTML:

### Role-Based Visibility

```html
<!-- Element visible only to admins -->
<div data-rbac-role="admin">
    This content is only visible to administrators.
</div>

<!-- Element visible to multiple roles -->
<div data-rbac-role="admin,manager">
    This content is visible to administrators and managers.
</div>

<!-- Element visible to everyone except specified roles -->
<div data-rbac-role="!user">
    This content is hidden from regular users.
</div>
```

### Permission-Based Visibility

```html
<!-- Element visible only with specific permission -->
<div data-rbac-permission="delete_users">
    <button class="delete-btn">Delete User</button>
</div>

<!-- Element visible with any of the specified permissions -->
<div data-rbac-permission="create_posts,edit_posts">
    <button class="post-action-btn">Manage Posts</button>
</div>
```

### Resource-Based Visibility

```html
<!-- Element visible to users with access to specific resource -->
<div data-rbac-resource="reports-view">
    <a href="/reports">View Reports</a>
</div>
```

### Unauthorized Behavior

```html
<!-- Specify behavior when unauthorized -->
<button data-rbac-resource="content-delete" 
        data-rbac-unauthorized="disable">
    Delete Content
</button>

<!-- Available unauthorized behaviors: -->
<!-- - "hide" (default): Remove from DOM -->
<!-- - "disable": Disable the element -->
<!-- - "blur": Apply blur effect -->
<!-- - "placeholder": Replace with placeholder -->
```

### Applying RBAC to Elements

RBAC is automatically applied to elements with data attributes when the page loads. You can also manually apply it:

```javascript
// Apply to all elements with RBAC attributes in the document
CarFuseRBAC.applyAccessControl();

// Apply to all elements within a specific container
CarFuseRBAC.applyAccessControl(document.getElementById('sidebar'));

// Apply to a specific element
CarFuseRBAC.applyElementAccess(myButton);
```

## RBAC in Alpine.js

Alpine.js integrates with the RBAC system through directives:

```html
<!-- Role-based visibility -->
<button x-auth-role="admin">Admin Only Button</button>

<!-- Permission-based visibility -->
<div x-auth-permission="edit_content">Edit Content Access</div>

<!-- Resource-based visibility -->
<section x-auth-access="reports-view">Reports Section</section>

<!-- Component integration -->
<div x-data="securityAwareComponent">
    <button x-show="hasRole('admin')" @click="doAdminThing()">
        Admin Action
    </button>
    
    <div x-show="!isAuthenticated()">
        <a href="/login">Please login</a>
    </div>
    
    <div x-show="hasPermission('edit_users')">
        User editing functionality
    </div>
</div>
```

## Troubleshooting

### Common RBAC Issues

1. **Role Assignment Issues**
   - Check the user's assigned roles in the database
   - Verify role hierarchy in configuration

2. **Resource Mapping Issues**
   - Ensure the resource is defined in the RBAC configuration
   - Check for typos in resource names

3. **Client-Side RBAC Not Working**
   - Verify that `securityData` is properly embedded in the page
   - Check browser console for JavaScript errors
   - Ensure `CarFuseRBAC.applyAccessControl()` is called

### Debugging RBAC

```javascript
// Enable RBAC debug mode
CarFuseRBAC.setDebug(true);

// Check user's current roles
console.log('User Roles:', CarFuseSecurity.getRoles());

// Check if resource access is correctly configured
console.log('Resource Mappings:', CarFuseRBAC.getResourceMappings());

// Test resource access explicitly
console.log('Can access admin-panel:', CarFuseSecurity.canAccessResource('admin-panel'));
```

## Related Documentation

- [Authentication System Overview](overview.md)
- [Login System](login-system.md)
- [Security Authentication](../../security/authentication.md)
- [Security Best Practices](../../security/best-practices.md)
