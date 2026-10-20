<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Shoppn</title>

    <link
        rel="stylesheet"
        href="<?php echo htmlspecialchars(app_url('css/style.css')); ?>"
    >
</head>

<body>

<header>

    <h1>Shoppn</h1>

    <nav>

        <a href="<?php echo htmlspecialchars(app_url('index.php')); ?>">
            Home
        </a>

        <?php if (!is_logged_in()): ?>

            |
            <a href="<?php echo htmlspecialchars(app_url('views/register.php')); ?>">
                Register
            </a>

            |
            <a href="<?php echo htmlspecialchars(app_url('views/login.php')); ?>">
                Login
            </a>

        <?php else: ?>

            |
            <span>
                Welcome
                <?php
                echo htmlspecialchars(
                    $_SESSION['customer_name'] ?? 'Customer'
                );
                ?>
            </span>

            |
            <a href="<?php echo htmlspecialchars(app_url('views/account/my_account.php')); ?>">
                My Account
            </a>

            <?php if (is_admin()): ?>

                |
                <a href="<?php echo htmlspecialchars(app_url('views/admin/')); ?>">
                    Admin
                </a>

            <?php endif; ?>

            |
            <a href="<?php echo htmlspecialchars(app_url('logout.php')); ?>">
                Logout
            </a>

        <?php endif; ?>

    </nav>

    <form
        action="<?php echo htmlspecialchars(app_url('views/search_results.php')); ?>"
        method="GET"
    >
        <input
            type="text"
            name="q"
            placeholder="Search products"
        >

        <button type="submit">
            Search
        </button>
    </form>



<?php
require_once __DIR__ . '/../../controllers/CartController.php';

$headerCartCount = 0;
$headerCartTotal = 0.0;
$headerCartIp = get_ip();

if (filter_var($headerCartIp, FILTER_VALIDATE_IP)) {
    try {
        $headerCartController = new CartController();
        $headerCartCount = $headerCartController->getCartCount($headerCartIp);
        $headerCartTotal = $headerCartController->getCartTotal($headerCartIp);
    } catch (Throwable $exception) {
        error_log('Header cart summary failed');
    }
}
?>

<div class="cart-summary">
    <strong>Shopping Cart</strong>

    | Total Items:
    <?php echo (int) $headerCartCount; ?>

    | Total Price:
    <?php echo number_format($headerCartTotal, 2); ?>

    |
    <a href="<?php echo htmlspecialchars(
        app_url('views/cart.php'),
        ENT_QUOTES,
        'UTF-8'
    ); ?>">
        Go to Cart
    </a>
</div>
</header>

<?php
$cartFlash = $_SESSION['cart_flash'] ?? null;
unset($_SESSION['cart_flash']);
?>

<?php if (is_array($cartFlash) && !empty($cartFlash['message'])): ?>
    <div class="cart-notice alert <?php
        echo ($cartFlash['type'] ?? '') === 'success'
            ? 'alert-success'
            : 'alert-error';
    ?>" role="status">
        <?php echo htmlspecialchars(
            (string) $cartFlash['message'],
            ENT_QUOTES,
            'UTF-8'
        ); ?>
    </div>
<?php endif; ?>


<hr>
