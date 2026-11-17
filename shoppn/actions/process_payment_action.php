<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../classes/PaystackClient.php';

require_login();
header('Cache-Control: no-store, private');

$reference = $_GET['reference'] ?? '';
$sessionCheckout = $_SESSION['task13_checkout'] ?? null;
if (!is_string($reference) || !preg_match('/\A[A-Za-z0-9._=-]{5,120}\z/D', $reference) ||
    !is_array($sessionCheckout) ||
    !hash_equals((string) ($sessionCheckout['reference'] ?? ''), $reference)) {
    $_SESSION['payment_failure'] = 'Your checkout session has expired. Please retry.';
    redirect(app_url('views/payment_failed.php'));
}

$customerId = (int) $_SESSION['customer_id'];
$ip = get_ip();
try {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new RuntimeException('Invalid cart identifier.');
    }
    $checkout = new CheckoutController();
    $attempt = $checkout->getAttempt($reference, $customerId);
    if (!$attempt || $attempt['ip_add'] !== $ip || $attempt['currency'] !== 'GHS') {
        throw new RuntimeException('Checkout attempt not found or invalid.');
    }

    if ($attempt['status'] === 'paid' && (int) $attempt['order_id'] > 0) {
        redirect(app_url('views/payment_success.php?order_id=' . (int) $attempt['order_id']));
    }
    if ($attempt['status'] !== 'pending') {
        throw new RuntimeException('Checkout attempt not pending.');
    }

    // IMPORTANT: Verification happens here, on the SERVER, using Paystack's secret key.
    $verifiedData = (new PaystackClient())->verify($reference);

    // processPayment checks reference, domain=test, success, GHS, amount and payer email;
    // it also locks checkout_attempts and prevents duplicate orders.
    $cart = new CartController();
    $orderId = $cart->processPayment(
        $customerId, $ip, $reference, (int) $attempt['amount_pesewas'], $verifiedData
    );
    unset($_SESSION['task13_checkout']);
    redirect(app_url('views/payment_success.php?order_id=' . (int) $orderId));
} catch (Throwable $ex) {
    error_log('Task 13 payment verification: ' . $ex->getMessage());
    $_SESSION['payment_failure'] =
        'Payment is not verified as successful. No new paid order was recorded.';
    redirect(app_url('views/payment_failed.php'));
}
