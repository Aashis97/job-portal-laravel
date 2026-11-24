"use strict";

document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll(".toggle-password").forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const targetId = toggle.dataset.target;
            const target = targetId ? document.getElementById(targetId) : toggle.closest(".input-group")?.querySelector("input");
            const icon = toggle.querySelector("i");

            if (!target || !icon) {
                return;
            }

            if (target.type === "password") {
                target.type = "text";
                if (icon.classList.contains("fa-eye")) {
                    icon.classList.replace("fa-eye", "fa-eye-slash");
                }
            } else {
                target.type = "password";
                if (icon.classList.contains("fa-eye-slash")) {
                    icon.classList.replace("fa-eye-slash", "fa-eye");
                }
            }
        });
    });

    const password = document.getElementById("password");
    const strengthBar = document.getElementById("strengthBar");
    const strengthText = document.getElementById("passwordStrengthText");

    if (password && strengthBar && strengthText) {
        password.addEventListener("keyup", () => {
            const value = password.value;
            let score = 0;

            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[a-z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            const width = (score / 5) * 100;
            strengthBar.style.width = width + "%";

            switch (score) {
                case 0:
                case 1:
                    strengthBar.style.background = "#ef4444";
                    strengthText.textContent = "Weak Password";
                    break;
                case 2:
                case 3:
                    strengthBar.style.background = "#f59e0b";
                    strengthText.textContent = "Medium Password";
                    break;
                case 4:
                case 5:
                    strengthBar.style.background = "#22c55e";
                    strengthText.textContent = "Strong Password";
                    break;
            }
        });
    }

    const confirmPassword = document.getElementById("confirmPassword");
    const match = document.getElementById("passwordMatch");

    if (confirmPassword && match) {
        confirmPassword.addEventListener("keyup", () => {
            if (confirmPassword.value === "") {
                match.textContent = "";
                return;
            }

            const currentPassword = password ? password.value : "";

            if (currentPassword === confirmPassword.value) {
                match.textContent = "✓ Password Matched";
                match.className = "success-text";
            } else {
                match.textContent = "✗ Password Not Matched";
                match.className = "error-text";
            }
        });
    }

    const phone = document.getElementById("phone");

    if (phone) {
        phone.addEventListener("keyup", () => {
            const error = document.getElementById("phoneError");
            const pattern = /^(98|97)\d{8}$/;

            if (error) {
                if (pattern.test(phone.value)) {
                    error.textContent = "";
                } else {
                    error.textContent = "Enter a valid Nepal mobile number.";
                }
            }
        });
    }

    const registrationForm = document.getElementById("registerForm");

    if (registrationForm) {
        const roleInputs = registrationForm.querySelectorAll('input[name="role"]');
        const employerFields = registrationForm.querySelector('[data-role-fields="employer"]');
        const jobSeekerFields = registrationForm.querySelector('[data-role-fields="job_seeker"]');
        const fullNameLabel = registrationForm.querySelector('label[for="fullName"]');
        const emailLabel = registrationForm.querySelector('label[for="email"]');
        const companyName = document.getElementById("companyName");
        const companyEmail = document.getElementById("companyEmail");
        const seekerPhone = document.getElementById("phone");
        const employerPhone = document.getElementById("companyPhone");

        const updateRegistrationFields = () => {
            const selectedRole = registrationForm.querySelector('input[name="role"]:checked')?.value;
            const isEmployer = selectedRole === "employer";

            if (employerFields) employerFields.hidden = !isEmployer;
            if (jobSeekerFields) jobSeekerFields.hidden = isEmployer;
            if (fullNameLabel) fullNameLabel.textContent = isEmployer ? "Employer Full Name" : "Full Name";
            if (emailLabel) emailLabel.textContent = isEmployer ? "Work Email Address" : "Email Address";
            if (companyName) companyName.required = isEmployer;
            if (companyEmail) companyEmail.required = isEmployer;
            if (seekerPhone) seekerPhone.disabled = isEmployer;
            if (employerPhone) employerPhone.disabled = !isEmployer;
        };

        roleInputs.forEach((input) => input.addEventListener("change", updateRegistrationFields));
        updateRegistrationFields();
    }

});