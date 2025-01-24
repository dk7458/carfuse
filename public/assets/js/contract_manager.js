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
            const contractId = this.dataset.id;

            if (confirm("Czy na pewno chcesz usunąć tę umowę?")) {
                fetch("/public/api.php?endpoint=contract&action=delete", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        contract_id: contractId,
                    }),
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error("Failed to delete contract");
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert("Error: " + data.error);
                        }
                    })
                    .catch((error) => {
                        console.error("Unexpected error:", error);
                    });
            }
        });
    });
});
