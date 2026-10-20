<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

// Task 11: The coursework identifies carts by IP (not suitable in production).
$ip = get_ip();
$items = [];
$total = 0.0;
$cartError = '';

if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP) || strlen($ip) > 50) {
    $cartError = 'Unable to identify your shopping cart.';
} else {
    try {
        $cartController = new CartController();
        $items = $cartController->getCartItems($ip);
        $total = $cartController->getCartTotal($ip);
    } catch (Throwable $exception) {
        error_log('Cart display failed: ' . $exception->getMessage());
        $cartError = 'Unable to load your cart right now. Please try again later.';
    }
}

if (empty($_SESSION['cart_csrf'])) {
    $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
}

function cart_escape($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/layout/header.php';
?>

<main class="cart-page">
    <section class="auth-card cart-card">
        <h2>My Shopping Cart</h2>

        <?php if ($cartError !== ''): ?>
            <p class="alert alert-error" role="alert"><?php echo cart_escape($cartError); ?></p>

        <?php elseif (empty($items)): ?>
            <p>Your shopping cart is currently empty.</p>
            <a class="product-details-link" href="<?php echo cart_escape(app_url('index.php')); ?>">
                Continue Shopping
            </a>

        <?php else: ?>
            <form method="POST" action="<?php echo cart_escape(app_url('actions/update_qty_action.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo cart_escape($_SESSION['cart_csrf']); ?>">
                <div class="product-table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Remove</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $id = (int) $item['p_id'];
                                $quantity = max(1, (int) $item['qty']);
                                $price = (float) $item['product_price'];
                                $subtotal = $quantity * $price;
                                ?>
                                <tr>
                                    <td class="cart-remove-cell">
                                        <input type="checkbox" name="remove_ids[]" value="<?php echo $id; ?>"
                                               aria-label="Remove <?php echo cart_escape($item['product_title']); ?>">
                                    </td>
                                    <td>
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img class="product-thumbnail"
                                                 src="<?php echo cart_escape(app_url('images/products/' . rawurlencode(basename($item['product_image'])))); ?>"
                                                 alt="<?php echo cart_escape($item['product_title']); ?>">
                                        <?php endif; ?>
                                        <strong><?php echo cart_escape($item['product_title']); ?></strong>
                                    </td>
                                    <td>
                                        <label for="cart-qty-<?php echo $id; ?>" class="cart-visually-hidden">
                                            Quantity for <?php echo cart_escape($item['product_title']); ?>
                                        </label>
                                        <input class="cart-qty-input" type="number"
                                               id="cart-qty-<?php echo $id; ?>" name="qty[<?php echo $id; ?>]"
                                               value="<?php echo $quantity; ?>" min="1" max="99" step="1" required>
                                    </td>
                                    <td><?php echo number_format($price, 2); ?></td>
                                    <td><?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Cart Total</th>
                                <th><?php echo number_format($total, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="cart-actions">
                    <button type="submit" formaction="<?php echo cart_escape(app_url('actions/update_qty_action.php')); ?>">
                        Update Quantity
                    </button>
                    <button type="submit" formaction="<?php echo cart_escape(app_url('actions/remove_from_cart_action.php')); ?>"
                            formnovalidate>
                        Remove Selected
                    </button>
                    <a class="product-details-link" href="<?php echo cart_escape(app_url('index.php')); ?>">
                        Continue Shopping
                    </a>
                    <button type="button" disabled>Checkout — Coming Soon</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
