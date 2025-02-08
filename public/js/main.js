document.addEventListener("DOMContentLoaded", function () {
    console.log("Main.js loaded successfully.");

    // Ensure dashboard view container exists before updating it
    const dashboardView = document.getElementById("dashboard-view");
    if (!dashboardView) {
        console.warn("Dashboard view container (#dashboard-view) not found.");
        return;
    }

    // Handle dashboard link navigation
    function attachDashboardLinks() {
        const dashboardLinks = document.querySelectorAll(".dashboard-link");
        
        if (dashboardLinks.length === 0) {
            console.warn("No dashboard links found.");
            return;
        }

        dashboardLinks.forEach(link => {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                let targetView = this.getAttribute("href");

                if (!targetView) {
                    console.warn("Dashboard link has no target view.");
                    return;
                }

                fetch(targetView)
                    .then(response => response.text())
                    .then(data => {
                        dashboardView.innerHTML = data;
                        attachDashboardLinks(); // Re-attach listeners after loading new content
                    })
                    .catch(error => console.error("Błąd ładowania widoku:", error));
            });
        });

        console.log("Dashboard links attached.");
    }

    // Attach event listeners after short delay (ensures elements are fully loaded)
    setTimeout(attachDashboardLinks, 100);
});
