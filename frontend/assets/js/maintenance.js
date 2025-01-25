import { fetchData, handleApiError, showAlert } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/maintenance.js
 * Description: Manages vehicle maintenance schedules and records
 * Changelog:
 * - Added centralized API handling
 * - Implemented loading states
 */

document.addEventListener('DOMContentLoaded', () => {
    const scheduleForm = document.getElementById('maintenanceForm');
    const recordList = document.getElementById('maintenanceRecords');
    const logsTableBody = document.getElementById('logsTableBody');
    const filterButton = document.getElementById('filterButton');
    const exportCsvButton = document.getElementById('exportCsv');
    const exportPdfButton = document.getElementById('exportPdf');

    // Export maintenance logs
    exportCsvButton.addEventListener('click', () => {
        window.location.href = '/public/api.php?endpoint=maintenance&action=export_csv';
    });

    exportPdfButton.addEventListener('click', () => {
        window.location.href = '/public/api.php?endpoint=maintenance&action=export_pdf';
    });

    // Batch delete maintenance logs
    document.querySelectorAll('.delete-log').forEach(button => {
        button.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete this maintenance log?')) {
                const logId = button.dataset.id;

                fetch('/public/api.php?endpoint=maintenance&action=batch_delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ log_ids: [logId] }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Log deleted successfully!');
                            location.reload();
                        } else {
                            alert(data.error || 'Error deleting log.');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });

    // Fetch maintenance logs
    function fetchLogs() {
        fetch('/public/api.php?endpoint=maintenance&action=get_maintenance')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logsTableBody.innerHTML = '';
                    data.maintenance.forEach(log => {
                        logsTableBody.innerHTML += `
                            <tr>
                                <td>${log.make}</td>
                                <td>${log.model}</td>
                                <td>${log.description}</td>
                                <td>${log.maintenance_date}</td>
                            </tr>`;
                    });
                }
            })
            .catch(error => console.error('Error fetching maintenance logs:', error));
    }

    // Filter maintenance logs
    filterButton.addEventListener('click', () => {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=maintenance&action=fetch_logs&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logsTableBody.innerHTML = '';
                    data.logs.forEach(log => {
                        logsTableBody.innerHTML += `
                            <tr>
                                <td>${log.make}</td>
                                <td>${log.model}</td>
                                <td>${log.description}</td>
                                <td>${log.maintenance_date}</td>
                            </tr>`;
                    });
                }
            })
            .catch(error => console.error('Error filtering maintenance logs:', error));
    });

    // Initial log fetch
    fetchLogs();

    async function fetchMaintenanceRecords(vehicleId) {
        try {
            const data = await fetchData('/public/api.php', {
                endpoint: 'maintenance',
                method: 'GET',
                params: {
                    action: 'fetch_records',
                    vehicle_id: vehicleId
                }
            });

            renderRecords(data.records);
        } catch (error) {
            handleApiError(error, 'fetching maintenance records');
        }
    }

    async function scheduleService(formData) {
        try {
            const result = await fetchData('/public/api.php', {
                endpoint: 'maintenance',
                method: 'POST',
                body: {
                    action: 'schedule',
                    ...Object.fromEntries(formData)
                }
            });

            showAlert('Maintenance scheduled successfully', 'success');
            fetchMaintenanceRecords(formData.get('vehicleId'));
        } catch (error) {
            handleApiError(error, 'scheduling maintenance');
        }
    }

    // Event listeners
    scheduleForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        scheduleService(new FormData(scheduleForm));
    });

    // Initialize with vehicle ID from URL if present
    const params = new URLSearchParams(window.location.search);
    if (params.has('vehicleId')) {
        fetchMaintenanceRecords(params.get('vehicleId'));
    }
});
