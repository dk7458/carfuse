document.addEventListener("DOMContentLoaded", function () {
    // Handle admin signature upload
    const signatureForm = document.querySelector('form[action*="upload_signature"]');
    if (signatureForm) {
        signatureForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(signatureForm);

            fetch(signatureForm.action, {
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

            fetch(templateForm.action, {
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

            fetch("/controllers/contract_ctrl.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    action: "delete_contract",
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
});
