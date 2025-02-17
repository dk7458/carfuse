import { apiFetch } from './api.js';

document.addEventListener("DOMContentLoaded", function () {
    console.log("Main.js Loaded");
    // Ensure this runs only once
    if (window.mainJsInitialized) return;
    window.mainJsInitialized = true;

    // Run script only on dashboard pages
    if (!window.location.pathname.includes("/dashboard")) {
        console.log("main.js: Not on dashboard. Exiting.");
        return;
    }

    console.log("Main.js loaded successfully.");

    // Detect if we're on the dashboard page
    const dashboardView = document.getElementById("dashboard-view");
    if (!dashboardView) {
        console.log("Dashboard view not found. main.js will not run on homepage.");
        return;
    }

    // Prevent execution if not on dashboard
    if (!document.querySelector(".dashboard-container")) {
        return;
    }

    const isProtectedPage = ["/dashboard", "/profile"].includes(window.location.pathname);
    if (isProtectedPage) {
        const jwt = document.cookie.split('; ').find(row => row.startsWith('jwt='));
        if (!jwt) {
            window.location.href = "/auth/login.php";
        }
    }

    async function fetchData(endpoint) {
        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('apiToken')}`,
                    'Content-Type': 'application/json'
                },
                credentials: 'include'
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Failed to fetch data:', error);
        }
    }

    // Load default dashboard module with session credentials
    apiFetch("/dashboard/modules/user/overview.php", {
        credentials: "include"
    })
    .then(response => response.text())
    .then(data => {
        dashboardView.innerHTML = data;
    })
    .catch(error => console.error("main.js fetch error:", error));

    document.querySelectorAll(".dashboard-link").forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            let targetView = this.getAttribute("href");
            apiFetch(targetView)
                .then(r => r.text())
                .then(data => {
                    document.getElementById("dashboard-view").innerHTML = data;
                })
                .catch(err => console.error("Dashboard link error:", err));
        });
    });
});
