/**
 * CarFuse RBAC (Role Based Access Control)
 * 
 * Provides utilities for implementing role-based access control
 * consistently throughout the CarFuse application.
 */
(function() {
    // Make sure AuthHelper is available
    if (typeof window.AuthHelper === 'undefined') {
        console.error('AuthHelper is required for RBAC functionality');
        return;
    }
    
    /**
     * Role hierarchy definition (higher values have more privileges)
     * This matches the hierarchy in AuthHelper but is exposed for external use
     */
    const ROLE_HIERARCHY = {
        'guest': 0,
        'user': 1,
        'moderator': 2,
        'admin': 3,
        'super_admin': 4
    };
    
    /**
     * Resource access mapping
     * Defines which roles can access which resources
     */
    const RESOURCE_ACCESS = {
        // User management resources
        'admin-dashboard': ['admin', 'super_admin'],
        'user-management': ['admin', 'super_admin'],
        'user-roles': ['admin', 'super_admin'],
        'user-create': ['admin', 'super_admin'],
        'user-edit': ['admin', 'super_admin'],
        'user-delete': ['super_admin'],
        
        // Content management resources
        'content-create': ['moderator', 'admin', 'super_admin'],
        'content-edit': ['moderator', 'admin', 'super_admin'],
        'content-publish': ['admin', 'super_admin'],
        'content-delete': ['admin', 'super_admin'],
        
        // User resources
        'settings': ['user', 'moderator', 'admin', 'super_admin'],
        'profile': ['user', 'moderator', 'admin', 'super_admin'],
        'messages': ['user', 'moderator', 'admin', 'super_admin'],
        
        // Report resources
        'reports-view': ['moderator', 'admin', 'super_admin'],
        'reports-create': ['admin', 'super_admin'],
        'reports-export': ['admin', 'super_admin'],
        
        // System resources
        'system-settings': ['super_admin'],
        'logs': ['admin', 'super_admin'],
        'backup': ['super_admin']
    };
    
    /**
     * Permission mapping to resources
     * Maps specific permissions to resources
     */
    const PERMISSION_RESOURCES = {
        'create_user': ['user-create'],
        'edit_user': ['user-edit'],
        'delete_user': ['user-delete'],
        'manage_roles': ['user-roles'],
        'create_content': ['content-create'],
        'edit_content': ['content-edit'],
        'publish_content': ['content-publish'],
        'delete_content': ['content-delete'],
        'view_reports': ['reports-view'],
        'create_reports': ['reports-create'],
        'export_reports': ['reports-export'],
        'manage_system': ['system-settings', 'logs', 'backup']
    };
    
    /**
     * CarFuse RBAC API
     */
    const RBAC = {
        // Constants
        ROLES: Object.keys(ROLE_HIERARCHY),
        ROLE_HIERARCHY: ROLE_HIERARCHY,
        RESOURCES: Object.keys(RESOURCE_ACCESS),
        
        /**
         * Check if a role has sufficient level compared to required role
         * @param {string} userRole - User's role
         * @param {string} requiredRole - Minimum required role
         * @returns {boolean} True if user's role meets or exceeds the required role
         */
        hasRoleLevel: function(userRole, requiredRole) {
            const userLevel = ROLE_HIERARCHY[userRole] || 0;
            const requiredLevel = ROLE_HIERARCHY[requiredRole] || 0;
            return userLevel >= requiredLevel;
        },
        
        /**
         * Check if current authenticated user has the specified role level
         * @param {string} requiredRole - Minimum required role
         * @returns {boolean} True if user has required role level
         */
        checkRoleLevel: function(requiredRole) {
            return window.AuthHelper.hasRoleLevel(requiredRole);
        },
        
        /**
         * Check if a role can access a specific resource
         * @param {string} role - User role
         * @param {string} resource - Resource identifier
         * @returns {boolean} True if role can access resource
         */
        canRoleAccessResource: function(role, resource) {
            const allowedRoles = RESOURCE_ACCESS[resource];
            if (!allowedRoles) return false;
            return allowedRoles.includes(role);
        },
        
        /**
         * Check if current authenticated user can access a resource
         * @param {string} resource - Resource identifier
         * @returns {boolean} True if user can access resource
         */
        checkResourceAccess: function(resource) {
            const userRole = window.AuthHelper.getUserRole();
            if (!userRole) return false;
            
            // Super admin can access everything
            if (userRole === 'super_admin') return true;
            
            return this.canRoleAccessResource(userRole, resource);
        },
        
        /**
         * Check if permission grants access to a resource
         * @param {string} permission - Permission name
         * @param {string} resource - Resource identifier
         * @returns {boolean} True if permission grants access
         */
        doesPermissionGrantAccess: function(permission, resource) {
            const resources = PERMISSION_RESOURCES[permission];
            if (!resources) return false;
            return resources.includes(resource);
        },
        
        /**
         * Configure resource access for specific roles
         * @param {object} resourceMap - Map of resources to allowed roles
         */
        configureResourceAccess: function(resourceMap) {
            if (!resourceMap || typeof resourceMap !== 'object') return;
            
            // Merge with existing resource access map
            Object.assign(RESOURCE_ACCESS, resourceMap);
        },
        
        /**
         * Apply access control to DOM elements based on user role
         * @param {string} selector - CSS selector for elements to check
         * @param {string} attributeName - Data attribute name containing resource or role
         */
        applyAccessControl: function(selector = '[data-rbac-resource], [data-rbac-role]', attributeName = 'data-rbac-resource') {
            const elements = document.querySelectorAll(selector);
            const userRole = window.AuthHelper.getUserRole();
            
            elements.forEach(el => {
                const resource = el.getAttribute('data-rbac-resource');
                const requiredRole = el.getAttribute('data-rbac-role');
                
                let hasAccess = true;
                
                // Check resource access if specified
                if (resource) {
                    hasAccess = this.checkResourceAccess(resource);
                }
                
                // Check role level if specified
                if (requiredRole && hasAccess) {
                    hasAccess = this.checkRoleLevel(requiredRole);
                }
                
                // Apply visibility based on access
                if (!hasAccess) {
                    // Check how to handle unauthorized elements
                    const onUnauthorized = el.getAttribute('data-rbac-unauthorized') || 'hide';
                    
                    switch (onUnauthorized) {
                        case 'hide':
                            el.style.display = 'none';
                            break;
                        case 'disable':
                            el.setAttribute('disabled', 'disabled');
                            el.classList.add('disabled');
                            break;
                        case 'remove':
                            el.parentNode.removeChild(el);
                            break;
                        default:
                            el.style.display = 'none';
                    }
                }
            });
        }
    };
    
    // Expose to global scope
    window.CarFuseRBAC = RBAC;
    
    // Apply access control on DOM load
    document.addEventListener('DOMContentLoaded', () => {
        // Wait a bit to ensure AuthHelper is fully initialized
        setTimeout(() => {
            if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                RBAC.applyAccessControl();
            }
        }, 100);
    });
    
    // Apply access control when auth state changes
    const authEventName = window.CarFuseEvents 
        ? window.CarFuseEvents.NAMES.AUTH.STATE_CHANGED 
        : 'auth:state-changed';
    
    document.addEventListener(authEventName, () => {
        RBAC.applyAccessControl();
    });
    
    // Notify that RBAC is loaded
    document.dispatchEvent(new CustomEvent('rbac:loaded'));
    
    console.info('CarFuse RBAC loaded');
})();
