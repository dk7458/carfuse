document.getElementById("sendNotification").addEventListener("click", function () {
    const recipient = prompt("Wprowadź odbiorcę (e-mail lub numer telefonu):");
    const type = prompt("Typ powiadomienia (email/sms):");
    const subject = type === "email" ? prompt("Wprowadź temat wiadomości:") : null;
    const message = prompt("Wprowadź treść wiadomości:");

    if (!recipient || !type || !message) {
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

fetch('/public/api.php?endpoint=notifications&action=fetch_unread')
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

document.getElementById('filterButton').addEventListener('click', () => {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    fetch(`/public/api.php?endpoint=notification&action=fetch_notifications&search=${search}&startDate=${startDate}&endDate=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById('notificationsTableBody');
                tableBody.innerHTML = '';
                data.notifications.forEach(notification => {
                    const row = `<tr>
                        <td>${notification.title}</td>
                        <td>${notification.message}</td>
                        <td>${notification.date}</td>
                    </tr>`;
                    tableBody.innerHTML += row;
                });
            }
        })
        .catch(error => console.error('Error:', error));
});
