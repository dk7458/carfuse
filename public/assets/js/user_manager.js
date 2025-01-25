import { fetchData, handleApiError, showAlert, validateEmail, validateNotEmpty } from './shared/utils.js';

/**
 * File: /assets/js/user_manager.js
 * Description: Handles user management tasks such as CRUD operations, bulk actions, and role updates.
 * Changelog:
 * - Initial creation of user management functionality.
 * - Refactored to use shared utilities, modularized user actions, and added validation.
 * - Added CSRF protection
 * - Implemented session validation
 */

document.addEventListener("DOMContentLoaded", async () => {
    const bulkActionForm = document.getElementById("bulk-actions-form");
    const bulkActionButton = document.getElementById("apply-bulk-action");
    const selectAllCheckbox = document.getElementById("select-all");
    const userCheckboxes = document.querySelectorAll(".user-checkbox");

    // Validate admin session
    try {
        await fetchData('/public/api.php', {
            endpoint: 'auth',
            method: 'GET',
            params: { action: 'validate_admin' }
        });
    } catch (error) {
        window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
        return;
    }

    // Fetch and display user data
    async function fetchUsers(page = 1, filters = {}) {
        try {
            const data = await fetchData('/public/api.php', {
                endpoint: 'users',
                method: 'GET',
                params: {
                    action: 'fetch',
                    page,
                    ...filters
                }
            });

            renderUsers(data.users);
            updatePagination(data.totalPages);
        } catch (error) {
            handleApiError(error, 'fetching users');
        }
    }

    // Render users in the table
    function renderUsers(users) {
        const usersTableBody = document.getElementById("usersTableBody");
        usersTableBody.innerHTML = users.map(user => `
            <tr>
                <td><input type="checkbox" class="user-checkbox" value="${user.id}"></td>
                <td>${user.name}</td>
                <td>${user.email}</td>
                <td>${user.role}</td>
                <td>${user.status}</td>
                <td>
                    <button class="edit-user" data-id="${user.id}">Edit</button>
                    <button class="delete-user" data-id="${user.id}">Delete</button>
                </td>
            </tr>
        `).join("");
    }

    async function updateUser(userId, userData) {
        try {
            await fetchData('/public/api.php', {
                endpoint: 'users',
                method: 'POST',
                body: {
                    action: 'update',
                    user_id: userId,
                    ...userData
                }
            });

            showAlert('User updated successfully', 'success');
            fetchUsers(); // Refresh list
        } catch (error) {
            handleApiError(error, 'updating user');
        }
    }

    // Apply bulk action
    bulkActionButton.addEventListener("click", async () => {
        const action = document.getElementById("bulk-action").value;
        const selectedUsers = Array.from(userCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);

        if (!action || selectedUsers.length === 0) {
            showAlert("Please select an action and at least one user.", 'error');
            return;
        }

        try {
            const result = await fetchData("/public/api.php?endpoint=user_manager&action=bulk_action", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action, user_ids: selectedUsers })
            });
            showAlert(result.success ? "Bulk action applied successfully." : "Error: " + result.error, result.success ? 'success' : 'error');
            if (result.success) location.reload();
        } catch (error) {
            console.error("Error:", error);
            showAlert("Error applying bulk action.", 'error');
        }
    });

    // Select/Deselect all users
    selectAllCheckbox.addEventListener("change", () => {
        const isChecked = selectAllCheckbox.checked;
        userCheckboxes.forEach(checkbox => checkbox.checked = isChecked);
    });

    // Add event listeners for CRUD operations
    document.addEventListener("click", async (event) => {
        if (event.target.classList.contains("edit-user")) {
            const userId = event.target.dataset.id;
            // Fetch and populate user data for editing
        } else if (event.target.classList.contains("delete-user")) {
            const userId = event.target.dataset.id;
            if (confirm("Are you sure you want to delete this user?")) {
                try {
                    const result = await fetchData(`/public/api.php?endpoint=user_manager&action=delete_user`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ user_id: userId })
                    });
                    showAlert(result.success ? "User deleted successfully." : "Error: " + result.error, result.success ? 'success' : 'error');
                    if (result.success) location.reload();
                } catch (error) {
                    console.error("Error:", error);
                    showAlert("Error deleting user.", 'error');
                }
            }
        }
    });

    document.getElementById('userForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        updateUser(formData.get('userId'), Object.fromEntries(formData));
    });

    // Fetch users on page load
    fetchUsers();
});
