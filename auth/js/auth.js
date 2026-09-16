// Client-side convenience validation for auth forms.
// Server-side validation in the PHP files is authoritative and re-checks everything.
document.addEventListener('DOMContentLoaded', function () {
    var pwd = document.getElementById('password');
    var confirm = document.getElementById('confirm_password');
    var form = document.getElementById('registerForm');

    var policy = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    function validate() {
        if (!pwd) return true;
        var ok = policy.test(pwd.value);
        pwd.style.borderColor = pwd.value && !ok ? '#C62828' : '';
        if (confirm) {
            var match = confirm.value === pwd.value;
            confirm.style.borderColor = confirm.value && !match ? '#C62828' : '';
        }
        return ok;
    }

    if (pwd) pwd.addEventListener('input', validate);
    if (confirm) confirm.addEventListener('input', validate);

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validate() || (confirm && confirm.value !== pwd.value)) {
                e.preventDefault();
                alert('Please check your password meets the policy and both fields match.');
            }
        });
    }
});
