// Ensure this script only runs once
if (!window.sharedJsInitialized) {
    window.sharedJsInitialized = true;
    console.log("Shared.js: homepage initialization started.");

    // Workaround for importing dependencies
    function loadScript(src, callback) {
        const script = document.createElement("script");
        script.src = src;
        script.onload = callback;
        script.onerror = function () {
            console.error(`Failed to load script: ${src}`);
        };
        document.head.appendChild(script);
    }

    // Load api.js dynamically if not already loaded
    if (!window.apiFetch) {
        loadScript("js/api.js", function () {
            console.log("api.js loaded successfully.");
            main(); // Call the main function after loading dependencies
        });
    } else {
        main();
    }

    function main() {
        console.log("Shared.js: running initialization.");

        // Utility function for checking elements
        function getElement(selector, context = document) {
            const element = context.querySelector(selector);
            if (!element) {
                console.warn(`Element not found: ${selector}`);
                return null;
            }
            return element;
        }

        // Improved API call with proper authentication and logging
        async function apiCall(endpoint, options = {}) {
            const token = localStorage.getItem("authToken");
            if (!token) {
                console.error("API call failed: Missing authentication token.");
                alert("Authentication error: Please log in.");
                window.location.href = '/auth/login';
                return null;
            }
            options.headers = {
                ...options.headers,
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            };
            try {
                const response = await fetch(endpoint, options);
                if (response.status === 401) {
                    console.error("API call failed: Unauthorized (401).");
                    await refreshToken();
                    return apiCall(endpoint, options); // Retry the API call
                }
                if (response.status === 403) {
                    console.error("API call failed: Forbidden (403).");
                    alert("Authentication error: Access forbidden.");
                    return null;
                }
                if (!response.ok) {
                    const error = await response.json();
                    console.error("API Error:", error);
                    throw error;
                }
                return await response.json();
            } catch (error) {
                console.error("API call failed:", error);
                return null;
            }
        }

        // Function to refresh JWT token
        async function refreshToken() {
            const refreshToken = localStorage.getItem("refreshToken");
            if (!refreshToken) {
                console.error("Refresh token missing.");
                alert("Session expired. Please log in again.");
                window.location.href = '/auth/login';
                return;
            }
            try {
                const response = await fetch('api/auth/refresh', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ refresh_token: refreshToken })
                });
                if (!response.ok) {
                    throw new Error('Failed to refresh token');
                }
                const data = await response.json();
                localStorage.setItem("authToken", data.access_token);
                console.log("Token refreshed successfully.");
            } catch (error) {
                console.error("Token refresh failed:", error);
                alert("Session expired. Please log in again.");
                window.location.href = '/auth/login';
            }
        }

        // Session-based initialization
        apiCall("api/shared/initSession.php")
            .then((data) => {
                if (data) {
                    console.log("Session initialized successfully for homepage.");
                }
            })
            .catch((error) => console.error("Session init error:", error));

        // Navbar toggle & CTA scrolling
        document.addEventListener("click", function (e) {
            // Navbar toggle handling
            if (e.target.matches("#navbarToggle, #navbarToggle *")) {
                const navbar = getElement("#navbarSupportedContent");
                if (navbar) {
                    navbar.classList.toggle("show");
                    console.log("Navbar toggled successfully.");
                }
            }

            // Register button handling
            if (e.target.matches("#register-btn")) {
                e.preventDefault();
                const href = e.target.getAttribute("href");
                const target = href ? getElement(href) : null;
                if (target) {
                    target.scrollIntoView({ behavior: "smooth" });
                    console.log("Smooth scroll triggered on homepage CTA.");
                }
            }
        });

        console.log("Shared.js: homepage initialization completed.");
    }
}

(function() {
    function isProtectedEndpoint(url) {
        const protectedEndpoints = [
            '/api/profile/update',
            '/api/password/reset/request',
            '/api/password/reset',
            '/api/payments/process',
            '/api/payments/refund',
            '/api/bookings',
            '/api/notifications',
            '/api/admin',
            '/api/documents'
        ];
        return protectedEndpoints.some(endpoint => url.startsWith(endpoint));
    }

    function apiFetch(url, options = {}) {
        if (!options.headers) {
            options.headers = {};
        }

        if (isProtectedEndpoint(url)) {
            const token = localStorage.getItem('token');
            if (token) {
                options.headers['Authorization'] = 'Bearer ' + token;
            } else {
                console.warn('Missing token for protected endpoint:', url);
            }
        }

        return fetch(url, options)
            .then(async response => {
                if (response.status === 401) {
                    console.warn('Unauthorized response. Attempting token refresh...');
                    // ...token refresh logic...
                    // After refresh, retry original request or handle failure
                }
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Request failed [${response.status}]: ${errorText}`);
                }
                return response;
            })
            .catch(error => {
                console.error('API fetch error:', error.message);
                throw error;
            });
    }

    // Expose functions to the global scope
    window.apiFetch = apiFetch;

    // Log successful API script loading
    console.log('Shared API script loaded successfully');
})();
