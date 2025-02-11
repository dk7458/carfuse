import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const logoutButton = document.getElementById('logout-button');

    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);
    if (logoutButton) logoutButton.addEventListener('click', logout);

    refreshSession(); // Validate session on load
    setInterval(refreshSession, 60000); // Check session expiration every 60s
});

/**
 * Handles user login
 */
async function handleLogin(event) {
    event.preventDefault();
    clearErrors();

    const formData = new FormData(event.target);
    const username = formData.get('username').trim();
    const password = formData.get('password').trim();

    if (!validateCredentials(username, password)) return;

    if (await checkAndRefreshSession()) return;

    try {
        const response = await ajax.post('/login', { username, password });
        if (response.success) {
            ajax.setToken(response.token);
            showSuccessToast('Logowanie udane! Przekierowywanie...');
            setTimeout(() => window.location.href = '/dashboard', 1500);
        } else {
            showError(response.error || 'Błąd logowania.');
        }
    } catch (error) {
        console.error('Błąd logowania:', error);
        showError('Nie udało się zalogować.');
    }
}

/**
 * Handles user registration
 */
async function handleRegister(event) {
    event.preventDefault();
    clearErrors();

    const formData = new FormData(event.target);
    const username = formData.get('username').trim();
    const email = formData.get('email').trim();
    const password = formData.get('password').trim();
    const confirmPassword = formData.get('confirm_password').trim();

    if (!validateCredentials(username, password, confirmPassword, email)) return;

    if (await checkAndRefreshSession()) return;

    try {
        const response = await ajax.post('/register', { username, email, password });
        if (response.success) {
            ajax.setToken(response.token);
            showSuccessToast('Rejestracja udana! Przekierowywanie...');
            setTimeout(() => window.location.href = '/dashboard', 1500);
        } else {
            showError(response.error || 'Błąd rejestracji.');
        }
    } catch (error) {
        console.error('Błąd rejestracji:', error);
        showError('Nie udało się zarejestrować.');
    }
}

/**
 * Logs out user and clears session data
 */
function logout() {
    localStorage.removeItem('auth_token');
    showSuccessToast('Wylogowano pomyślnie.');
    setTimeout(() => window.location.href = '/auth/login', 1500);
}

/**
 * Refreshes the user session and handles expiration
 */
async function refreshSession() {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        const expiration = payload.exp * 1000;
        const now = Date.now();

        if (now >= expiration) {
            await logout();
        } else {
            const response = await ajax.get('/session/refresh', null, { 'X-Auth-Token': token });
            if (response.success) {
                ajax.setToken(response.token);
            } else {
                await logout();
            }
        }
    } catch (error) {
        await logout();
    }
}

/**
 * Checks and refreshes the session if the token is expired
 */
async function checkAndRefreshSession() {
    const token = localStorage.getItem('auth_token');
    if (!token) return false;

    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        const expiration = payload.exp * 1000;
        const now = Date.now();

        if (now >= expiration) {
            await refreshSession();
            return true;
        }
    } catch (error) {
        await logout();
        return true;
    }

    return false;
}

/**
 * Clears error messages
 */
function clearErrors() {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
        errorContainer.innerText = '';
        errorContainer.style.display = 'none';
    }
}

/**
 * Displays an error message
 */
function showError(message) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
        errorContainer.innerText = message;
        errorContainer.style.display = 'block';
    }
    showErrorToast(message);
}

/**
 * Validates login and registration credentials
 */
function validateCredentials(username, password, confirmPassword = null, email = null) {
    if (!username || username.length < 3) {
        showError('Nazwa użytkownika musi mieć co najmniej 3 znaki.');
        return false;
    }

    if (email && !isValidEmail(email)) {
        showError('Wprowadź poprawny adres e-mail.');
        return false;
    }

    if (!password || password.length < 6) {
        showError('Hasło musi mieć co najmniej 6 znaków.');
        return false;
    }

    if (confirmPassword !== null && password !== confirmPassword) {
        showError('Hasła nie są identyczne.');
        return false;
    }

    return true;
}

/**
 * Validates email format
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}
