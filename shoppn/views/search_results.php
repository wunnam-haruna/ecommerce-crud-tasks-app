<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// The existing header uses "q".
// Also support "user_query" from the lecturer's instructions.
$rawQuery = $_GET['q'] ?? $_GET['user_query'] ?? '';

$query = is_string($rawQuery)
    ? trim($rawQuery)
    : '';

$controller = new ProductController();

$products = [];
$message = '';

// Validate the search request.
if ($query === '') {
    $message = 'Enter a product name or keyword to search.';
} elseif (strlen($query) > 100) {
    $message = 'Search must be 100 characters or less.';
} else {
    // Search through the Controller, not directly through SQL.
    $products = $controller->searchProducts($query);

    if (empty($products)) {
        $message = 'No products found for your search.';
    }
}

// Safely display text in HTML.
$e = function ($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

require __DIR__ . '/layout/header.php';
?>

<main class="catalog-layout">

    <?php require __DIR__ . '/layout/sidebar.php'; ?>

    <section class="catalog-content">

        <h2>Search Results</h2>

        <?php if ($query !== ''): ?>
            <p>
                Results for:
                <strong><?php echo $e($query); ?></strong>
            </p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>

            <p><?php echo $e($message); ?></p>

            <a href="<?php echo $e(app_url('index.php')); ?>">
                Back to Products
            </a>

        <?php else: ?>

            <p>
                <?php echo count($products); ?>
                product(s) found.
            </p>

            <div class="product-grid">

                <?php foreach ($products as $product): ?>

                    <article class="product-card">

                        <?php if (!empty($product['product_image'])): ?>

                            <img
                                src="<?php echo $e(app_url(
                                    'images/products/' .
                                    rawurlencode(basename(
                                        $product['product_image']
                                    ))
                                )); ?>"
                                alt="<?php echo $e(
                                    $product['product_title']
                                ); ?>"
                                class="product-card-image"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div class="product-no-image">
                                No image available
                            </div>

                        <?php endif; ?>

                        <div class="product-card-body">

                            <h3>
                                <?php echo $e(
                                    $product['product_title']
                                ); ?>
                            </h3>

                            <p class="product-card-brand">
                                <?php echo $e(
                                    $product['brand_name']
                                ); ?>
                            </p>

                            <p class="product-card-price">
                                Price:
                                <?php echo number_format(
                                    (float) $product['product_price'],
                                    2
                                ); ?>
                            </p>

                            <a
                                class="product-details-link"
                                href="<?php echo $e(app_url(
                                    'views/single_product.php?pro_id=' .
                                    (int) $product['product_id']
                                )); ?>"
                            >
                                View Details
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require __DIR__ . '/layout/footer.php'; ?>
