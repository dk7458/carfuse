document.addEventListener("DOMContentLoaded", function () {
    // Handle admin signature upload
    const signatureForm = document.querySelector('form[action*="upload_signature"]');
    if (signatureForm) {
        signatureForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(signatureForm);

            fetch('/public/api.php?endpoint=signatures&action=upload', {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("Podpis został pomyślnie zaktualizowany.");
                        location.reload();
                    } else {
                        alert(`Błąd: ${data.error}`);
                    }
                })
                .catch((error) => {
                    console.error("Error uploading signature:", error);
                    alert("Wystąpił błąd podczas przesyłania podpisu.");
                });
        });
    }

    // Handle contract template upload
    const templateForm = document.querySelector('form[action*="upload_template"]');
    if (templateForm) {
        templateForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(templateForm);

            fetch('/public/api.php?endpoint=templates&action=upload', {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("Szablon umowy został pomyślnie dodany.");
                        location.reload();
                    } else {
                        alert(`Błąd: ${data.error}`);
                    }
                })
                .catch((error) => {
                    console.error("Error uploading template:", error);
                    alert("Wystąpił błąd podczas przesyłania szablonu umowy.");
                });
        });
    }

    // Handle contract deletion
    document.querySelectorAll(".delete-contract").forEach((button) => {
        button.addEventListener("click", function () {
            if (!confirm("Czy na pewno chcesz usunąć tę umowę?")) return;

            const contractId = this.dataset.id;

            fetch('/public/api.php?endpoint=contracts&action=delete', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    contract_id: contractId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("Umowa została pomyślnie usunięta.");
                        location.reload();
                    } else {
                        alert(`Błąd: ${data.error}`);
                    }
                })
                .catch((error) => {
                    console.error("Error deleting contract:", error);
                    alert("Wystąpił błąd podczas usuwania umowy.");
                });
        });
    });

    // Fetch all contracts
    fetch('/public/api.php?endpoint=contracts&action=fetch_all')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch contracts');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Contracts:', data.contracts);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

    // Handle contract filters
    document.getElementById('filterButton').addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=contract_manager&action=fetch_contracts&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contractsTableBody = document.getElementById('contractsTableBody');
                    contractsTableBody.innerHTML = '';
                    data.contracts.forEach(contract => {
                        const row = `<tr>
                            <td>${contract.id}</td>
                            <td>${contract.client}</td>
                            <td>${contract.start_date}</td>
                            <td>${contract.end_date}</td>
                        </tr>`;
                        contractsTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    });
});
