document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const form = document.getElementById('registerForm');
    if (!form) return;

    const button = document.getElementById('registerButton');
    const password = document.getElementById('customer_pass');

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^[0-9+\-\s]{7,15}$/;

    // At least 8 characters, uppercase, lowercase, number and symbol.
    const passwordRegex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\p{P}\p{S}]).{8,}$/su;
    const encoder = new TextEncoder();

    function passwordError(value) {
        if (/[\x00-\x1F\x7F]/.test(value)) {
            return 'Password must not contain control characters or line breaks.';
        }

        if (encoder.encode(value).length > 72) {
            return 'Password is too long: maximum 72 bytes. Some characters use more than one byte.';
        }

        if (!passwordRegex.test(value)) {
            return 'Use at least 8 characters with an uppercase letter, a lowercase letter, a number, and a special character.';
        }

        return '';
    }

    function showError(field, errorId, message) {
        document.getElementById(errorId).textContent = message;
        field.setAttribute('aria-invalid', 'true');
    }

    function resetButton() {
        button.disabled = false;
        button.textContent = 'Create account';
    }

    // Re-enable the button when returning with the browser's Back button.
    window.addEventListener('pageshow', resetButton);

    // Update an existing password error while the customer corrects it.
    password.addEventListener('input', function () {
        if (password.getAttribute('aria-invalid') !== 'true') return;

        const message = passwordError(password.value);
        document.getElementById('passwordError').textContent = message;

        if (message === '') {
            password.removeAttribute('aria-invalid');
        }
    });

    form.addEventListener('submit', function (event) {
        form.querySelectorAll('.error-message').forEach(function (element) {
            element.textContent = '';
        });

        form.querySelectorAll('[aria-invalid]').forEach(function (element) {
            element.removeAttribute('aria-invalid');
        });

        let firstInvalid = null;

        function fail(field, errorId, message) {
            showError(field, errorId, message);
            if (firstInvalid === null) firstInvalid = field;
        }

        const name = document.getElementById('customer_name');
        const email = document.getElementById('customer_email');
        const country = document.getElementById('customer_country');
        const city = document.getElementById('customer_city');
        const contact = document.getElementById('customer_contact');

        if (name.value.trim() === '' || [...name.value.trim()].length > 100) {
            fail(name, 'nameError', 'Enter your full name, up to 100 characters.');
        }

        if (!emailRegex.test(email.value.trim()) || [...email.value.trim()].length > 50) {
            fail(email, 'emailError', 'Enter a valid email address, up to 50 characters.');
        }

        // Do not trim or otherwise change the password.
        const message = passwordError(password.value);

        if (message !== '') {
            fail(password, 'passwordError', message);
        }

        if (country.value === '') {
            fail(country, 'countryError', 'Please select a country.');
        }

        if (city.value.trim() === '' || [...city.value.trim()].length > 30) {
            fail(city, 'cityError', 'Enter your city, up to 30 characters.');
        }

        if (!phoneRegex.test(contact.value.trim())) {
            fail(contact, 'contactError', 'Enter a contact number using 7–15 characters: digits, spaces, + or -.');
        }

        if (firstInvalid !== null) {
            event.preventDefault();
            resetButton();
            firstInvalid.focus();
            return;
        }

        // Continue with the normal POST request. PHP validates again.
        button.disabled = true;
        button.textContent = 'Creating account...';
    });
});
