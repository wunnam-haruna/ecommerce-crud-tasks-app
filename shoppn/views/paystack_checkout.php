<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';

require_login();
header('Cache-Control: no-store, private');

$reference = $_GET['reference'] ?? '';
$sessionCheckout = $_SESSION['task13_checkout'] ?? null;
if (!is_string($reference) || !is_array($sessionCheckout) ||
    !hash_equals((string) ($sessionCheckout['reference'] ?? ''), $reference)) {
    $_SESSION['payment_error'] = 'The checkout session is no longer available.';
    redirect(app_url('views/payment.php'));
}
$checkoutController = new CheckoutController();
$attempt = $checkoutController->getAttempt($reference, (int) $_SESSION['customer_id']);
if (!$attempt || $attempt['status'] !== 'pending' ||
    !hash_equals((string) $attempt['ip_add'], get_ip())) {
    $_SESSION['payment_error'] = 'The checkout is no longer pending.';
    redirect(app_url('views/payment.php'));
}
$e = static fn($text) => htmlspecialchars((string) ($text ?? ''), ENT_QUOTES, 'UTF-8');
$accessCode = (string) $sessionCheckout['access_code'];
$verifyUrl = app_url('actions/process_payment_action.php?reference=' . rawurlencode($reference));
require __DIR__ . '/layout/header.php';
?>
<main class="cart-page">
    <section class="auth-card cart-card">
        <h2>Paystack Checkout — Test Mode</h2>
        <p>Amount: <strong>GHS <?php echo number_format((int) $attempt['amount_pesewas'] / 100, 2); ?></strong></p>
        <p>This uses Paystack test payment methods. Do not enter a real card.</p>
        <button id="openPaystack" type="button">Open Paystack Test Payment</button>
        <p id="paystackMessage" role="status">Click the button above to continue.</p>
        <p><a href="<?php echo $e(app_url('views/cart.php')); ?>">Back to Cart</a></p>
        <p><a href="<?php echo $e($verifyUrl); ?>">Check Payment Status</a> (only records an order after server verification)</p>
    </section>
</main>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
(() => {
    'use strict';
    const accessCode = <?php echo json_encode($accessCode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const verifyUrl = <?php echo json_encode($verifyUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const button = document.getElementById('openPaystack');
    const message = document.getElementById('paystackMessage');
    button.addEventListener('click', () => {
        if (typeof PaystackPop === 'undefined') {
            message.textContent = 'Unable to load Paystack. Check your connection and try again.';
            return;
        }
        try {
            const popup = new PaystackPop();
            popup.resumeTransaction(accessCode, {
                onSuccess: () => { window.location.assign(verifyUrl); },
                onCancel: () => { message.textContent = 'Payment cancelled. No order has been created.'; },
                onError: () => { message.textContent = 'Payment could not be opened. Please try again.'; }
            });
        } catch (error) {
            message.textContent = 'Payment popup could not open. Please try again.';
        }
    });
})();
</script>
<?php require __DIR__ . '/layout/footer.php'; ?>
