document.addEventListener("DOMContentLoaded", () => {
    // Bulk Action: Apply
    const bulkActionForm = document.getElementById("bulk-actions-form");
    const bulkActionButton = document.getElementById("apply-bulk-action");
    const selectAllCheckbox = document.getElementById("select-all");
    const userCheckboxes = document.querySelectorAll(".user-checkbox");

    bulkActionButton.addEventListener("click", () => {
        const action = document.getElementById("bulk-action").value;
        const selectedUsers = Array.from(userCheckboxes)
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.value);

        if (!action || selectedUsers.length === 0) {
            alert("Wybierz akcję i zaznacz użytkowników.");
            return;
        }

        fetch("/controllers/user_ctrl.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                action: "bulk_action",
                bulk_action: action,
                user_ids: selectedUsers,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("Akcja zbiorowa została pomyślnie wykonana.");
                    location.reload();
                } else {
                    alert("Wystąpił błąd: " + data.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Nie udało się wykonać akcji zbiorowej.");
            });
    });

    // Select/Deselect All Users
    selectAllCheckbox.addEventListener("change", () => {
        const isChecked = selectAllCheckbox.checked;
        userCheckboxes.forEach((checkbox) => (checkbox.checked = isChecked));
    });

    // Role Change
    document.querySelectorAll(".user-role").forEach((select) => {
        select.addEventListener("change", () => {
            const userId = select.dataset.id;
            const newRole = select.value;

            fetch("/controllers/user_ctrl.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    action: "change_role",
                    user_id: userId,
                    role: newRole,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert("Rola użytkownika została pomyślnie zaktualizowana.");
                    } else {
                        alert("Nie udało się zaktualizować roli: " + data.error);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Nie udało się zaktualizować roli.");
                });
        });
    });

    // Delete User
    document.querySelectorAll(".delete-user").forEach((button) => {
        button.addEventListener("click", () => {
            if (confirm("Czy na pewno chcesz usunąć tego użytkownika?")) {
                const userId = button.dataset.id;

                fetch("/controllers/user_ctrl.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        action: "delete_user",
                        user_id: userId,
                    }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            alert("Użytkownik został usunięty.");
                            location.reload();
                        } else {
                            alert("Nie udało się usunąć użytkownika: " + data.error);
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        alert("Nie udało się usunąć użytkownika.");
                    });
            }
        });
    });
});
