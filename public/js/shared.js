document.addEventListener("DOMContentLoaded", function () {
    // Home.php interactions: Navbar toggle, CTA smooth scrolling, hero animation
    const navbarToggle = document.getElementById("navbarToggle");
    if (navbarToggle) {
        navbarToggle.addEventListener("click", function () {
            // ...existing navbar code...
            this.classList.toggle("active");
        });
    }
    const registerBtn = document.getElementById("register-btn");
    if (registerBtn) {
        registerBtn.addEventListener("click", function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute("href"));
            if (target) {
                target.scrollIntoView({ behavior: "smooth" });
            }
        });
    }
    const heroSection = document.querySelector(".hero-section");
    if (heroSection) {
        // Simple fade-in effect
        heroSection.classList.add("fade-in");
    }

    // Initialize Charts with caching and error handling
    let chartCache = null;
    let lastFetchTime = 0;
    const cacheDuration = 60000; // 60 seconds

    function loadCharts() {
        const now = Date.now();
        if (chartCache && now - lastFetchTime < cacheDuration) {
            renderCharts(chartCache);
            return;
        }
        fetch("/api/shared/charts.php")
            .then(response => response.json())
            .then(data => {
                if (!data.bookingTrends || !data.revenueTrends) {
                    console.error("Missing chart data");
                    return;
                }
                chartCache = data;
                lastFetchTime = now;
                renderCharts(data);
            })
            .catch(error => console.error("Błąd ładowania wykresów:", error));
    }
    function renderCharts(data) {
        const bookingCanvas = document.getElementById("bookingTrends");
        if (bookingCanvas) {
            let ctx = bookingCanvas.getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: data.bookingTrends,
            });
        } else {
            console.warn("Element #bookingTrends not found.");
        }
        const revenueCanvas = document.getElementById("revenueTrends");
        if (revenueCanvas) {
            let ctx = revenueCanvas.getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: data.revenueTrends,
            });
        } else {
            console.warn("Element #revenueTrends not found.");
        }
    }
    // Update charts every minute
    loadCharts();
    setInterval(loadCharts, cacheDuration);

    // Modal handling functions using Bootstrap
    function showModal(modalId) {
        $("#" + modalId).modal("show");
    }
    function hideModal(modalId) {
        $("#" + modalId).modal("hide");
    }
    // Expose modal functions globally if needed
    window.showModal = showModal;
    window.hideModal = hideModal;
});
