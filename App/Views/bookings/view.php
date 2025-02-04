<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Szczegóły rezerwacji</h1>

<div class="booking-container">
    <div class="card shadow p-4">
        <h3 class="text-center">Informacje o rezerwacji</h3>
        <table class="table table-bordered">
            <tr>
                <th>ID Rezerwacji</th>
                <td id="bookingId">Ładowanie...</td>
            </tr>
            <tr>
                <th>Pojazd</th>
                <td id="vehicleName">Ładowanie...</td>
            </tr>
            <tr>
                <th>Data odbioru</th>
                <td id="pickupDate">Ładowanie...</td>
            </tr>
            <tr>
                <th>Data zwrotu</th>
                <td id="returnDate">Ładowanie...</td>
            </tr>
            <tr>
                <th>Status</th>
                <td id="bookingStatus">Ładowanie...</td>
            </tr>
            <tr>
                <th>Płatność</th>
                <td id="paymentStatus">Ładowanie...</td>
            </tr>
        </table>
    </div>

    <div class="text-center mt-4">
        <button id="cancelBookingBtn" class="btn btn-danger">Anuluj rezerwację</button>
        <button id="rescheduleBookingBtn" class="btn btn-warning">Zmień termin</button>
    </div>
</div>

<script src="/js/bookings.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
