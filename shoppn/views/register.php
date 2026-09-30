<?php

require_once __DIR__ . '/../core/core.php';

if (is_logged_in()) {
    redirect('../index.php');
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

require __DIR__ . '/layout/header.php';

?>

<main>
    <h2>Customer Registration</h2>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form
        id="registerForm"
        action="../actions/register_action.php"
        method="POST"
        novalidate
    >

        <div>
            <label for="customer_name">Full Name</label><br>
            <input
                type="text"
                id="customer_name"
                name="customer_name"
                maxlength="100"
                required
            >
            <span class="error-message" id="nameError"></span>
        </div>

        <br>

        <div>
            <label for="customer_email">Email</label><br>
            <input
                type="email"
                id="customer_email"
                name="customer_email"
                maxlength="50"
                required
            >
            <span class="error-message" id="emailError"></span>
        </div>

        <br>

        <div>
            <label for="customer_pass">Password</label><br>
            <input
                type="password"
                id="customer_pass"
                name="customer_pass"
                required
            >
            <span class="error-message" id="passwordError"></span>
        </div>

        <br>

        <div>
            <label for="customer_country">Country</label><br>
            <select
                id="customer_country"
                name="customer_country"
                required
            >
                <option value="">Select country</option>
                <option value="Ghana">Ghana</option>
                <option value="Nigeria">Nigeria</option>
                <option value="Kenya">Kenya</option>
                <option value="South Africa">South Africa</option>
                <option value="Other">Other</option>
            </select>
            <span class="error-message" id="countryError"></span>
        </div>

        <br>

        <div>
            <label for="customer_city">City</label><br>
            <input
                type="text"
                id="customer_city"
                name="customer_city"
                maxlength="30"
                required
            >
            <span class="error-message" id="cityError"></span>
        </div>

        <br>

        <div>
            <label for="customer_contact">Contact Number</label><br>
            <input
                type="text"
                id="customer_contact"
                name="customer_contact"
                maxlength="15"
                required
            >
            <span class="error-message" id="contactError"></span>
        </div>

        <br>

        <button type="submit" id="registerButton">
            Register
        </button>

    </form>

    <p>
        Already have an account?
        <a href="login.php">Login</a>
    </p>
</main>

<script src="../js/validate.js"></script>

<?php require __DIR__ . '/layout/footer.php'; ?>
