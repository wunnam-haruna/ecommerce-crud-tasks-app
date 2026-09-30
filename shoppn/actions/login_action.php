<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(app_url('views/login.php'));
}

// Sanitize inputs
$email = trim($_POST['customer_email'] ?? '');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

$password = $_POST['customer_pass'] ?? '';

// Validate required fields
if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email and password are required.';
    redirect(app_url('views/login.php'));
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    redirect(app_url('views/login.php'));
}

// Ask controller to authenticate customer
$controller = new CustomerController();
$result = $controller->login($email, $password);

if (!$result['success']) {
    $_SESSION['error'] = $result['error'] ?? 'Login failed.';
    redirect(app_url('views/login.php'));
}

// Successful login
$customer = $result['customer'];

$_SESSION['customer_id'] = $customer['customer_id'];
$_SESSION['customer_name'] = $customer['customer_name'];
$_SESSION['customer_email'] = $customer['customer_email'];
$_SESSION['user_role'] = $customer['user_role'];

redirect(app_url('index.php'));

?>
