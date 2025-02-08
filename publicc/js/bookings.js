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
        const response = await fetch(`/api/user/bookings.php?pickup_date=${pickupDate}`);
        const data = await response.json();
        hideLoadingIndicator();

        if (data.vehicles && data.vehicles.length > 0) {
            displayAvailableVehicles(data.vehicles);
        } else {
            showErrorToast('Brak dostępnych pojazdów na wybrany termin.');
            clearVehicles();
            fetchAlternativeDates(pickupDate);
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
 * Fetches alternative dates with available vehicles
 */
async function fetchAlternativeDates(pickupDate) {
    try {
        const response = await fetch(`/api/user/bookings.php?alternative_dates=true&pickup_date=${pickupDate}`);
        const data = await response.json();
        if (data.alternativeDates && data.alternativeDates.length > 0) {
            displayAlternativeDates(data.alternativeDates, pickupDate);
        } else {
            showErrorToast('Brak alternatywnych terminów.');
        }
    } catch (error) {
        console.error('Błąd pobierania alternatywnych terminów:', error);
        showErrorToast('Nie udało się pobrać alternatywnych terminów.');
    }
}

/**
 * Displays alternative dates in the UI
 */
function displayAlternativeDates(alternativeDates, originalDate) {
    vehiclesContainer.innerHTML = '<p class="text-muted">Brak dostępnych pojazdów.</p>';

    const alternativesContainer = document.createElement('div');
    alternativesContainer.className = 'alternatives';

    const title = document.createElement('h5');
    title.innerText = 'Dostępne alternatywne terminy:';
    alternativesContainer.appendChild(title);

    alternativeDates.forEach(date => {
        const dateElement = document.createElement('button');
        dateElement.className = 'alternative-date';
        dateElement.innerText = date;
        dateElement.addEventListener('click', () => {
            document.getElementById('pickup-date').value = date;
            fetchAvailableVehicles();
        });
        alternativesContainer.appendChild(dateElement);
    });

    const notifyButton = document.createElement('button');
    notifyButton.className = 'notify-button';
    notifyButton.innerText = 'Powiadom mnie, gdy pojazdy będą dostępne';
    notifyButton.addEventListener('click', () => setNotificationAlert(originalDate));
    alternativesContainer.appendChild(notifyButton);

    vehiclesContainer.appendChild(alternativesContainer);
}

/**
 * Sets a notification alert for the user
 */
async function setNotificationAlert(pickupDate) {
    try {
        const response = await fetch('/api/user/bookings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pickup_date: pickupDate })
        });
        const data = await response.json();
        if (data.success) {
            showSuccessToast('Powiadomienie zostało ustawione.');
        } else {
            showErrorToast(data.error || 'Nie udało się ustawić powiadomienia.');
        }
    } catch (error) {
        console.error('Błąd ustawiania powiadomienia:', error);
        showErrorToast('Nie udało się ustawić powiadomienia.');
    }
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
        const response = await fetch('/api/user/bookings.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        hideLoadingIndicator();

        if (data.success) {
            showSuccessToast('Rezerwacja zakończona sukcesem!');
            setTimeout(() => window.location.href = "/bookings/view", 1500);
        } else {
            showErrorToast(data.error || 'Wystąpił problem podczas tworzenia rezerwacji.');
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
