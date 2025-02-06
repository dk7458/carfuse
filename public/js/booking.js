import ajax from './ajax';

document.addEventListener('DOMContentLoaded', function () {
    const pickupDateInput = document.getElementById('pickup-date');
    const bookingForm = document.getElementById('booking-form');
    const errorContainer = document.getElementById('error-container');
    const loadingIndicator = document.getElementById('loading-indicator');

    if (pickupDateInput) pickupDateInput.addEventListener('change', fetchAvailableVehicles);
    if (bookingForm) bookingForm.addEventListener('submit', submitBookingRequest);

    /**
     * Pobiera dostępne pojazdy po wybraniu daty odbioru.
     */
    async function fetchAvailableVehicles() {
        const pickupDate = pickupDateInput.value.trim();
        if (!pickupDate) return;

        showLoadingIndicator();

        try {
            const response = await fetch(`/vehicles/available?pickup_date=${pickupDate}`);
            const data = await response.json();
            hideLoadingIndicator();

            if (data.vehicles && data.vehicles.length > 0) {
                displayAvailableVehicles(data.vehicles);
            } else {
                showError('Brak dostępnych pojazdów na wybrany termin.');
            }
        } catch (error) {
            hideLoadingIndicator();
            console.error('Błąd pobierania dostępnych pojazdów:', error);
            showError('Nie udało się pobrać dostępnych pojazdów.');
        }
    }

    /**
     * Wyświetla dostępne pojazdy w interfejsie użytkownika.
     */
    function displayAvailableVehicles(vehicles) {
        const vehiclesContainer = document.getElementById('vehicles-container');
        vehiclesContainer.innerHTML = '';

        vehicles.forEach(vehicle => {
            const vehicleElement = document.createElement('div');
            vehicleElement.className = 'vehicle';
            vehicleElement.innerHTML = `
                <p>${vehicle.name}</p>
                <p>${vehicle.type}</p>
            `;
            vehiclesContainer.appendChild(vehicleElement);
        });
    }

    /**
     * Sprawdza poprawność lokalizacji odbioru i zwrotu.
     */
    function validateLocations() {
        const pickupLocation = document.getElementById('pickup-location').value.trim();
        const dropoffLocation = document.getElementById('dropoff-location').value.trim();

        if (!pickupLocation || !dropoffLocation) {
            showError('Miejsce odbioru i zwrotu są wymagane.');
            return false;
        }

        return true;
    }

    /**
     * Waliduje formularz przed wysłaniem.
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
     * Obsługuje przesyłanie formularza rezerwacji.
     */
    async function submitBookingRequest(event) {
        event.preventDefault();
        clearErrors();

        if (!validateLocations() || !validateBookingForm()) return;

        const formData = new FormData(bookingForm);

        showLoadingIndicator();

        try {
            const response = await fetch('/booking/create', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            hideLoadingIndicator();

            if (data.success) {
                alert('Rezerwacja zakończona sukcesem!');
                window.location.href = "/bookings/view";
            } else {
                showError(data.error || 'Wystąpił problem podczas tworzenia rezerwacji.');
            }
        } catch (error) {
            hideLoadingIndicator();
            console.error('Błąd tworzenia rezerwacji:', error);
            showError('Nie udało się utworzyć rezerwacji.');
        }
    }

    /**
     * Pokazuje wskaźnik ładowania.
     */
    function showLoadingIndicator() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
        }
    }

    /**
     * Ukrywa wskaźnik ładowania.
     */
    function hideLoadingIndicator() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }

    /**
     * Wyświetla komunikat o błędzie.
     */
    function showError(message) {
        if (errorContainer) {
            errorContainer.innerText = message;
            errorContainer.style.display = 'block';
        }
    }

    /**
     * Czyści komunikaty o błędach.
     */
    function clearErrors() {
        if (errorContainer) {
            errorContainer.innerText = '';
            errorContainer.style.display = 'none';
        }
    }
});
