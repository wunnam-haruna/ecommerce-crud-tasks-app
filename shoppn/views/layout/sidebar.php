<?php

require_once __DIR__ . '/../../controllers/ProductController.php';

// Use the controller provided by the homepage when available.
$sidebarController = isset($controller)
    && $controller instanceof ProductController
    ? $controller
    : new ProductController();

// Retrieve categories and brands.
$sidebarCategories = $sidebarController->getAllCategories();
$sidebarBrands = $sidebarController->getAllBrands();

// Safely display text and URLs.
$sidebarEscape = function ($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<aside class="catalog-sidebar">

    <h3>Categories</h3>

    <?php if (empty($sidebarCategories)): ?>

        <p>No categories available.</p>

    <?php else: ?>

        <ul class="sidebar-links">

            <?php foreach ($sidebarCategories as $category): ?>

                <li>
                    <a href="<?php echo $sidebarEscape(
                        app_url(
                            'index.php?cat=' .
                            (int) $category['cat_id']
                        )
                    ); ?>">
                        <?php echo $sidebarEscape(
                            $category['cat_name']
                        ); ?>
                    </a>
                </li>

            <?php endforeach; ?>

        </ul>

    <?php endif; ?>

    <h3>Brands</h3>

    <?php if (empty($sidebarBrands)): ?>

        <p>No brands available.</p>

    <?php else: ?>

        <ul class="sidebar-links">

            <?php foreach ($sidebarBrands as $brand): ?>

                <li>
                    <a href="<?php echo $sidebarEscape(
                        app_url(
                            'index.php?brand=' .
                            (int) $brand['brand_id']
                        )
                    ); ?>">
                        <?php echo $sidebarEscape(
                            $brand['brand_name']
                        ); ?>
                    </a>
                </li>

            <?php endforeach; ?>

        </ul>

    <?php endif; ?>

    <a class="view-all-link"
       href="<?php echo $sidebarEscape(app_url('index.php')); ?>">
        View Featured Products
    </a>

</aside>
