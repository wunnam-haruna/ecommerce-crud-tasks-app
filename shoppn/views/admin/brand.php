<?php

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$controller = new ProductController();
$brands = $controller->getAllBrands();

$editBrand = false;
$editId = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $editBrand = $controller->getBrandById($editId);
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

require __DIR__ . '/../layout/header.php';
?>

<main>
    <section class="auth-card">
        <h2>Manage Brands</h2>

        <?php if ($success !== ''): ?>
            <p class="alert alert-success">
                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="alert alert-error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <h3><?php echo $editBrand ? 'Edit Brand' : 'Add Brand'; ?></h3>

        <form
            method="POST"
            action="<?php echo htmlspecialchars(
                app_url(
                    $editBrand
                        ? 'actions/update_brand_action.php'
                        : 'actions/add_brand_action.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >
            <?php if ($editBrand): ?>
                <input
                    type="hidden"
                    name="brand_id"
                    value="<?php echo (int) $editBrand['brand_id']; ?>"
                >
            <?php endif; ?>

            <div class="form-field">
                <label for="brand_name">Brand Name</label>

                <input
                    type="text"
                    id="brand_name"
                    name="brand_name"
                    maxlength="100"
                    value="<?php echo $editBrand
                        ? htmlspecialchars(
                            $editBrand['brand_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : ''; ?>"
                    required
                >
            </div>

            <br>

            <button type="submit">
                <?php echo $editBrand ? 'Update Brand' : 'Add Brand'; ?>
            </button>

            <?php if ($editBrand): ?>
                <a href="<?php echo htmlspecialchars(
                    app_url('views/admin/brand.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>">
                    Cancel
                </a>
            <?php endif; ?>
        </form>

        <h3>Existing Brands</h3>

        <?php if (empty($brands)): ?>
            <p>No brands have been added yet.</p>
        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>Brand ID</th>
                        <th>Brand Name</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td>
                                <?php echo (int) $brand['brand_id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars(
                                    $brand['brand_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </td>

                            <td>
                                <a href="<?php echo htmlspecialchars(
                                    app_url(
                                        'views/admin/brand.php?edit_id='
                                        . (int) $brand['brand_id']
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </section>
</main>

<?php require __DIR__ . '/../layout/footer.php'; ?>
