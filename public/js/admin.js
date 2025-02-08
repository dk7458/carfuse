document.addEventListener("DOMContentLoaded", function () {
    // Fetch and manage admin dashboard data
    function loadAdminDashboard() {
        fetch("/api/admin/dashboard.php")
            .then(response => response.json())
            .then(data => {
                document.getElementById("totalUsers").textContent = data.totalUsers || 0;
                document.getElementById("totalRevenue").textContent = `$${data.totalRevenue || "0.00"}`;
                document.getElementById("totalBookings").textContent = data.totalBookings || 0;
            })
            .catch(error => console.error("Błąd ładowania dashboardu:", error));
    }

    // Fetch and manage users with role dropdown update
    function fetchUsers() {
        fetch("/api/admin/users.php")
            .then(response => response.json())
            .then(data => {
                const userTable = document.getElementById("userList");
                userTable.innerHTML = "";

                if (data.length === 0) {
                    userTable.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Brak użytkowników.</td></tr>`;
                } else {
                    data.forEach(user => {
                        userTable.innerHTML += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.name}</td>
                                <td>
                                    <select onchange="updateUserRole(${user.id}, this.value)">
                                        <option value="admin" ${user.role === "admin" ? "selected" : ""}>Admin</option>
                                        <option value="user" ${user.role === "user" ? "selected" : ""}>User</option>
                                        <option value="staff" ${user.role === "staff" ? "selected" : ""}>Staff</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Usuń</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd ładowania użytkowników:", error));
    }

    // Update user role via AJAX
    window.updateUserRole = function (userId, newRole) {
        fetch("/api/admin/update_user_role.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: userId, role: newRole })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                alert("Rola użytkownika zmieniona.");
            } else {
                alert("Nie udało się zmienić roli.");
            }
        })
        .catch(error => console.error("Błąd aktualizacji roli użytkownika:", error));
    };

    function deleteUser(userId) {
        if (confirm("Czy na pewno chcesz usunąć użytkownika?")) {
            fetch(`/api/admin/delete_user.php?id=${userId}`, { method: "DELETE" })
                .then(response => response.json())
                .then(() => {
                    alert("Użytkownik usunięty.");
                    fetchUsers();
                })
                .catch(error => console.error("Błąd usuwania użytkownika:", error));
        }
    }

    // Fetch and manage reports with filtering and export functionality
    function fetchReports(filter = "") {
        let url = "/api/admin/reports.php";
        if(filter) url += `?filter=${encodeURIComponent(filter)}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                const reportTable = document.getElementById("reportList");
                reportTable.innerHTML = "";

                if (data.length === 0) {
                    reportTable.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Brak raportów.</td></tr>`;
                } else {
                    data.forEach(report => {
                        reportTable.innerHTML += `
                            <tr>
                                <td>${report.id}</td>
                                <td>${report.type}</td>
                                <td>${report.date}</td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewReport(${report.id})">Podgląd</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd ładowania raportów:", error));
    }

    // Export reports as CSV or PDF
    window.exportReports = function (format) {
        fetch(`/api/admin/reports_export.php?format=${format}`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `reports.${format}`;
                document.body.appendChild(a);
                a.click();
                a.remove();
            })
            .catch(error => console.error("Błąd eksportu raportów:", error));
    };

    // Fetch and manage transactions with refund handling
    function fetchTransactions() {
        fetch("/api/admin/transactions.php")
            .then(response => response.json())
            .then(data => {
                const transactionTable = document.getElementById("transactionList");
                transactionTable.innerHTML = "";

                if (data.length === 0) {
                    transactionTable.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Brak transakcji.</td></tr>`;
                } else {
                    data.forEach(tx => {
                        transactionTable.innerHTML += `
                            <tr>
                                <td>${tx.id}</td>
                                <td>${tx.amount}</td>
                                <td>${tx.date}</td>
                                <td>${tx.status}</td>
                                <td>
                                    ${tx.refundable ? `<button class="btn btn-sm btn-warning" onclick="refundTransaction(${tx.id})">Refund</button>` : ""}
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd ładowania transakcji:", error));
    }

    // Handle refund action with confirmation dialog
    window.refundTransaction = function (transactionId) {
        if (confirm("Czy chcesz wydać refundację dla tej transakcji?")) {
            fetch("/api/admin/refund_transaction.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id: transactionId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    alert("Refundacja została przeprowadzona.");
                    fetchTransactions();
                } else {
                    alert("Refundacja nie powiodła się.");
                }
            })
            .catch(error => console.error("Błąd refundacji transakcji:", error));
        }
    };

    // Dummy function to view report details
    window.viewReport = function(reportId) {
        // ...existing code to view report...
        alert("Wyświetlanie raportu: " + reportId);
    };

    // Event listener for report filtering input
    document.getElementById("reportFilter")?.addEventListener("input", function (e) {
        fetchReports(e.target.value);
    });

    // Initial data loading
    loadAdminDashboard();
    fetchUsers();
    fetchReports();
    fetchTransactions();
});
