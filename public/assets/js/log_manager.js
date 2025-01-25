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
    const LOG_CONFIG = {
        pageSize: 20,
        endpoints: {
            fetch: '/public/api.php?endpoint=logs&action=fetch',
            clear: '/public/api.php?endpoint=logs&action=clear_logs',
            export: '/public/api.php?endpoint=logs&action=export'
        }
    };

    // DOM elements
    const elements = {
        logsTable: document.getElementById("logsTableBody"),
        filterForm: document.getElementById('logFilter'),
        clearButton: document.getElementById("clearLogs"),
        exportButtons: document.querySelectorAll('.export-btn')
    };

    let currentPage = 1;

    // Fetch Logs
    async function fetchLogData(page = 1, filters = {}) {
        try {
            const logData = await fetchData(LOG_CONFIG.endpoints.fetch, {
                method: 'GET',
                params: { page, ...filters },
                showLoader: true
            });

            renderLogTable(logData.logs);
            updatePagination(logData.totalPages, page);
        } catch (error) {
            handleApiError(error, 'fetching logs');
        }
    }

    // Render Logs
    function renderLogTable(logs) {
        elements.logsTable.innerHTML = logs.map((log) => `
            <tr>
                <td>${log.timestamp}</td>
                <td>${log.level || "General"}</td>
                <td>${log.message}</td>
            </tr>
        `).join("");
    }

    // Export Logs as CSV
    async function handleExport(format) {
        try {
            await fetchData(LOG_CONFIG.endpoints.export, {
                method: 'GET',
                params: { format },
                showLoader: true
            });
        } catch (error) {
            handleApiError(error, 'exporting logs');
        }
    }

    // Clear Logs
    async function handleClearLogs() {
        if (confirm("Are you sure you want to clear logs older than 30 days?")) {
            try {
                const data = await fetchData(LOG_CONFIG.endpoints.clear, { method: "POST" });
                if (data.success) {
                    showAlert("Logs cleared successfully.", 'success');
                    fetchLogData();
                } else {
                    showAlert("Failed to clear logs.", 'error');
                }
            } catch (error) {
                console.error("Error clearing logs:", error);
                showAlert("Error clearing logs.", 'error');
            }
        }
    }

    // Event handler setup
    function initializeEventListeners() {
        elements.filterForm?.addEventListener('submit', handleFilterSubmit);
        elements.clearButton?.addEventListener("click", handleClearLogs);
        elements.exportButtons.forEach(btn => {
            btn.addEventListener('click', () => handleExport(btn.dataset.format));
        });
    }

    // Handle Filter Submit
    function handleFilterSubmit(e) {
        e.preventDefault();
        const formData = new FormData(elements.filterForm);
        const filters = Object.fromEntries(formData.entries());
        currentPage = 1;
        fetchLogData(currentPage, filters);
    }

    // Initialize
    initializeEventListeners();
    fetchLogData();
});
