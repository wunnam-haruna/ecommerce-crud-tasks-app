<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// Only accept form submissions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../views/register.php');
}


// Read and sanitise form input
$name = trim(strip_tags($_POST['customer_name'] ?? ''));

$email = trim($_POST['customer_email'] ?? '');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

$password = $_POST['customer_pass'] ?? '';

$country = trim(strip_tags($_POST['customer_country'] ?? ''));
$city = trim(strip_tags($_POST['customer_city'] ?? ''));
$contact = trim(strip_tags($_POST['customer_contact'] ?? ''));


// Required fields
if (
    $name === '' ||
    $email === '' ||
    $password === '' ||
    $country === '' ||
    $city === '' ||
    $contact === ''
) {
    $_SESSION['error'] = 'All required fields must be filled.';
    redirect('../views/register.php');
}


// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    redirect('../views/register.php');
}


// Validate lengths against the database schema
if (strlen($name) > 100) {
    $_SESSION['error'] = 'Name must not exceed 100 characters.';
    redirect('../views/register.php');
}

if (strlen($email) > 50) {
    $_SESSION['error'] = 'Email must not exceed 50 characters.';
    redirect('../views/register.php');
}

if (strlen($country) > 30) {
    $_SESSION['error'] = 'Country must not exceed 30 characters.';
    redirect('../views/register.php');
}

if (strlen($city) > 30) {
    $_SESSION['error'] = 'City must not exceed 30 characters.';
    redirect('../views/register.php');
}

if (strlen($contact) > 15) {
    $_SESSION['error'] = 'Contact number must not exceed 15 characters.';
    redirect('../views/register.php');
}


// Register through the Controller
$controller = new CustomerController();

$result = $controller->register([
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'country' => $country,
    'city' => $city,
    'contact' => $contact
]);


if ($result['success']) {

    // Automatically log the customer in after registration
    $_SESSION['customer_id'] = $result['customer_id'];
    $_SESSION['user_role'] = $result['user_role'];
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_email'] = $email;

    redirect('../views/account/my_account.php');
}


// Registration failed
$_SESSION['error'] = $result['error'] ?? 'Registration failed.';

redirect('../views/register.php');

?>
