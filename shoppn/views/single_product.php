
<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Get the selected product ID.
$productId = filter_input(
    INPUT_GET,
    'pro_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

// Redirect if the product ID is invalid.
if (!$productId) {
    redirect(app_url('index.php'));
}

// Retrieve product details through the Controller.
$controller = new ProductController();
$product = $controller->getProductById($productId);

// Handle products that do not exist.
if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    redirect(app_url('index.php'));
}

// Escape text for safe HTML output.
$e = function ($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

require __DIR__ . '/layout/header.php';
?>

<main class="product-detail-page">

    <a
        class="product-back-link"
        href="<?php echo $e(app_url('index.php')); ?>"
    >
        &larr; Back to Products
    </a>

    <section class="product-detail-card">

        <!-- Product Image -->
        <div class="product-detail-image">

            <?php if (!empty($product['product_image'])): ?>

                <img
                    src="<?php echo $e(app_url(
                        'images/products/' .
                        rawurlencode(
                            basename($product['product_image'])
                        )
                    )); ?>"
                    alt="<?php echo $e(
                        $product['product_title']
                    ); ?>"
                >

            <?php else: ?>

                <div class="product-no-image">
                    No image available
                </div>

            <?php endif; ?>

        </div>

        <!-- Product Information -->
        <div class="product-detail-info">

            <h1>
                <?php echo $e($product['product_title']); ?>
            </h1>

            <p class="product-detail-brand">
                <strong>Brand:</strong>
                <?php echo $e(
                    $product['brand_name'] ?? 'Unknown'
                ); ?>
            </p>

            <p class="product-detail-category">
                <strong>Category:</strong>
                <?php echo $e(
                    $product['cat_name'] ?? 'Unknown'
                ); ?>
            </p>

            <p class="product-detail-price">
                Price:
                <?php echo number_format(
                    (float) $product['product_price'],
                    2
                ); ?>
            </p>

            <hr>

            <h3>Product Description</h3>

            <p>
                <?php echo nl2br($e(
                    $product['product_desc']
                    ?? 'No description available.'
                )); ?>
            </p>

            <h3>Product Keywords</h3>

            <p>
                <?php echo $e(
                    $product['product_keywords']
                    ?? 'No keywords available.'
                ); ?>
            </p>

            <!-- TASK 11: WORKING ADD TO CART LINK -->
            <div class="product-cart-section">

                <a
                    class="product-details-link"
                    href="<?php echo $e(app_url(
                        'actions/add_to_cart_action.php?add_cart=' .
                        (int) $product['product_id']
                    )); ?>"
                >
                    Add to Cart
                </a>

                <a
                    class="product-back-link"
                    style="margin-left: 15px;"
                    href="<?php echo $e(
                        app_url('views/cart.php')
                    ); ?>"
                >
                    View Shopping Cart
                </a>

            </div>

        </div>

    </section>

</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
