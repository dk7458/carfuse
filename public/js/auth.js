import ajax from './ajax';

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Handle login form submission
    async function handleLogin(event) {
        event.preventDefault();

        const formData = new FormData(loginForm);
        const username = formData.get('username');
        const password = formData.get('password');

        try {
            const response = await ajax.post('/login', { username, password });
            ajax.setToken(response.token);
            redirectToDashboard();
        } catch (error) {
            console.error('Error during login:', error);
            showError('Error during login.');
        }
    }

    // Handle registration form submission
    async function handleRegister(event) {
        event.preventDefault();

        const formData = new FormData(registerForm);
        const username = formData.get('username');
        const password = formData.get('password');

        try {
            const response = await ajax.post('/register', { username, password });
            ajax.setToken(response.token);
            redirectToDashboard();
        } catch (error) {
            console.error('Error during registration:', error);
            showError('Error during registration.');
        }
    }

    // Redirect to dashboard
    function redirectToDashboard() {
        window.location.href = '/dashboard';
    }

    // Show error messages
    function showError(message) {
        const errorContainer = document.getElementById('error-container');
        errorContainer.innerText = message;
        errorContainer.style.display = 'block';
    }

    // Logout functionality
    function logout() {
        localStorage.removeItem('token');
        window.location.href = '/login';
    }

    // Automatically refresh expired sessions
    function refreshSession() {
        const token = localStorage.getItem('token');
        if (!token) return;

        const payload = JSON.parse(atob(token.split('.')[1]));
        const expiration = payload.exp * 1000;
        const now = Date.now();

        if (now >= expiration) {
            logout();
        }
    }

    setInterval(refreshSession, 60000); // Check session every minute
});
