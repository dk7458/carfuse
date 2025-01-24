document.getElementById("sendNotification").addEventListener("click", function () {
    const recipient = prompt("Wprowadź odbiorcę (e-mail lub numer telefonu):");
    const type = prompt("Typ powiadomienia (email/sms):");
    const subject = type === "email" ? prompt("Wprowadź temat wiadomości:") : null;
    const message = prompt("Wprowadź treść wiadomości:");

    if (!recipient || !type || !message) {
        alert("Wszystkie pola są wymagane!");
        return;
    }

    fetch("/public/api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            action: "send_notification",
            type: type,
            recipient: recipient,
            subject: subject,
            message: message,
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
        .catch((error) => console.error("Błąd:", error));
});

document.addEventListener('DOMContentLoaded', function () {
    // Fetch unread notifications
    function fetchUnreadNotifications() {
        fetch('/public/api.php?endpoint=notifications&action=fetch_unread')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch unread notifications');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with unread notifications
                    console.log('Unread Notifications:', data.notifications);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    fetchUnreadNotifications();
});
