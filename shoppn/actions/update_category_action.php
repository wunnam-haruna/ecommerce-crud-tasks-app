<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/admin/category.php'));
}

$id = filter_input(INPUT_POST, 'cat_id', FILTER_VALIDATE_INT);
$name = trim(strip_tags($_POST['cat_name'] ?? ''));

if (!$id || $id <= 0) {
    $_SESSION['error'] = 'Invalid category.';
    redirect(app_url('views/admin/category.php'));
}

if ($name === '') {
    $_SESSION['error'] = 'Category name is required.';
    redirect(app_url('views/admin/category.php?edit_id=' . $id));
}

if (strlen($name) > 100) {
    $_SESSION['error'] = 'Category name is too long.';
    redirect(app_url('views/admin/category.php?edit_id=' . $id));
}

try {
    $controller = new ProductController();

    if (!$controller->getCategoryById($id)) {
        $_SESSION['error'] = 'Category not found.';
        redirect(app_url('views/admin/category.php'));
    }

    if ($controller->updateCategory($id, $name)) {
        $_SESSION['success'] = 'Category updated.';
    } else {
        $_SESSION['error'] = 'Category could not be updated.';
    }
} catch (Throwable $exception) {
    error_log('Update category failed: ' . $exception->getMessage());
    $_SESSION['error'] = 'Category could not be updated.';
}

redirect(app_url('views/admin/category.php'));
