import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    const pickupDateInput = document.getElementById('pickup-date');
    const bookingForm = document.getElementById('booking-form');
    const vehiclesContainer = document.getElementById('vehicles-container');
    const errorContainer = document.getElementById('error-container');
    const loadingIndicator = document.getElementById('loading-indicator');

    if (pickupDateInput) pickupDateInput.addEventListener('change', fetchAvailableVehicles);
    if (bookingForm) bookingForm.addEventListener('submit', submitBookingRequest);

    initRealTimeValidation();
});

/**
 * Fetches available vehicles based on the selected pickup date.
 */
async function fetchAvailableVehicles() {
    const pickupDate = document.getElementById('pickup-date').value.trim();
    if (!pickupDate) return;

    showLoadingIndicator();

    try {
        const response = await ajax.get(`/vehicles/available?pickup_date=${pickupDate}`);
        hideLoadingIndicator();

        if (response.vehicles && response.vehicles.length > 0) {
            displayAvailableVehicles(response.vehicles);
        } else {
            showErrorToast('Brak dostępnych pojazdów na wybrany termin.');
            clearVehicles();
        }
    } catch (error) {
        hideLoadingIndicator();
        console.error('Błąd pobierania dostępnych pojazdów:', error);
        showErrorToast('Nie udało się pobrać dostępnych pojazdów.');
    }
}

/**
 * Displays the available vehicles in the UI.
 */
function displayAvailableVehicles(vehicles) {
    vehiclesContainer.innerHTML = '';

    vehicles.forEach(vehicle => {
        const vehicleElement = document.createElement('div');
        vehicleElement.className = 'vehicle';
        vehicleElement.innerHTML = `
            <h4>${vehicle.name}</h4>
            <p>Typ: ${vehicle.type}</p>
        `;
        vehiclesContainer.appendChild(vehicleElement);
    });
}

/**
 * Clears the vehicle list.
 */
function clearVehicles() {
    vehiclesContainer.innerHTML = '<p class="text-muted">Brak dostępnych pojazdów.</p>';
}

/**
 * Handles the booking form submission.
 */
async function submitBookingRequest(event) {
    event.preventDefault();
    clearErrors();

    if (!validateBookingForm()) return;

    const formData = new FormData(event.target);
    showLoadingIndicator();

    try {
        const response = await ajax.post('/booking/create', formData);
        hideLoadingIndicator();

        if (response.success) {
            showSuccessToast('Rezerwacja zakończona sukcesem!');
            setTimeout(() => window.location.href = "/bookings/view", 1500);
        } else {
            showErrorToast(response.error || 'Wystąpił problem podczas tworzenia rezerwacji.');
        }
    } catch (error) {
        hideLoadingIndicator();
        console.error('Błąd tworzenia rezerwacji:', error);
        showErrorToast('Nie udało się utworzyć rezerwacji.');
    }
}

/**
 * Initializes real-time validation for booking form fields.
 */
function initRealTimeValidation() {
    const requiredFields = ['pickup-date', 'return-date', 'pickup-location', 'dropoff-location'];

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.addEventListener('input', () => validateField(input));
        }
    });
}

/**
 * Validates a single form field.
 */
function validateField(input) {
    if (!input.value.trim()) {
        showError(input.dataset.errorMessage || 'To pole jest wymagane.');
    } else {
        clearError();
    }
}

/**
 * Validates the entire booking form before submission.
 */
function validateBookingForm() {
    let isValid = true;
    const requiredFields = ['pickup-date', 'return-date', 'pickup-location', 'dropoff-location'];

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input || !input.value.trim()) {
            showError(`Pole ${field.replace('-', ' ')} jest wymagane.`);
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Shows a loading indicator.
 */
function showLoadingIndicator() {
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
}

/**
 * Hides the loading indicator.
 */
function hideLoadingIndicator() {
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
}

/**
 * Displays an error message.
 */
function showError(message) {
    if (errorContainer) {
        errorContainer.innerText = message;
        errorContainer.style.display = 'block';
    }
}

/**
 * Clears all error messages.
 */
function clearErrors() {
    if (errorContainer) {
        errorContainer.innerText = '';
        errorContainer.style.display = 'none';
    }
}
