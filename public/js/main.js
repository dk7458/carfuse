import { apiFetch } from './api.js';

document.addEventListener("DOMContentLoaded", function () {
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
