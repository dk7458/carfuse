import ajax from './ajax';

document.addEventListener('DOMContentLoaded', function() {
    const pickupDateInput = document.getElementById('pickup-date');
    const bookingForm = document.getElementById('booking-form');

    pickupDateInput.addEventListener('change', fetchAvailableVehicles);
    bookingForm.addEventListener('submit', submitBookingRequest);

    // Fetch available vehicles when users select a pickup date
    function fetchAvailableVehicles() {
        const pickupDate = pickupDateInput.value;
        if (!pickupDate) return;

        showLoadingIndicator();

        fetch(`/vehicles/available?pickup_date=${pickupDate}`)
            .then(response => response.json())
            .then(data => {
                hideLoadingIndicator();
                if (data.vehicles && data.vehicles.length > 0) {
                    displayAvailableVehicles(data.vehicles);
                } else {
                    showError('No vehicles available for the selected date.');
                }
            })
            .catch(error => {
                hideLoadingIndicator();
                console.error('Error fetching available vehicles:', error);
                showError('Error fetching available vehicles.');
            });
    }

    // Display available vehicles on the UI
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

    // Function to handle real-time vehicle availability updates
    function updateVehicleAvailability() {
        // Ensure real-time updates are handled properly
        // Example: Fetch availability from server and update UI
    }

    // Validate pickup & drop-off locations before submission
    function validateLocations() {
        const pickupLocation = document.getElementById('pickup-location').value;
        const dropoffLocation = document.getElementById('dropoff-location').value;

        if (!pickupLocation || !dropoffLocation) {
            showError('Pickup and drop-off locations are required.');
            return false;
        }

        return true;
    }

    // Form validation before submission
    function validateBookingForm() {
        let isValid = true;
        // Ensure all required fields are validated
        // Example: Check if all required fields are filled
        return isValid;
    }

    // Submit booking requests via AJAX to /booking/create API
    function submitBookingRequest(event) {
        event.preventDefault();

        if (!validateLocations()) return;

        const formData = new FormData(bookingForm);

        showLoadingIndicator();

        fetch('/booking/create', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingIndicator();
            if (data.success) {
                // Handle successful booking
                alert('Booking successful!');
            } else {
                showError(data.error || 'Error creating booking.');
            }
        })
        .catch(error => {
            hideLoadingIndicator();
            console.error('Error creating booking:', error);
            showError('Error creating booking.');
        });
    }

    // Example usage in booking.js
    async function createBooking(bookingDetails) {
        try {
            const response = await ajax.post('/bookings', bookingDetails);
            // ...handle successful booking...
        } catch (error) {
            // ...handle booking error...
        }
    }

    // Show loading indicator
    function showLoadingIndicator() {
        const loadingIndicator = document.getElementById('loading-indicator');
        loadingIndicator.style.display = 'block';
    }

    // Hide loading indicator
    function hideLoadingIndicator() {
        const loadingIndicator = document.getElementById('loading-indicator');
        loadingIndicator.style.display = 'none';
    }

    // Show error messages
    function showError(message) {
        const errorContainer = document.getElementById('error-container');
        errorContainer.innerText = message;
        errorContainer.style.display = 'block';
    }

    // Display error messages correctly
    function displayErrorMessage(message) {
        // Ensure error messages are shown to the user
        // Example: Update error message element with the provided message
    }
});
