
<?php

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

// Only administrators can access this page.
require_admin();

$controller = new ProductController();

// Retrieve categories, brands and products.
$categories = $controller->getAllCategories();
$brands = $controller->getAllBrands();
$products = $controller->getAllProducts();

// Generate the security token for product forms.
if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

// Check whether we are editing an existing product.
$editProduct = false;

if (isset($_GET['edit_id'])) {
    $editId = filter_input(
        INPUT_GET,
        'edit_id',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$editId) {
        $_SESSION['error'] = 'Invalid product ID.';
        redirect(app_url('views/admin/product.php'));
    }

    $editProduct = $controller->getProductById($editId);

    if (!$editProduct) {
        $_SESSION['error'] = 'Product not found.';
        redirect(app_url('views/admin/product.php'));
    }
}

// Read success and error messages.
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

// Helper for safely displaying HTML values.
$e = function ($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

// Products require at least one category and brand.
$canAddProducts = !empty($categories) && !empty($brands);

require __DIR__ . '/../layout/header.php';
?>

<main>
    <section class="auth-card product-admin-card">

        <h2>Manage Products</h2>

        <p>Add new products or update existing products in Shoppn.</p>

        <?php if ($success !== ''): ?>
            <p class="alert alert-success" role="status">
                <?php echo $e($success); ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="alert alert-error" role="alert">
                <?php echo $e($error); ?>
            </p>
        <?php endif; ?>

        <?php if (!$canAddProducts): ?>
            <p class="alert alert-error">
                Add at least one brand and one category before
                creating products.
            </p>

            <p>
                <a href="<?php echo $e(app_url('views/admin/brand.php')); ?>">
                    Manage Brands
                </a>

                |

                <a href="<?php echo $e(app_url('views/admin/category.php')); ?>">
                    Manage Categories
                </a>
            </p>
        <?php endif; ?>

        <h3>
            <?php echo $editProduct ? 'Edit Product' : 'Add Product'; ?>
        </h3>

        <form
            method="POST"
            enctype="multipart/form-data"
            action="<?php echo $e(app_url(
                $editProduct
                    ? 'actions/update_product_action.php'
                    : 'actions/add_product_action.php'
            )); ?>"
        >

            <!-- Security token -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo $e($_SESSION['product_csrf']); ?>"
            >

            <!-- Product ID when editing -->
            <?php if ($editProduct): ?>
                <input
                    type="hidden"
                    name="product_id"
                    value="<?php echo (int) $editProduct['product_id']; ?>"
                >
            <?php endif; ?>

            <div class="product-form-grid">

                <!-- Product Title -->
                <div class="form-field">
                    <label for="product_title">Product Title</label>

                    <input
                        type="text"
                        id="product_title"
                        name="product_title"
                        maxlength="200"
                        value="<?php echo $editProduct
                            ? $e($editProduct['product_title'])
                            : ''; ?>"
                        required
                    >
                </div>

                <!-- Product Price -->
                <div class="form-field">
                    <label for="product_price">Price</label>

                    <input
                        type="number"
                        id="product_price"
                        name="product_price"
                        min="0.01"
                        max="999999999.99"
                        step="0.01"
                        value="<?php echo $editProduct
                            ? $e(number_format(
                                (float) $editProduct['product_price'],
                                2,
                                '.',
                                ''
                            ))
                            : ''; ?>"
                        required
                    >
                </div>

                <!-- Category Dropdown -->
                <div class="form-field">
                    <label for="product_cat">Category</label>

                    <select
                        id="product_cat"
                        name="product_cat"
                        required
                    >
                        <option value="">Select Category</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?php echo (int) $category['cat_id']; ?>"
                                <?php
                                if (
                                    $editProduct &&
                                    (int) $editProduct['product_cat'] ===
                                    (int) $category['cat_id']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                <?php echo $e($category['cat_name']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Brand Dropdown -->
                <div class="form-field">
                    <label for="product_brand">Brand</label>

                    <select
                        id="product_brand"
                        name="product_brand"
                        required
                    >
                        <option value="">Select Brand</option>

                        <?php foreach ($brands as $brand): ?>
                            <option
                                value="<?php echo (int) $brand['brand_id']; ?>"
                                <?php
                                if (
                                    $editProduct &&
                                    (int) $editProduct['product_brand'] ===
                                    (int) $brand['brand_id']
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                <?php echo $e($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <!-- Description -->
                <div class="form-field product-form-wide">
                    <label for="product_desc">Description</label>

                    <textarea
                        id="product_desc"
                        name="product_desc"
                        rows="4"
                        maxlength="500"
                    ><?php echo $editProduct
                        ? $e($editProduct['product_desc'])
                        : ''; ?></textarea>
                </div>

                <!-- Keywords -->
                <div class="form-field product-form-wide">
                    <label for="product_keywords">Keywords</label>

                    <input
                        type="text"
                        id="product_keywords"
                        name="product_keywords"
                        maxlength="100"
                        placeholder="Example: running, sports, sneakers"
                        value="<?php echo $editProduct
                            ? $e($editProduct['product_keywords'])
                            : ''; ?>"
                    >
                </div>

                <!-- Product Image -->
                <div class="form-field product-form-wide">
                    <label for="product_image">Product Image</label>

                    <?php if (
                        $editProduct &&
                        !empty($editProduct['product_image'])
                    ): ?>

                        <p>Current image:</p>

                        <img
                            class="product-thumbnail"
                            src="<?php echo $e(app_url(
                                'images/products/' .
                                rawurlencode(basename(
                                    $editProduct['product_image']
                                ))
                            )); ?>"
                            alt="Current product image"
                        >

                        <p class="field-help">
                            Leave the upload empty to keep this image.
                        </p>

                    <?php endif; ?>

                    <input
                        type="file"
                        id="product_image"
                        name="product_image"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        <?php echo $editProduct ? '' : 'required'; ?>
                    >

                    <p class="field-help">
                        Accepted formats: JPG, PNG, GIF and WebP.
                        Maximum size: 2 MB.
                    </p>
                </div>

            </div>

            <br>

            <button
                type="submit"
                <?php echo $canAddProducts ? '' : 'disabled'; ?>
            >
                <?php echo $editProduct
                    ? 'Update Product'
                    : 'Add Product'; ?>
            </button>

            <?php if ($editProduct): ?>
                <a href="<?php echo $e(
                    app_url('views/admin/product.php')
                ); ?>">
                    Cancel
                </a>
            <?php endif; ?>

        </form>

        <hr>

        <!-- Existing Products -->
        <h3>Existing Products</h3>

        <?php if (empty($products)): ?>

            <p>No products have been added yet.</p>

        <?php else: ?>

            <div class="product-table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>

                                <td>
                                    <?php echo (int) $product['product_id']; ?>
                                </td>

                                <td>
                                    <?php if (!empty($product['product_image'])): ?>
                                        <img
                                            class="product-thumbnail"
                                            src="<?php echo $e(app_url(
                                                'images/products/' .
                                                rawurlencode(basename(
                                                    $product['product_image']
                                                ))
                                            )); ?>"
                                            alt="Product image"
                                        >
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo $e($product['product_title']); ?>
                                </td>

                                <td>
                                    <?php echo $e($product['cat_name']); ?>
                                </td>

                                <td>
                                    <?php echo $e($product['brand_name']); ?>
                                </td>

                                <td>
                                    <?php echo number_format(
                                        (float) $product['product_price'],
                                        2
                                    ); ?>
                                </td>

                                <td>
                                    <a href="<?php echo $e(app_url(
                                        'views/admin/product.php?edit_id=' .
                                        (int) $product['product_id']
                                    )); ?>">
                                        Edit
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>
</main>

<?php require __DIR__ . '/../layout/footer.php'; ?>
