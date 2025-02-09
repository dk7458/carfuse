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
                return null;
            }
            options.headers = {
                ...options.headers,
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            };
            try {
                const response = await fetch(endpoint, options);
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
