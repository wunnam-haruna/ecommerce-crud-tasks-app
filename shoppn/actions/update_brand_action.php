<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/admin/brand.php'));
}

$id = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
$name = trim(strip_tags($_POST['brand_name'] ?? ''));

if (!$id || $id <= 0) {
    $_SESSION['error'] = 'Invalid brand.';
    redirect(app_url('views/admin/brand.php'));
}

if ($name === '') {
    $_SESSION['error'] = 'Brand name is required.';
    redirect(app_url('views/admin/brand.php?edit_id=' . $id));
}

if (strlen($name) > 100) {
    $_SESSION['error'] = 'Brand name is too long.';
    redirect(app_url('views/admin/brand.php?edit_id=' . $id));
}

try {
    $controller = new ProductController();

    if (!$controller->getBrandById($id)) {
        $_SESSION['error'] = 'Brand not found.';
        redirect(app_url('views/admin/brand.php'));
    }

    if ($controller->updateBrand($id, $name)) {
        $_SESSION['success'] = 'Brand updated.';
    } else {
        $_SESSION['error'] = 'Brand could not be updated.';
    }
} catch (Throwable $exception) {
    error_log('Update brand failed: ' . $exception->getMessage());
    $_SESSION['error'] = 'Brand could not be updated.';
}

redirect(app_url('views/admin/brand.php'));
