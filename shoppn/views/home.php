<?php

require_once __DIR__ . '/../controllers/ProductController.php';

$controller = new ProductController();

// Determine which products to display.
$heading = 'Featured Products';

if (isset($_GET['cat'])) {

    $catId = filter_input(
        INPUT_GET,
        'cat',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $products = $catId
        ? $controller->getProductsByCategory($catId)
        : [];

    $heading = 'Products by Category';

} elseif (isset($_GET['brand'])) {

    $brandId = filter_input(
        INPUT_GET,
        'brand',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $products = $brandId
        ? $controller->getProductsByBrand($brandId)
        : [];

    $heading = 'Products by Brand';

} else {

    $products = $controller->getFeaturedProducts(6);
}

// Escape content before displaying it in HTML.
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

        <h2><?php echo $e($heading); ?></h2>

        <?php if (empty($products)): ?>

            <p>No products found.</p>

        <?php else: ?>

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
