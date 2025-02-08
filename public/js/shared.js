document.addEventListener("DOMContentLoaded", function () {
    console.log("Shared.js execution started on index.php.");
    console.log("Shared.js loaded successfully.");

    // Home.php interactions: Navbar toggle, CTA smooth scrolling, hero animation
    const navbarToggle = document.getElementById("navbarToggle");
    if (navbarToggle) {
        navbarToggle.addEventListener("click", function () {
            this.classList.toggle("active");
        });
    } else {
        console.warn("Navbar toggle (#navbarToggle) not found.");
    }

    const registerBtn = document.getElementById("register-btn");
    if (registerBtn) {
        registerBtn.addEventListener("click", function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute("href"));
            if (target) {
                target.scrollIntoView({ behavior: "smooth" });
            } else {
                console.warn("CTA target not found for register button.");
            }
        });
    } else {
        console.warn("Register button (#register-btn) not found.");
    }

    const heroSection = document.querySelector(".hero-section");
    if (heroSection) {
        heroSection.classList.add("fade-in");
    } else {
        console.warn("Hero section (.hero-section) not found.");
    }

    // Check for chart element and use fallback if absent
    const chartElement = document.getElementById('chart');
    if (!chartElement) {
        console.warn("Chart element not found. Running fallback.");
        // [Optional fallback logic]
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

        fetch("/public/api/shared/charts.php", {
            headers: { "Accept": "application/json" }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Chart data:", data);
            if (!data.bookingTrends || !data.revenueTrends) {
                console.error("Missing chart data.");
                return;
            }
            chartCache = data;
            lastFetchTime = now;
            renderCharts(data);
        })
        .catch(error => console.error("Error fetching chart data:", error));
    }

    function renderCharts(data) {
        let bookingCanvas = document.getElementById("bookingTrends");
        if (!bookingCanvas) {
            console.warn("Element #bookingTrends not found. Creating fallback canvas.");
            bookingCanvas = document.createElement("canvas");
            bookingCanvas.id = "bookingTrendsFallback";
            document.body.appendChild(bookingCanvas);
        }
        new Chart(bookingCanvas.getContext("2d"), {
            type: "line",
            data: data.bookingTrends,
        });

        let revenueCanvas = document.getElementById("revenueTrends");
        if (!revenueCanvas) {
            console.warn("Element #revenueTrends not found. Creating fallback canvas.");
            revenueCanvas = document.createElement("canvas");
            revenueCanvas.id = "revenueTrendsFallback";
            document.body.appendChild(revenueCanvas);
        }
        new Chart(revenueCanvas.getContext("2d"), {
            type: "bar",
            data: data.revenueTrends,
        });
    }

    // Update charts every minute
    loadCharts();
    setInterval(loadCharts, cacheDuration);

    // Modal handling functions using Bootstrap
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            $("#" + modalId).modal("show");
        } else {
            console.warn(`Modal (#${modalId}) not found.`);
        }
    }

    function hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            $("#" + modalId).modal("hide");
        } else {
            console.warn(`Modal (#${modalId}) not found.`);
        }
    }

    // Expose modal functions globally if needed
    window.showModal = showModal;
    window.hideModal = hideModal;
});
