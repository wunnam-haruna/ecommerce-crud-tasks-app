<?php

require_once __DIR__ . '/../../core/core.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

require_admin();

$controller = new ProductController();
$categories = $controller->getAllCategories();

$editCategory = false;
$editId = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {
    $editCategory = $controller->getCategoryById($editId);
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

require __DIR__ . '/../layout/header.php';
?>

<main>
    <section class="auth-card">

        <h2>Manage Categories</h2>

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

        <h3><?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?></h3>

        <form
            method="POST"
            action="<?php echo htmlspecialchars(
                app_url(
                    $editCategory
                        ? 'actions/update_category_action.php'
                        : 'actions/add_category_action.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >
            <?php if ($editCategory): ?>
                <input
                    type="hidden"
                    name="cat_id"
                    value="<?php echo (int) $editCategory['cat_id']; ?>"
                >
            <?php endif; ?>

            <div class="form-field">
                <label for="cat_name">Category Name</label>

                <input
                    type="text"
                    id="cat_name"
                    name="cat_name"
                    maxlength="100"
                    value="<?php echo $editCategory
                        ? htmlspecialchars(
                            $editCategory['cat_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : ''; ?>"
                    required
                >
            </div>

            <br>

            <button type="submit">
                <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>
            </button>

            <?php if ($editCategory): ?>
                <a href="<?php echo htmlspecialchars(
                    app_url('views/admin/category.php'),
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>">
                    Cancel
                </a>
            <?php endif; ?>
        </form>

        <h3>Existing Categories</h3>

        <?php if (empty($categories)): ?>

            <p>No categories have been added yet.</p>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo (int) $category['cat_id']; ?></td>

                            <td>
                                <?php echo htmlspecialchars(
                                    $category['cat_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </td>

                            <td>
                                <a href="<?php echo htmlspecialchars(
                                    app_url(
                                        'views/admin/category.php?edit_id='
                                        . (int) $category['cat_id']
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
