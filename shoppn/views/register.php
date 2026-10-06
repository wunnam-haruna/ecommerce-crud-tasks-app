<?php

require_once __DIR__ . '/../core/core.php';

if (is_logged_in()) {
    redirect(app_url('index.php'));
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require __DIR__ . '/layout/header.php';
?>

<main class="auth-page">
    <section class="auth-card" aria-labelledby="registrationTitle">

        <h2 id="registrationTitle">Create your Shoppn account</h2>
        <p>Enter your details below. All fields are required.</p>

        <?php if ($error !== ''): ?>
            <p class="alert alert-error" role="alert">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form
            id="registerForm"
            class="auth-form"
            method="POST"
            action="<?php echo htmlspecialchars(app_url('actions/register_action.php'), ENT_QUOTES, 'UTF-8'); ?>"
            novalidate
        >

            <div class="form-field">
                <label for="customer_name">Full Name</label><br>
                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    maxlength="100"
                    autocomplete="name"
                    aria-describedby="nameError"
                    required
                >
                <span class="error-message" id="nameError" aria-live="polite"></span>
            </div>

            <br>

            <div class="form-field">
                <label for="customer_email">Email</label><br>
                <input
                    type="email"
                    id="customer_email"
                    name="customer_email"
                    maxlength="50"
                    autocomplete="email"
                    aria-describedby="emailError"
                    required
                >
                <span class="error-message" id="emailError" aria-live="polite"></span>
            </div>

            <br>

            <div class="form-field">
                <label for="customer_pass">Password</label><br>
                <input
                    type="password"
                    id="customer_pass"
                    name="customer_pass"
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="passwordHelp passwordError"
                    required
                >

                <p id="passwordHelp" class="field-help">
                    Use at least 8 characters with an uppercase letter (A–Z),
                    a lowercase letter (a–z), a number, and a special character
                    such as !, @, #, or $. Spaces do not count as special characters.
                    Maximum 72 bytes; some characters use more than one byte.
                </p>

                <span class="error-message" id="passwordError" aria-live="polite"></span>
            </div>

            <br>

            <div class="form-field">
                <label for="customer_country">Country</label><br>
                <select
                    id="customer_country"
                    name="customer_country"
                    autocomplete="country-name"
                    aria-describedby="countryError"
                    required
                >
                    <option value="">Select country</option>
                    <option value="Ghana">Ghana</option>
                    <option value="Nigeria">Nigeria</option>
                    <option value="Kenya">Kenya</option>
                    <option value="South Africa">South Africa</option>
                    <option value="Other">Other</option>
                </select>

                <span class="error-message" id="countryError" aria-live="polite"></span>
            </div>

            <br>

            <div class="form-field">
                <label for="customer_city">City</label><br>
                <input
                    type="text"
                    id="customer_city"
                    name="customer_city"
                    maxlength="30"
                    autocomplete="address-level2"
                    aria-describedby="cityError"
                    required
                >
                <span class="error-message" id="cityError" aria-live="polite"></span>
            </div>

            <br>

            <div class="form-field">
                <label for="customer_contact">Contact Number</label><br>
                <input
                    type="tel"
                    id="customer_contact"
                    name="customer_contact"
                    maxlength="15"
                    autocomplete="tel"
                    aria-describedby="contactError"
                    required
                >
                <span class="error-message" id="contactError" aria-live="polite"></span>
            </div>

            <br>

            <button type="submit" id="registerButton">
                Create account
            </button>

        </form>

        <p>
            Already have an account?
            <a href="<?php echo htmlspecialchars(app_url('views/login.php'), ENT_QUOTES, 'UTF-8'); ?>">
                Login
            </a>
        </p>

    </section>
</main>

<script
    src="<?php echo htmlspecialchars(app_url('js/validate.js'), ENT_QUOTES, 'UTF-8'); ?>?v=2"
    defer
></script>

<?php require __DIR__ . '/layout/footer.php'; ?>
