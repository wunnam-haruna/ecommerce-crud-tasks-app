<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/admin/brand.php'));
}

$name = trim(strip_tags($_POST['brand_name'] ?? ''));

if ($name === '') {
    $_SESSION['error'] = 'Brand name is required.';
    redirect(app_url('views/admin/brand.php'));
}

if (strlen($name) > 100) {
    $_SESSION['error'] = 'Brand name is too long.';
    redirect(app_url('views/admin/brand.php'));
}

try {
    $controller = new ProductController();
    $success = $controller->addBrand($name);

    if ($success) {
        $_SESSION['success'] = 'Brand added.';
    } else {
        $_SESSION['error'] = 'Brand could not be added.';
    }
} catch (Throwable $exception) {
    error_log('Add brand failed: ' . $exception->getMessage());
    $_SESSION['error'] = 'Brand could not be added.';
}

redirect(app_url('views/admin/brand.php'));
