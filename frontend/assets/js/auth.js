import { fetchData, handleApiError } from './shared/utils.js';

/**
 * File: /assets/js/auth.js
 * Description: Handles authentication tasks such as login, logout, and session validation.
 * Changelog:
 * - Initial creation of authentication logic.
 * - Refactored to use centralized fetchData utility
 * - Added session handling and CSRF protection
 */

document.addEventListener("DOMContentLoaded", () => {
    const logoutButton = document.getElementById("logout");
    
    if (logoutButton) {
        logoutButton.addEventListener("click", async () => {
            try {
                await fetchData('/public/api.php', {
                    endpoint: 'auth',
                    method: 'POST',
                    body: { action: 'logout' }
                });
                window.location.href = "/login.php";
            } catch (error) {
                handleApiError(error, 'logging out');
            }
        });
    }

    const validateSession = async () => {
        try {
            await fetchData('/public/api.php', {
                endpoint: 'auth',
                method: 'GET',
                params: { action: 'validate_session' }
            });
        } catch (error) {
            // Session expired or invalid
            window.location.href = "/login.php?redirect=" + encodeURIComponent(window.location.pathname);
        }
    };

    // Check session on protected pages
    if (!window.location.pathname.includes('login.php')) {
        validateSession();
        // Recheck session every 5 minutes
        setInterval(validateSession, 300000);
    }
});
