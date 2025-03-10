function togglePasswordVisibility(passwordFieldId = 'password', eyeIconId = 'eye') {
    var passwordField = document.getElementById(passwordFieldId);
    var eyeIcon = document.getElementById(eyeIconId);
    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.classList.remove("fa-eye-slash");
        eyeIcon.classList.add("fa-eye");
    } else {
        passwordField.type = "password";
        eyeIcon.classList.remove("fa-eye");
        eyeIcon.classList.add("fa-eye-slash");
    }
}
