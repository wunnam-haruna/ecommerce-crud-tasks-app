<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/CheckoutTransportGuard.php';
shoppn_task13_require_secure_transport();
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';
require_once __DIR__ . '/../classes/PaystackClient.php';

require_login();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/payment.php'));
}
$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($submittedToken) || empty($_SESSION['checkout_csrf']) ||
    !hash_equals($_SESSION['checkout_csrf'], $submittedToken)) {
    $_SESSION['payment_error'] = 'Session expired. Please reload checkout and try again.';
    redirect(app_url('views/payment.php'));
}

$customerId = (int) $_SESSION['customer_id'];
$email = $_SESSION['customer_email'] ?? '';
$ip = get_ip();
$attempt = null;
$checkoutController = null;
try {
    if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new RuntimeException('Invalid customer or cart.');
    }
    $cart = new CartController();
    $items = $cart->getCartItems($ip);
    if (!$items) {
        throw new RuntimeException('Please add products to your cart first.');
    }
    $checkoutController = new CheckoutController();
    // Secure snapshot is saved BEFORE Paystack is initialized.
    $attempt = $checkoutController->createAttempt($customerId, $ip, $email, $items);
    $client = new PaystackClient();
    $paystack = $client->initialize($email, (int) $attempt['amount_pesewas'], $attempt['reference']);

    // Paystack access codes are NOT secret API keys. Keep the reference bound to this session.
    $_SESSION['task13_checkout'] = [
        'reference' => $attempt['reference'],
        'access_code' => $paystack['access_code'],
        'amount_pesewas' => $attempt['amount_pesewas'],
    ];
    redirect(app_url('views/paystack_checkout.php?reference=' . rawurlencode($attempt['reference'])));
} catch (Throwable $ex) {
    error_log('Task 13 initialization failed: ' . $ex->getMessage());
    if ($attempt && $checkoutController) {
        try {
            $checkoutController->markInitializationFailure($attempt['reference'], $customerId);
        } catch (Throwable $ignored) {
            error_log('Task 13 could not update failed initialization state.');
        }
    }
    $_SESSION['payment_error'] = 'Could not start a test payment. Check the Paystack test configuration and try again.';
    redirect(app_url('views/payment.php'));
}
