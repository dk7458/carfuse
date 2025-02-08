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

    // Fetch and manage users
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
                                <td>${user.role}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="changeUserRole(${user.id})">Zmień rolę</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Usuń</button>
                                </td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd ładowania użytkowników:", error));
    }

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

    // Fetch and manage reports
    function fetchReports() {
        fetch("/api/admin/reports.php")
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

    loadAdminDashboard();
    fetchUsers();
    fetchReports();
});
