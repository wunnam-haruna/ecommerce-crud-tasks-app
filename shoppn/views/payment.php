<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_login();

$ip = get_ip();
$items = [];
$total = 0.0;
$error = $_SESSION['payment_error'] ?? '';
unset($_SESSION['payment_error']);

try {
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new RuntimeException('Invalid cart identifier.');
    }
    $cart = new CartController();
    $items = $cart->getCartItems($ip);
    $total = $cart->getCartTotal($ip);
} catch (Throwable $ex) {
    error_log('Payment page cart error: ' . $ex->getMessage());
    $error = 'Unable to load cart. Please try again.';
}

if (empty($_SESSION['checkout_csrf'])) {
    $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));
}
$e = static fn($text) => htmlspecialchars((string) ($text ?? ''), ENT_QUOTES, 'UTF-8');
header('Cache-Control: no-store, private');
require __DIR__ . '/layout/header.php';
?>
<main class="cart-page">
    <section class="auth-card cart-card">
        <h2>Secure Checkout — Paystack Test Mode</h2>
        <p>This is a university demonstration. No real money will be charged.</p>
        <?php if ($error): ?>
            <p class="alert alert-error" role="alert"><?php echo $e($error); ?></p>
        <?php endif; ?>
        <?php if (!$items): ?>
            <p>Your cart is empty or unavailable.</p>
            <a class="product-details-link" href="<?php echo $e(app_url('views/cart.php')); ?>">Back to Cart</a>
        <?php else: ?>
            <div class="product-table-scroll">
                <table>
                    <thead><tr><th>Product</th><th>Quantity</th><th>Price (GHS)</th><th>Subtotal (GHS)</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $e($item['product_title']); ?></td>
                            <td><?php echo (int) $item['qty']; ?></td>
                            <td><?php echo number_format((float) $item['product_price'], 2); ?></td>
                            <td><?php echo number_format((float) $item['qty'] * (float) $item['product_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="3">Total (GHS)</th><th><?php echo number_format($total, 2); ?></th></tr></tfoot>
                </table>
            </div>
            <p>Payment email: <strong><?php echo $e($_SESSION['customer_email'] ?? ''); ?></strong></p>
            <form method="POST" action="<?php echo $e(app_url('actions/start_payment_action.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $e($_SESSION['checkout_csrf']); ?>">
                <button type="submit">Continue to Paystack Test Checkout</button>
            </form>
            <p><a href="<?php echo $e(app_url('views/cart.php')); ?>">Back to Cart</a></p>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/layout/footer.php'; ?>
