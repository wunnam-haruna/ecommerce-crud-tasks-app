<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/admin/category.php'));
}

$name = trim(strip_tags($_POST['cat_name'] ?? ''));

if ($name === '') {
    $_SESSION['error'] = 'Category name is required.';
    redirect(app_url('views/admin/category.php'));
}

if (strlen($name) > 100) {
    $_SESSION['error'] = 'Category name is too long.';
    redirect(app_url('views/admin/category.php'));
}

try {
    $controller = new ProductController();

    if ($controller->addCategory($name)) {
        $_SESSION['success'] = 'Category added.';
    } else {
        $_SESSION['error'] = 'Category could not be added.';
    }
} catch (Throwable $exception) {
    error_log('Add category failed: ' . $exception->getMessage());
    $_SESSION['error'] = 'Category could not be added.';
}

redirect(app_url('views/admin/category.php'));
