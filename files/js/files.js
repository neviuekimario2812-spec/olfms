// Early client-side check for allowed extension and size before upload.
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('document');
    if (!input) return;
    input.addEventListener('change', function () {
        var file = input.files[0];
        if (!file) return;
        var ext = file.name.split('.').pop().toLowerCase();
        if (['pdf', 'png'].indexOf(ext) === -1) {
            alert('Only PDF and PNG files are allowed.');
            input.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert('This file is larger than 10MB and will be rejected.');
            input.value = '';
        }
    });
});
