// Ensures the date picker never allows a past date (server also re-validates).
document.addEventListener('DOMContentLoaded', function () {
    var dateInput = document.getElementById('appointment_date');
    if (dateInput) {
        var today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
});
