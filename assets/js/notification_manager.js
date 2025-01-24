// File Path: /assets/js/notification_manager.js
// Description: Handles AJAX actions for sending, resending, deleting notifications, and generating reports in the admin notification manager.
// Changelog:
// - Added `sendNotification` functionality.
// - Enhanced error handling and notifications.
// - Unified with existing resend, delete, and report generation logic.
// - Added support for scheduling push notifications.
// - Added support for managing maintenance reminder templates.

document.addEventListener("DOMContentLoaded", function () {
    /**
     * Function: Send a new notification.
     */
    document.getElementById("sendNotification")?.addEventListener("click", function () {
        const recipient = prompt("Wprowadź odbiorcę (e-mail lub numer telefonu):");
        const type = prompt("Typ powiadomienia (email/sms/push):");
        const subject = type === "email" ? prompt("Wprowadź temat wiadomości:") : null;
        const message = prompt("Wprowadź treść wiadomości:");
        const scheduleTime = type === "push" ? prompt("Wprowadź czas wysyłki (YYYY-MM-DD HH:MM:SS):") : null;

        if (!recipient || !type || !message || (type === "push" && !scheduleTime)) {
            alert("Wszystkie pola są wymagane!");
            return;
        }

        fetch("/public/api.php?endpoint=notifications&action=send_notification", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                type: type,
                recipient: recipient,
                subject: subject,
                message: message,
                schedule_time: scheduleTime,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert(data.success);
                    location.reload();
                } else {
                    alert(data.error || "Błąd podczas wysyłania powiadomienia.");
                }
            })
            .catch((error) => {
                console.error("Błąd:", error);
                alert("Błąd sieci. Spróbuj ponownie później.");
            });
    });

    /**
     * Function: Resend an existing notification.
     */
    document.querySelectorAll(".resend-notification").forEach((button) => {
        button.addEventListener("click", function () {
            const notificationId = this.dataset.id;

            if (!notificationId) {
                alert("Nieprawidłowy identyfikator powiadomienia.");
                return;
            }

            fetch("/public/api.php?endpoint=notifications&action=resend_notification", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
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

    /**
     * Function: Delete a notification.
     */
    document.querySelectorAll(".delete-notification").forEach((button) => {
        button.addEventListener("click", function () {
            const notificationId = this.dataset.id;

            if (!notificationId || !confirm("Czy na pewno chcesz usunąć to powiadomienie?")) {
                return;
            }

            fetch("/public/api.php?endpoint=notifications&action=delete_notification", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
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

    /**
     * Function: Generate a notification report.
     */
    const reportForm = document.getElementById("generate-report-form");
    if (reportForm) {
        reportForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(reportForm);
            const data = Object.fromEntries(formData.entries());

            fetch("/public/api.php?endpoint=notifications&action=generate_report", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    ...data,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("Raport wygenerowany.");
                        console.log(data.data); // Debugging output
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

    /**
     * Function: Submit the reminder template form.
     */
    const reminderTemplateForm = document.getElementById("reminderTemplateForm");
    if (reminderTemplateForm) {
        reminderTemplateForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(reminderTemplateForm);
            const data = Object.fromEntries(formData.entries());

            fetch("/public/api.php?endpoint=notifications&action=save_template", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    ...data,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.success);
                        location.reload();
                    } else {
                        alert(data.error || "Wystąpił błąd podczas zapisywania szablonu.");
                    }
                })
                .catch((error) => {
                    console.error("Błąd:", error);
                    alert("Błąd sieci. Spróbuj ponownie później.");
                });
        });
    }

    /**
     * Function: Edit an existing reminder template.
     */
    document.querySelectorAll(".edit-template").forEach((button) => {
        button.addEventListener("click", function () {
            const templateId = this.dataset.id;
            const templateName = this.dataset.name;
            const templateContent = this.dataset.content;

            document.getElementById("template_name").value = templateName;
            document.getElementById("template_content").value = templateContent;
            document.getElementById("reminderTemplateForm").dataset.id = templateId;
        });
    });

    /**
     * Function: Delete a reminder template.
     */
    document.querySelectorAll(".delete-template").forEach((button) => {
        button.addEventListener("click", function () {
            const templateId = this.dataset.id;

            if (!templateId || !confirm("Czy na pewno chcesz usunąć ten szablon?")) {
                return;
            }

            fetch("/public/api.php?endpoint=notifications&action=delete_template", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    template_id: templateId,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.success);
                        location.reload();
                    } else {
                        alert(data.error || "Wystąpił błąd podczas usuwania szablonu.");
                    }
                })
                .catch((error) => {
                    console.error("Błąd:", error);
                    alert("Błąd sieci. Spróbuj ponownie później.");
                });
        });
    });

    // Fetch all notifications
    fetch('/public/api.php?endpoint=notifications&action=fetch_all')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch notifications');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Notifications:', data.notifications);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

    // Filter notifications
    document.getElementById('filterButton').addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=notification_manager&action=fetch_notifications&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationsTableBody = document.getElementById('notificationsTableBody');
                    notificationsTableBody.innerHTML = '';
                    data.notifications.forEach(notification => {
                        const row = `<tr>
                            <td>${notification.title}</td>
                            <td>${notification.message}</td>
                            <td>${notification.date}</td>
                        </tr>`;
                        notificationsTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    });
});
