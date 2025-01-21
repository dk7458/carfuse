document.addEventListener("DOMContentLoaded", () => {
    const bookingData = [60, 40]; // Example Data
    const fleetData = [80, 20]; // Example Data

    const bookingChart = new Chart(document.getElementById("bookingChart"), {
        type: "pie",
        data: {
            labels: ["Active", "Canceled"],
            datasets: [{
                data: bookingData,
                backgroundColor: ["#007bff", "#dc3545"]
            }]
        }
    });

    const fleetChart = new Chart(document.getElementById("fleetChart"), {
        type: "bar",
        data: {
            labels: ["Dostępne", "Niedostępne"],
            datasets: [{
                data: fleetData,
                backgroundColor: ["#28a745", "#ffc107"]
            }]
        }
    });
});
