import { fetchData, handleApiError, showAlert } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/maintenance.js
 * Description: Manages vehicle maintenance schedules and records
 * Changelog:
 * - Added centralized API handling
 * - Implemented loading states
 */

document.addEventListener('DOMContentLoaded', () => {
  const ENDPOINTS = {
    maintenance: '/public/api.php?endpoint=maintenance',
    export: {
      csv: '/public/api.php?endpoint=maintenance&action=export_csv',
      pdf: '/public/api.php?endpoint=maintenance&action=export_pdf'
    }
  };

  // Initialize DOM elements
  const elements = {
    scheduleForm: document.getElementById('maintenanceForm'),
    recordList: document.getElementById('maintenanceRecords'),
    logsTable: document.getElementById('logsTableBody'),
    filterButton: document.getElementById('filterButton'),
    exportButtons: {
      csv: document.getElementById('exportCsv'),
      pdf: document.getElementById('exportPdf')
    }
  };

  async function handleExport(format) {
    try {
      window.location.href = ENDPOINTS.export[format];
    } catch (error) {
      handleApiError(error, `exporting ${format}`);
    }
  }

  async function deleteMaintenanceLog(logId) {
    try {
      const response = await fetchData(ENDPOINTS.maintenance, {
        method: 'POST',
        body: { 
          action: 'batch_delete',
          log_ids: [logId]
        }
      });
      
      showAlert('Log deleted successfully!', 'success');
      location.reload();
    } catch (error) {
      handleApiError(error, 'deleting maintenance log');
    }
  }

  // Fetch maintenance logs
  async function fetchLogs() {
    try {
      const data = await fetchData(ENDPOINTS.maintenance, {
        method: 'GET',
        params: { action: 'get_maintenance' }
      });

      if (data.success) {
        elements.logsTable.innerHTML = '';
        data.maintenance.forEach(log => {
          elements.logsTable.innerHTML += `
            <tr>
              <td>${log.make}</td>
              <td>${log.model}</td>
              <td>${log.description}</td>
              <td>${log.maintenance_date}</td>
            </tr>`;
        });
      }
    } catch (error) {
      handleApiError(error, 'fetching maintenance logs');
    }
  }

  // Filter maintenance logs
  async function filterLogs() {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    try {
      const data = await fetchData(ENDPOINTS.maintenance, {
        method: 'GET',
        params: {
          action: 'fetch_logs',
          search,
          startDate,
          endDate
        }
      });

      if (data.success) {
        elements.logsTable.innerHTML = '';
        data.logs.forEach(log => {
          elements.logsTable.innerHTML += `
            <tr>
              <td>${log.make}</td>
              <td>${log.model}</td>
              <td>${log.description}</td>
              <td>${log.maintenance_date}</td>
            </tr>`;
        });
      }
    } catch (error) {
      handleApiError(error, 'filtering maintenance logs');
    }
  }

  async function fetchMaintenanceRecords(vehicleId) {
    try {
      const data = await fetchData(ENDPOINTS.maintenance, {
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
      const result = await fetchData(ENDPOINTS.maintenance, {
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
  elements.exportButtons.csv?.addEventListener('click', () => handleExport('csv'));
  elements.exportButtons.pdf?.addEventListener('click', () => handleExport('pdf'));
  elements.filterButton?.addEventListener('click', filterLogs);
  elements.scheduleForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    scheduleService(new FormData(elements.scheduleForm));
  });

  // Initialize with vehicle ID from URL if present
  const params = new URLSearchParams(window.location.search);
  if (params.has('vehicleId')) {
    fetchMaintenanceRecords(params.get('vehicleId'));
  }

  // Initial log fetch
  fetchLogs();
});
