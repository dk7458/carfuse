document.addEventListener("DOMContentLoaded", function () {
    // Initialize Charts
    function loadCharts() {
        fetch("/api/shared/charts.php")
            .then(response => response.json())
            .then(data => {
                let ctx = document.getElementById("bookingTrends").getContext("2d");
                new Chart(ctx, {
                    type: "line",
                    data: data.bookingTrends,
                });

                ctx = document.getElementById("revenueTrends").getContext("2d");
                new Chart(ctx, {
                    type: "bar",
                    data: data.revenueTrends,
                });
            })
            .catch(error => console.error("Błąd ładowania wykresów:", error));
    }

    // Initialize Modals
    function showModal(modalId) {
        $("#" + modalId).modal("show");
    }

    loadCharts();
});
