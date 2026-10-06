<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// Store an error message, then return to the registration form.
function registration_error($message)
{
    $_SESSION['error'] = $message;
    redirect(app_url('views/register.php'));
}

// Only process submitted registration forms.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/register.php'));
}

// Reject missing fields and array values before processing them.
$fields = [
    'customer_name',
    'customer_email',
    'customer_pass',
    'customer_country',
    'customer_city',
    'customer_contact'
];

foreach ($fields as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
        registration_error('Please complete all required fields.');
    }
}

// Read the form fields.
$name = trim(strip_tags($_POST['customer_name']));
$email = trim($_POST['customer_email']);
$country = trim(strip_tags($_POST['customer_country']));
$city = trim(strip_tags($_POST['customer_city']));
$contact = trim(strip_tags($_POST['customer_contact']));

// Keep the password exactly as entered.
// Do not trim it, remove symbols, or apply strip_tags().
$password = $_POST['customer_pass'];

if (
    $name === '' ||
    $email === '' ||
    $password === '' ||
    $country === '' ||
    $city === '' ||
    $contact === ''
) {
    registration_error('All required fields must be filled.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    registration_error('Please enter a valid email address.');
}

// Check field lengths against the existing database schema.
$limits = [
    'Full name' => [$name, 100],
    'Email' => [$email, 50],
    'Country' => [$country, 30],
    'City' => [$city, 30],
    'Contact number' => [$contact, 15]
];

foreach ($limits as $label => [$value, $maximum]) {
    if (preg_match('/\A.{1,' . $maximum . '}\z/us', $value) !== 1) {
        registration_error(
            $label . ' must contain valid text and be no longer than '
            . $maximum . ' characters.'
        );
    }
}

// Bcrypt processes at most 72 bytes. Reject longer passwords.
if (strlen($password) > 72) {
    registration_error(
        'Password is too long. Use no more than 72 bytes; '
        . 'some characters use more than one byte.'
    );
}

// Reject control characters, including null bytes and line breaks.
if (preg_match('/[\x00-\x1F\x7F]/', $password)) {
    registration_error(
        'Password must not contain control characters or line breaks.'
    );
}

// Password requirements:
// - At least 8 characters
// - At least one uppercase letter
// - At least one lowercase letter
// - At least one number
// - At least one punctuation mark or symbol
// Spaces do not count as special characters.
$passwordRegex = '/\A(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\p{P}\p{S}]).{8,}\z/u';

if (preg_match($passwordRegex, $password) !== 1) {
    registration_error(
        'Password must contain at least 8 characters, including '
        . 'an uppercase letter, a lowercase letter, a number, '
        . 'and a special character such as !, @, #, or $.'
    );
}

// The existing controller checks duplicate emails
// and hashes the password before passing it to the model.
try {
    $controller = new CustomerController();

    $result = $controller->register([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'country' => $country,
        'city' => $city,
        'contact' => $contact
    ]);
} catch (mysqli_sql_exception $exception) {
    // Also handle duplicate emails detected by the database.
    if ((int) $exception->getCode() === 1062) {
        registration_error('Email already registered.');
    }

    error_log(
        'Registration database error code: ' . $exception->getCode()
    );

    registration_error(
        'Registration could not be completed. Please try again later.'
    );
} catch (Throwable $exception) {
    error_log('Registration failed: ' . get_class($exception));

    registration_error(
        'Registration could not be completed. Please try again later.'
    );
}

if (empty($result['success'])) {
    registration_error($result['error'] ?? 'Registration failed.');
}

// Start a fresh session ID when signing in the new customer.
if (!session_regenerate_id(true)) {
    registration_error(
        'Your account was created, but automatic sign-in failed. '
        . 'Please use the Login page.'
    );
}

$_SESSION['customer_id'] = (int) $result['customer_id'];
$_SESSION['user_role'] = 2;
$_SESSION['customer_name'] = $name;
$_SESSION['customer_email'] = $email;

unset($_SESSION['error']);

redirect(app_url('views/account/my_account.php'));
