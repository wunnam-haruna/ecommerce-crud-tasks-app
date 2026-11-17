<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

require_login();
header('Cache-Control: no-store, private');

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]);
if (!$orderId) {
    redirect(app_url('index.php'));
}
try {
    $order = (new CartController())->getOrderById((int) $orderId, (int) $_SESSION['customer_id']);
} catch (Throwable $ex) {
    error_log('Task 13 invoice retrieval: ' . $ex->getMessage());
    $order = false;
}
if (!$order || $order['order_status'] !== 'paid') {
    redirect(app_url('index.php'));
}
$e = static fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
require __DIR__ . '/layout/header.php';
?>
<main class="cart-page">
    <section class="auth-card cart-card">
        <h2>Payment Confirmed — Test Invoice</h2>
        <p><strong>TEST TRANSACTION — No real money collected.</strong></p>
        <p>Invoice number: <?php echo (int) $order['invoice_no']; ?></p>
        <p>Order ID: <?php echo (int) $order['order_id']; ?></p>
        <p>Order date: <?php echo $e($order['order_date']); ?></p>
        <p>Status: Paid (Paystack Test)</p>
        <div class="product-table-scroll">
            <table>
                <thead><tr><th>Product</th><th>Quantity</th><th>Unit Price (GHS)</th><th>Subtotal (GHS)</th></tr></thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <?php $price = (float) $item['unit_price_at_purchase']; $qty = (int) $item['qty']; ?>
                    <tr>
                        <td><?php echo $e($item['product_title'] ?? ('Product #' . (int) $item['product_id'])); ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><?php echo number_format($price, 2); ?></td>
                        <td><?php echo number_format($price * $qty, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th colspan="3">Paid Total (GHS)</th><th><?php echo number_format((float) $order['paid_amount'], 2); ?></th></tr></tfoot>
            </table>
        </div>
        <p>Payment reference: <?php echo $e($order['paystack_reference']); ?></p>
        <p><a class="product-details-link" href="<?php echo $e(app_url('index.php')); ?>">Continue Shopping</a></p>
    </section>
</main>
<?php require __DIR__ . '/layout/footer.php'; ?>
