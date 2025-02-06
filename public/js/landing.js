document.addEventListener("DOMContentLoaded", function() {
    const pickupDate = document.getElementById("pickup_date");
    const returnDate = document.getElementById("return_date");
    const dateError = document.getElementById("dateError");

    function validateDates() {
        const pickup = new Date(pickupDate.value);
        const returnD = new Date(returnDate.value);

        if (returnD < pickup) {
            dateError.style.display = "block";
            return false;
        }
        dateError.style.display = "none";
        return true;
    }
});
