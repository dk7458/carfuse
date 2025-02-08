document.addEventListener("DOMContentLoaded", function () {
    console.log("Shared.js execution started on index.php.");
    
    // Utility function for checking elements
    function getElement(selector, context = document) {
        const element = context.querySelector(selector);
        if (!element) {
            console.warn(`Element not found: ${selector}`);
            return null;
        }
        return element;
    }

    fetch("/api/shared/charts.php", {
        headers: { "Accept": "application/json" },
        credentials: "include" // Ensures session cookies are sent
    })
    
    // Event delegation for dynamic elements
    document.addEventListener('click', function(e) {
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
                console.log('Smooth scroll initiated');
            }
        }
    });

    // Enhanced chart handling with caching
    let chartData = null;
    let lastFetchTime = 0;
    const CACHE_DURATION = 60000; // 60 seconds

    async function getChartData() {
        const now = Date.now();
        if (chartData && (now - lastFetchTime < CACHE_DURATION)) {
            console.log('Using cached chart data');
            return chartData;
        }

        try {
            console.log('Fetching fresh chart data');
            const response = await fetch("/public/api/shared/charts.php");
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            chartData = await response.json();
            lastFetchTime = now;
            return chartData;
        } catch (error) {
            console.error('Chart data fetch error:', error);
            return null;
        }
    }

    function createFallbackCanvas(id) {
        console.log(`Creating fallback canvas for ${id}`);
        const canvas = document.createElement('canvas');
        canvas.id = `${id}_fallback`;
        canvas.style.width = '100%';
        canvas.style.maxHeight = '400px';
        return canvas;
    }

    async function initializeCharts() {
        const data = await getChartData();
        if (!data) return;

        ['bookingTrends', 'revenueTrends'].forEach(chartId => {
            let canvas = getElement(`#${chartId}`);
            if (!canvas) {
                canvas = createFallbackCanvas(chartId);
                document.body.appendChild(canvas);
            }

            try {
                new Chart(canvas.getContext('2d'), {
                    type: chartId === 'bookingTrends' ? 'line' : 'bar',
                    data: data[chartId],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                console.log(`${chartId} chart rendered successfully`);
            } catch (error) {
                console.error(`Error rendering ${chartId}:`, error);
            }
        });
    }

    // Enhanced modal handling
    const modalHandler = {
        show: function(modalId) {
            const modal = getElement(`#${modalId}`);
            if (modal) {
                try {
                    $(modal).modal('show');
                    console.log(`Modal ${modalId} shown successfully`);
                } catch (error) {
                    console.error(`Bootstrap modal error for ${modalId}:`, error);
                }
            }
        },
        hide: function(modalId) {
            const modal = getElement(`#${modalId}`);
            if (modal) {
                try {
                    $(modal).modal('hide');
                    console.log(`Modal ${modalId} hidden successfully`);
                } catch (error) {
                    console.error(`Bootstrap modal error for ${modalId}:`, error);
                }
            }
        }
    };

    // Initialize charts
    initializeCharts();
    
    // Expose modal functions globally
    window.showModal = modalHandler.show;
    window.hideModal = modalHandler.hide;

    // Setup chart refresh
    setInterval(initializeCharts, CACHE_DURATION);

    console.log('Shared.js initialization completed');
});
