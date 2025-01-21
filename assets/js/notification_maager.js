document.addEventListener("DOMContentLoaded", function () {
    // Resend notification
    document.querySelectorAll(".resend-notification").forEach((button) => {
        button.addEventListener("click", function () {
            const notificationId = this.dataset.id;

            if (!notificationId) {
                alert("Nieprawidłowy identyfikator powiadomienia.");
                return;
            }

            fetch("/controllers/notification_ctrl.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "resend_notification",
                    notification_id: notificationId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.success);
                        location.reload();
                    } else {
                        alert(data.error || "Wystąpił błąd podczas wysyłania powiadomienia ponownie.");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Błąd sieci. Spróbuj ponownie później.");
                });
        });
    });

    // Delete notification
    document.querySelectorAll(".delete-notification").forEach((button) => {
        button.addEventListener("click", function () {
            const notificationId = this.dataset.id;

            if (!notificationId || !confirm("Czy na pewno chcesz usunąć to powiadomienie?")) {
                return;
            }

            fetch("/controllers/notification_ctrl.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "delete_notification",
                    notification_id: notificationId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.success);
                        location.reload();
                    } else {
                        alert(data.error || "Wystąpił błąd podczas usuwania powiadomienia.");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Błąd sieci. Spróbuj ponownie później.");
                });
        });
    });

    // Generate report
    const reportForm = document.getElementById("generate-report-form");
    if (reportForm) {
        reportForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(reportForm);
            const data = Object.fromEntries(formData.entries());

            fetch("/controllers/notification_ctrl.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    action: "generate_report",
                    ...data,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("Raport wygenerowany.");
                        console.log(data.data);
                        // TODO: Display report data in a user-friendly format
                    } else {
                        alert(data.error || "Wystąpił błąd podczas generowania raportu.");
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Błąd sieci. Spróbuj ponownie później.");
                });
        });
    }
});
