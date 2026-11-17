<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../classes/CheckoutTransportGuard.php';
shoppn_task13_require_secure_transport();

require_login();
header('Cache-Control: no-store, private');
$message = $_SESSION['payment_failure'] ?? 'Payment could not be verified.';
unset($_SESSION['payment_failure']);
$e = static fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
require __DIR__ . '/layout/header.php';
?>
<main class="cart-page">
    <section class="auth-card cart-card">
        <h2>Test Payment Not Completed</h2>
        <p class="alert alert-error" role="alert"><?php echo $e($message); ?></p>
        <p>If you completed a test payment, return to the payment window and select Check Payment Status.</p>
        <p><a class="product-details-link" href="<?php echo $e(app_url('views/cart.php')); ?>">Back to Cart</a></p>
    </section>
</main>
<?php require __DIR__ . '/layout/footer.php'; ?>
