import { fetchData, handleApiError, showAlert } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/bookings.js
 * Description: Manages booking operations
 * Changelog:
 * - Refactored to use centralized fetchData utility
 * - Added loading indicators and error handling
 */

document.addEventListener("DOMContentLoaded", () => {
    // User Actions
    const checkAvailabilityButton = document.getElementById("checkAvailability");
    const confirmBookingButton = document.getElementById("confirmBooking");

    checkAvailabilityButton?.addEventListener("click", async (e) => {
        e.preventDefault();
        try {
            const pickupDate = document.getElementById("pickupDate").value;
            const dropoffDate = document.getElementById("dropoffDate").value;

            const data = await fetchData('/public/api.php', {
                endpoint: 'booking',
                method: 'POST',
                body: {
                    action: 'check_availability',
                    pickup_date: pickupDate,
                    dropoff_date: dropoffDate
                }
            });

            const carsContainer = document.getElementById("availableCars");
            carsContainer.innerHTML = data.cars.map(car => `
                <div class="car-tile">
                    <img src="${car.image_path}" alt="${car.make} ${car.model}">
                    <h3>${car.make} ${car.model}</h3>
                    <p>Price per day: ${car.price_per_day} PLN</p>
                    <button class="select-car" data-id="${car.id}">Select</button>
                </div>
            `).join('');
        } catch (error) {
            handleApiError(error, 'checking availability');
        }
    });

    confirmBookingButton?.addEventListener("click", async (e) => {
        e.preventDefault();
        try {
            const vehicleId = document.getElementById("vehicleId").value;
            const pickupDate = document.getElementById("pickupDate").value;
            const dropoffDate = document.getElementById("dropoffDate").value;
            const totalPrice = document.getElementById("totalPrice").textContent;

            const data = await fetchData('/public/api.php', {
                endpoint: 'booking',
                method: 'POST',
                body: {
                    action: 'create_booking',
                    vehicle_id: vehicleId,
                    pickup_date: pickupDate,
                    dropoff_date: dropoffDate,
                    total_price: totalPrice
                }
            });

            window.location.href = `/views/user/booking_confirmation.php?booking_id=${data.booking_id}`;
        } catch (error) {
            handleApiError(error, 'creating booking');
        }
    });

    // Admin Actions
    document.querySelectorAll(".refund-button").forEach((button) => {
        button.addEventListener("click", async () => {
            const bookingId = button.dataset.id;
            const refundAmount = button.dataset.amount;

            if (confirm(`Confirm refund of ${refundAmount} PLN?`)) {
                try {
                    const data = await fetchData('/public/api.php', {
                        endpoint: 'booking_manager',
                        method: 'POST',
                        body: {
                            action: 'refund',
                            booking_id: bookingId,
                            refund_amount: refundAmount
                        }
                    });

                    showAlert('Refund processed successfully', 'success');
                    location.reload();
                } catch (error) {
                    handleApiError(error, 'processing refund');
                }
            }
        });
    });
});
