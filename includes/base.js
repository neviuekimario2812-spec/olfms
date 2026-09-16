// Global behaviour shared by every page (mobile nav toggle, confirm dialogs)
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('siteNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    // Generic "confirm before submit" for any form/link carrying data-confirm
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Auto-dismiss alerts after 6 seconds
    document.querySelectorAll('.alert').forEach(function (a) {
        setTimeout(function () { a.style.display = 'none'; }, 6000);
    });
});
