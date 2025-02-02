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
    function handleLogin(event) {
        event.preventDefault();

        const formData = new FormData(loginForm);

        fetch('/login', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('token', data.token);
                redirectToDashboard();
            } else {
                showError(data.error || 'Login failed.');
            }
        })
        .catch(error => {
            console.error('Error during login:', error);
            showError('Error during login.');
        });
    }

    // Handle registration form submission
    function handleRegister(event) {
        event.preventDefault();

        const formData = new FormData(registerForm);

        fetch('/register', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                localStorage.setItem('token', data.token);
                redirectToDashboard();
            } else {
                showError(data.error || 'Registration failed.');
            }
        })
        .catch(error => {
            console.error('Error during registration:', error);
            showError('Error during registration.');
        });
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
