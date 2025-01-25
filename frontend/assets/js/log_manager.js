import { fetchData, showAlert, showModal, hideModal, handleApiError } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/log_manager.js
 * Description: Handles log management operations, including fetching, filtering, clearing, and exporting logs for general and error-specific cases.
 * Changelog:
 * - Merged functionality from logs.js, logs_manager.js, and error_log_viewer.js.
 * - Added modular functions for log actions.
 * - Improved code reusability with centralized utilities.
 * - Centralized utilities, improved modularity.
 * - Implemented centralized error handling
 * - Added loading indicators for log fetching
 */

document.addEventListener("DOMContentLoaded", () => {
    // Constants
    const logsTableBody = document.getElementById("logsTableBody");
    const filterButton = document.getElementById("filterButton");
    const clearLogsButton = document.getElementById("clearLogs");
    const exportCsvButton = document.getElementById("exportCsv");
    const logTable = document.getElementById('logTable');
    const filterForm = document.getElementById('logFilter');
    let currentPage = 1;

    // Fetch Logs
    async function fetchLogs(page = 1, filters = {}) {
        try {
            const data = await fetchData('/public/api.php', {
                endpoint: 'logs',
                method: 'GET',
                params: {
                    action: 'fetch',
                    page,
                    ...filters
                },
                showLoader: true
            });

            renderLogs(data.logs);
            updatePagination(data.totalPages, page);
        } catch (error) {
            handleApiError(error, 'fetching logs');
        }
    }

    // Render Logs
    function renderLogs(logs) {
        logsTableBody.innerHTML = logs.map((log) => `
            <tr>
                <td>${log.timestamp}</td>
                <td>${log.level || "General"}</td>
                <td>${log.message}</td>
            </tr>
        `).join("");
    }

    // Export Logs as CSV
    async function exportLogs(format) {
        try {
            await fetchData('/public/api.php', {
                endpoint: 'logs',
                method: 'GET',
                params: {
                    action: 'export',
                    format
                },
                showLoader: true
            });
        } catch (error) {
            handleApiError(error, 'exporting logs');
        }
    }

    // Clear Logs
    async function clearLogs() {
        if (confirm("Are you sure you want to clear logs older than 30 days?")) {
            try {
                const data = await fetchData("/public/api.php?endpoint=logs&action=clear_logs", { method: "POST" });
                if (data.success) {
                    showAlert("Logs cleared successfully.", 'success');
                    fetchLogs();
                } else {
                    showAlert("Failed to clear logs.", 'error');
                }
            } catch (error) {
                console.error("Error clearing logs:", error);
                showAlert("Error clearing logs.", 'error');
            }
        }
    }

    // Event Listeners
    filterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(filterForm);
        const filters = Object.fromEntries(formData.entries());
        currentPage = 1;
        fetchLogs(currentPage, filters);
    });

    document.querySelectorAll('.export-btn').forEach(btn => {
        btn.addEventListener('click', () => exportLogs(btn.dataset.format));
    });

    clearLogsButton?.addEventListener("click", clearLogs);

    // Fetch Logs on Page Load
    fetchLogs();
});
