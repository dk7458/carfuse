import { apiFetch } from './api.js';

document.addEventListener("DOMContentLoaded", function () {
    if (window.sharedJsInitialized) return;
    window.sharedJsInitialized = true;
    console.log("Shared.js: homepage initialization started.");

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
        const token = localStorage.getItem('authToken');
        options.headers = {
            ...options.headers,
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        };
        try {
            const response = await fetch(endpoint, options);
            if (!response.ok) {
                const error = await response.json();
                console.error("API Error", error);
                throw error;
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API call failed:', error);
        }
    }

    // Session-based initialization
    apiCall("/api/shared/initSession.php")
    .then(data => {
        if (data) {
            console.log("Session initialized successfully for homepage.");
        }
    })
    .catch(error => console.error("Session init error:", error));

    // Navbar toggle & CTA scrolling
    document.addEventListener("click", function(e) {
        // Navbar toggle handling
        if (e.target.matches('#navbarToggle, #navbarToggle *')) {
            const navbar = getElement('#navbarSupportedContent');
            if (navbar) {
                navbar.classList.toggle('show');
                console.log('Navbar toggled successfully');
            }
        }

        // Register button handling
        if (e.target.matches('#register-btn')) {
            e.preventDefault();
            const href = e.target.getAttribute('href');
            const target = href ? getElement(href) : null;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
                console.log("Smooth scroll triggered on homepage CTA.");
            }
        }
    });

    console.log("Shared.js: homepage initialization completed.");
});

document.addEventListener("DOMContentLoaded", () => {
  // ...existing shared functionalities...
});
