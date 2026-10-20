
<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Default return page.
$returnUrl = app_url('index.php');

// Return to the previous Shoppn page when possible.
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

if (is_string($referrer) && $referrer !== '') {
    $path = parse_url($referrer, PHP_URL_PATH);
    $query = parse_url($referrer, PHP_URL_QUERY);

    $allowedPages = [
        app_url('index.php'),
        app_url('views/single_product.php'),
        app_url('views/search_results.php'),
        rtrim(app_base(), '/') . '/'
    ];

    if (is_string($path) && in_array($path, $allowedPages, true)) {
        $returnUrl = $path;

        if (is_string($query) && strlen($query) <= 512) {
            $returnUrl .= '?' . $query;
        }
    }
}

// Display a message after redirecting.
function cart_message($message, $type = 'error')
{
    global $returnUrl;

    $_SESSION['cart_flash'] = [
        'type' => $type,
        'message' => $message
    ];

    redirect($returnUrl);
}

// Lecturer's GET-based Add to Cart flow.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    cart_message('Invalid cart request.');
}

// Validate product ID.
$productId = filter_input(
    INPUT_GET,
    'add_cart',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$productId) {
    cart_message('Invalid product selected.');
}

// Identify visitor using the existing lab helper.
$ip = get_ip();

if (
    !is_string($ip) ||
    strlen($ip) > 50 ||
    !filter_var($ip, FILTER_VALIDATE_IP)
) {
    cart_message('Unable to identify this cart.');
}

// Default quantity.
$quantity = 1;

// Logged-in customer or guest.
$customerId = isset($_SESSION['customer_id'])
    ? (int) $_SESSION['customer_id']
    : null;

try {
    // Confirm product exists.
    $productController = new ProductController();
    $product = $productController->getProductById($productId);

    if (!$product) {
        cart_message('Product not found.');
    }

    // Access cart operations through controller.
    $cartController = new CartController();

    // Prevent duplicate products.
    if ($cartController->isInCart($productId, $ip)) {
        cart_message(
            'This item is already in your cart. Go to cart to update quantity.'
        );
    }

    // Insert product into cart.
    $success = $cartController->addToCart(
        $productId,
        $quantity,
        $ip,
        $customerId
    );

    if ($success) {
        cart_message(
            'Product added to your cart successfully.',
            'success'
        );
    }

    cart_message('Unable to add product to cart.');

} catch (Throwable $exception) {
    error_log(
        'Add to cart failed: ' . $exception->getMessage()
    );

    cart_message(
        'Something went wrong while adding the product.'
    );
}
