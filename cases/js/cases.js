// Warn early if the chosen evidence file exceeds the 10MB server limit.
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('evidence');
    if (!input) return;
    input.addEventListener('change', function () {
        var file = input.files[0];
        if (file && file.size > 10 * 1024 * 1024) {
            alert('This file is larger than 10MB and will be rejected. Please choose a smaller file.');
            input.value = '';
        }
    });
});
