document.getElementById("sendNotification").addEventListener("click", function () {
    const recipient = prompt("Wprowadź odbiorcę (e-mail lub numer telefonu):");
    const type = prompt("Typ powiadomienia (email/sms):");
    const subject = type === "email" ? prompt("Wprowadź temat wiadomości:") : null;
    const message = prompt("Wprowadź treść wiadomości:");

    if (!recipient || !type || !message) {
        alert("Wszystkie pola są wymagane!");
        return;
    }

    fetch("/controllers/notification_ctrl.php", {
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
