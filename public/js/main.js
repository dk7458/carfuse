document.addEventListener("DOMContentLoaded", function () {
    console.log("Main.js loaded successfully.");

    // Navbar Toggle
    const navbarToggle = document.getElementById("navbarToggle");
    if (navbarToggle) {
        navbarToggle.addEventListener("click", function () {
            document.getElementById("sidebar").classList.toggle("active");
        });
    }

    // Toast Notifications Auto Dismiss
    document.querySelectorAll(".toast").forEach(toast => {
        setTimeout(() => {
            toast.classList.add("fade-out");
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    });

    // Handle Modal Open/Close
    document.querySelectorAll("[data-toggle='modal']").forEach(button => {
        button.addEventListener("click", function () {
            const target = this.getAttribute("data-target");
            const modal = document.getElementById(target);
            if (modal) modal.classList.add("show");
        });
    });

    document.querySelectorAll(".modal .close").forEach(button => {
        button.addEventListener("click", function () {
            this.closest(".modal").classList.remove("show");
        });
    });

    // Global AJAX Loader
    document.addEventListener("ajaxStart", function () {
        document.getElementById("loadingOverlay").style.display = "block";
    });

    document.addEventListener("ajaxStop", function () {
        document.getElementById("loadingOverlay").style.display = "none";
    });

    // Button Click Logging (Debugging)
    document.querySelectorAll("button").forEach(button => {
        button.addEventListener("click", function () {
            console.log(`Button clicked: ${this.textContent}`);
        });
    });

    console.log("Main.js execution completed.");
});
