import ajax from './ajax';

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const errorContainer = document.getElementById('error-container');

    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);

    /**
     * Obsługuje logowanie użytkownika.
     */
    async function handleLogin(event) {
        event.preventDefault();
        clearErrors();

        const formData = new FormData(loginForm);
        const username = formData.get('username').trim();
        const password = formData.get('password').trim();

        if (!validateCredentials(username, password)) return;

        try {
            const response = await ajax.post('/login', { username, password });
            if (response.success) {
                ajax.setToken(response.token);
                redirectToDashboard();
            } else {
                showError(response.error || 'Błąd podczas logowania.');
            }
        } catch (error) {
            console.error('Błąd logowania:', error);
            showError('Wystąpił problem podczas logowania. Spróbuj ponownie.');
        }
    }

    /**
     * Obsługuje rejestrację użytkownika.
     */
    async function handleRegister(event) {
        event.preventDefault();
        clearErrors();

        const formData = new FormData(registerForm);
        const username = formData.get('username').trim();
        const password = formData.get('password').trim();
        const confirmPassword = formData.get('confirm_password').trim();

        if (!validateCredentials(username, password, confirmPassword)) return;

        try {
            const response = await ajax.post('/register', { username, password });
            if (response.success) {
                ajax.setToken(response.token);
                redirectToDashboard();
            } else {
                showError(response.error || 'Błąd podczas rejestracji.');
            }
        } catch (error) {
            console.error('Błąd rejestracji:', error);
            showError('Wystąpił problem podczas rejestracji. Spróbuj ponownie.');
        }
    }

    /**
     * Przekierowuje użytkownika do dashboardu.
     */
    function redirectToDashboard() {
        window.location.href = '/dashboard';
    }

    /**
     * Wyświetla komunikat błędu.
     */
    function showError(message) {
        if (errorContainer) {
            errorContainer.innerText = message;
            errorContainer.style.display = 'block';
        }
    }

    /**
     * Czyści komunikaty błędów.
     */
    function clearErrors() {
        if (errorContainer) {
            errorContainer.innerText = '';
            errorContainer.style.display = 'none';
        }
    }

    /**
     * Sprawdza poprawność danych logowania i rejestracji.
     */
    function validateCredentials(username, password, confirmPassword = null) {
        if (!username || username.length < 3) {
            showError('Nazwa użytkownika musi mieć co najmniej 3 znaki.');
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
     * Wylogowuje użytkownika i czyści tokeny autoryzacji.
     */
    function logout() {
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
    }

    /**
     * Sprawdza, czy token sesji wygasł i wylogowuje użytkownika w razie potrzeby.
     */
    function refreshSession() {
        const token = localStorage.getItem('auth_token');
        if (!token) return;

        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            const expiration = payload.exp * 1000;
            const now = Date.now();

            if (now >= expiration) {
                logout();
            }
        } catch (error) {
            console.error('Błąd walidacji tokena:', error);
            logout();
        }
    }

    // Sprawdzanie sesji co minutę
    setInterval(refreshSession, 60000);
});
