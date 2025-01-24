document.addEventListener('DOMContentLoaded', function () {
    // Fetch summary data
    function fetchSummary() {
        fetch('/public/api.php?endpoint=summary&action=fetch')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch summary data');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with summary data
                    console.log('Summary Data:', data.summary);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    fetchSummary();

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
