document.addEventListener("DOMContentLoaded", function () {
    console.log("Minimal JavaScript Loaded");

    fetch("/api/test")
        .then(response => response.json())
        .then(data => console.log("API Response:", data))
        .catch(error => console.error("API Error:", error));
});

// Retrieve JWT from sessionStorage or cookie
function getAuthToken() {
    const token = sessionStorage.getItem("jwt") || getCookie("jwt");
    if (token && !isTokenExpired(token)) {
        return token;
    }
    return null;
}

/**
 * Checks if the JWT token is expired.
 * @param {string} token 
 * @return {boolean}
 */
function isTokenExpired(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(c =>
            '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
        ).join(''));
        const payload = JSON.parse(jsonPayload);
        return Date.now() >= payload.exp * 1000;
    } catch (e) {
        console.error("Error decoding token:", e);
        return true;
    }
}

/**
 * A wrapper for fetch that adds Authorization header for protected endpoints.
 * Protected endpoints: /api/views/profile.php, /api/views/dashboard.php
 */
async function secureFetch(url, options = {}) {
    const protectedEndpoints = ['/api/views/profile.php', '/api/views/dashboard.php'];
    if (protectedEndpoints.includes(url.trim())) {
        const token = getAuthToken();
        if (token) {
            options.headers = {
                ...options.headers,
                "Authorization": "Bearer " + token
            };
        }
    }
    return fetch(url, options);
}
