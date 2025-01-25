import { fetchData, handleApiError } from './shared/utils.js';

/**
 * File: /assets/js/auth.js
 * Description: Handles authentication tasks such as login, logout, and session validation.
 * Changelog:
 * - Initial creation of authentication logic.
 * - Refactored to use centralized fetchData utility
 * - Added session handling and CSRF protection
 */

document.addEventListener('DOMContentLoaded', () => {
  const SESSION_CHECK_INTERVAL = 300000; // 5 minutes
  const AUTH_ENDPOINTS = {
    logout: '/public/api.php',
    validate: '/public/api.php',
    login: '/login.php'
  };

  const handleLogout = async () => {
    try {
      await fetchData(AUTH_ENDPOINTS.logout, {
        endpoint: 'auth',
        method: 'POST',
        body: { action: 'logout' }
      });
      window.location.href = AUTH_ENDPOINTS.login;
    } catch (error) {
      handleApiError(error, 'logging out');
    }
  };

  const validateSession = async () => {
    try {
      const sessionStatus = await fetchData(AUTH_ENDPOINTS.validate, {
        endpoint: 'auth',
        method: 'GET',
        params: { action: 'validate_session' }
      });
      return sessionStatus.valid;
    } catch (error) {
      redirectToLogin();
      return false;
    }
  };

  const redirectToLogin = () => {
    const currentPath = encodeURIComponent(window.location.pathname);
    window.location.href = `${AUTH_ENDPOINTS.login}?redirect=${currentPath}`;
  };

  // Initialize auth handlers
  document.getElementById('logout')?.addEventListener('click', handleLogout);

  // Set up session validation
  if (!window.location.pathname.includes('login.php')) {
    validateSession();
    setInterval(validateSession, SESSION_CHECK_INTERVAL);
  }
});
