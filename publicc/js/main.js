document.addEventListener("DOMContentLoaded", function () {
    console.log("Main.js loaded successfully.");

    // Detect if we're on the dashboard page
    const dashboardView = document.getElementById("dashboard-view");
    if (!dashboardView) {
        console.log("Dashboard view not found. Assuming home page.");
        return;
    }

    // Load default dashboard module when applicable
    fetch("/dashboard/modules/user/overview.php")
        .then(response => response.text())
        .then(data => {
            dashboardView.innerHTML = data;
        })
        .catch(error => console.error("Błąd ładowania domyślnego widoku:", error));
});
