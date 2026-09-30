document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('registerForm');

    if (!form) {
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneRegex = /^[0-9+\-\s]{7,15}$/;

    form.addEventListener('submit', function (event) {

        let valid = true;

        const name = document.getElementById('customer_name');
        const email = document.getElementById('customer_email');
        const password = document.getElementById('customer_pass');
        const country = document.getElementById('customer_country');
        const city = document.getElementById('customer_city');
        const contact = document.getElementById('customer_contact');

        clearErrors();

        if (name.value.trim() === '') {
            showError('nameError', 'Full name is required.');
            valid = false;
        }

        if (!emailRegex.test(email.value.trim())) {
            showError('emailError', 'Enter a valid email address.');
            valid = false;
        }

        if (password.value.length < 6) {
            showError(
                'passwordError',
                'Password must contain at least 6 characters.'
            );
            valid = false;
        }

        if (country.value === '') {
            showError('countryError', 'Please select a country.');
            valid = false;
        }

        if (city.value.trim() === '') {
            showError('cityError', 'City is required.');
            valid = false;
        }

        if (!phoneRegex.test(contact.value.trim())) {
            showError(
                'contactError',
                'Enter a valid contact number.'
            );
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
            return;
        }

        const button = document.getElementById('registerButton');

        button.disabled = true;
        button.textContent = 'Registering...';
    });


    function showError(id, message) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = message;
            element.style.color = 'red';
            element.style.marginLeft = '8px';
        }
    }


    function clearErrors() {
        const errors = document.querySelectorAll('.error-message');

        errors.forEach(function (error) {
            error.textContent = '';
        });
    }

});
