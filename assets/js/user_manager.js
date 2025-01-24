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

        fetch("/public/api.php?endpoint=users&action=bulk_action", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
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

            fetch("/public/api.php?endpoint=users&action=change_role", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
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

                fetch("/public/api.php?endpoint=users&action=delete_user", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
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

    // Status Change
    document.querySelectorAll(".toggle-status").forEach((button) => {
        button.addEventListener("click", () => {
            const userId = button.dataset.id;
            const currentStatus = button.dataset.status;
            const newStatus = currentStatus === "1" ? "0" : "1";

            fetch("/public/api.php?endpoint=users&action=update_status", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    user_id: userId,
                    status: newStatus,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        button.dataset.status = newStatus;
                        button.textContent = newStatus === "1" ? "Aktywny" : "Nieaktywny";
                        alert("Status użytkownika został pomyślnie zaktualizowany.");
                    } else {
                        alert("Nie udało się zaktualizować statusu: " + data.error);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Nie udało się zaktualizować statusu.");
                });
        });
    });

    // Edit User
    const editUserForm = document.getElementById("editUserForm");
    editUserForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const userId = document.getElementById("editUserId").value;
        const userName = document.getElementById("editUserName").value;
        const userEmail = document.getElementById("editUserEmail").value;
        const userRole = document.getElementById("editUserRole").value;
        const userStatus = document.getElementById("editUserStatus").value;

        // Validate inputs
        if (!userId || !userName || !userEmail || !userRole || !userStatus) {
            alert("Wszystkie pola są wymagane.");
            return;
        }

        fetch("/public/api.php?endpoint=users&action=edit_user", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                user_id: userId,
                name: userName,
                email: userEmail,
                role: userRole,
                status: userStatus,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("Dane użytkownika zostały pomyślnie zaktualizowane.");
                    // Refresh the user table dynamically
                    location.reload();
                } else {
                    alert("Nie udało się zaktualizować danych użytkownika: " + data.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Wystąpił błąd podczas aktualizacji danych użytkownika.");
            });
    });

    // Populate Edit User Modal with user data
    document.querySelectorAll(".edit-user").forEach((button) => {
        button.addEventListener("click", () => {
            const userId = button.dataset.id;

            fetch(`/public/api.php?endpoint=users&action=fetch_user&id=${userId}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        document.getElementById("editUserId").value = data.user.id;
                        document.getElementById("editUserName").value = data.user.name;
                        document.getElementById("editUserEmail").value = data.user.email;
                        document.getElementById("editUserRole").value = data.user.role;
                        document.getElementById("editUserStatus").value = data.user.status;
                    } else {
                        alert("Nie udało się pobrać danych użytkownika: " + data.error);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("Wystąpił błąd podczas pobierania danych użytkownika.");
                });
        });
    });

    // Add User
    const addUserForm = document.getElementById("addUserForm");
    addUserForm.addEventListener("submit", (event) => {
        event.preventDefault();

        const userName = document.getElementById("addUserName").value;
        const userEmail = document.getElementById("addUserEmail").value;
        const userRole = document.getElementById("addUserRole").value;
        const userStatus = document.getElementById("addUserStatus").value;

        // Validate inputs
        if (!userName || !userEmail || !userRole || !userStatus) {
            alert("Wszystkie pola są wymagane.");
            return;
        }

        fetch("/public/api.php?endpoint=users&action=add_user", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                name: userName,
                email: userEmail,
                role: userRole,
                status: userStatus,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert("Użytkownik został pomyślnie dodany.");
                    // Clear form fields
                    addUserForm.reset();
                    // Close modal
                    const addUserModal = bootstrap.Modal.getInstance(document.getElementById("addUserModal"));
                    addUserModal.hide();
                    // Refresh the user table dynamically
                    location.reload();
                } else {
                    alert("Nie udało się dodać użytkownika: " + data.error);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Wystąpił błąd podczas dodawania użytkownika.");
            });
    });

    // Fetch all users
    fetch('/public/api.php?endpoint=users&action=fetch_all')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch users');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Users:', data.users);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

    // Filter Users
    document.getElementById('filterButton').addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=user_manager&action=fetch_users&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const usersTableBody = document.getElementById('usersTableBody');
                    usersTableBody.innerHTML = '';
                    data.users.forEach(user => {
                        const row = `<tr>
                            <td>${user.id}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>${user.registration_date}</td>
                        </tr>`;
                        usersTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    });
});
