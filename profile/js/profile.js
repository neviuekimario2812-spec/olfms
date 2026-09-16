document.addEventListener('DOMContentLoaded', function () {
    var newPwd = document.getElementById('new_password');
    var confirm = document.getElementById('confirm_password');
    if (!newPwd || !confirm) return;
    function check() {
        confirm.style.borderColor = confirm.value && confirm.value !== newPwd.value ? '#C62828' : '';
    }
    newPwd.addEventListener('input', check);
    confirm.addEventListener('input', check);
});
