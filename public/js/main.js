document.addEventListener("DOMContentLoaded", function () {
    console.log("Main.js loaded successfully.");

    // Ensure dashboard view container exists before updating it
    const dashboardView = document.getElementById("dashboard-view");
    if (!dashboardView) {
        console.warn("Dashboard view container (#dashboard-view) not found.");
        return;
    }

    // Handle link navigation with event delegation
    function handleLinkNavigation(e) {
        e.preventDefault();
        const target = e.target.closest(".dashboard-link, .nav-link, .btn-ajax");
        if (!target) return;

        let targetView = target.getAttribute("href");
        if (!targetView) {
            console.warn("Link has no target view.");
            return;
        }

        fetch(targetView)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(data => {
                dashboardView.innerHTML = data;
                attachGlobalListeners(); // Re-attach listeners after loading new content
            })
            .catch(error => console.error("Error loading view:", error));
    }

    // Attach global event listeners with a safety check for document.body
    function attachGlobalListeners() {
        if (document.body) {
            document.body.removeEventListener("click", handleLinkNavigation);
            document.body.addEventListener("click", handleLinkNavigation);
            console.log("Global listeners attached.");
        } else {
            console.warn("document.body is not available.");
        }
    }

    // Initial attachment of global listeners
    attachGlobalListeners();
});
