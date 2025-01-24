document.addEventListener("DOMContentLoaded", () => {
    const bookingData = [60, 40]; // Example Data
    const fleetData = [80, 20]; // Example Data

    fetch('/public/api.php?endpoint=summary&action=get_summary')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch summary');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Summary:', data.summary);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

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

    document.getElementById('filterButton').addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=summary&action=fetch_data&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tableBody = document.getElementById('summaryTableBody');
                    tableBody.innerHTML = '';
                    data.summary.forEach(item => {
                        const row = `<tr>
                            <td>${item.name}</td>
                            <td>${item.value}</td>
                            <td>${item.date}</td>
                        </tr>`;
                        tableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    });
});
