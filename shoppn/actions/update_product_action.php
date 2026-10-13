<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Only administrators can update products.
require_admin();

// Return to the product form with an error.
function update_product_error($message, $id = null)
{
    $_SESSION['error'] = $message;

    $url = app_url('views/admin/product.php');

    if ($id !== null && $id > 0) {
        $url .= '?edit_id=' . $id;
    }

    redirect($url);
}

// Only accept POST requests.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/admin/product.php'));
}

// Verify the CSRF token.
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['product_csrf']) ||
    !hash_equals($_SESSION['product_csrf'], $token)
) {
    update_product_error('Invalid form submission.');
}

// Validate product ID.
$id = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id) {
    update_product_error('Invalid product ID.');
}

// Check the submitted field types.
$fields = [
    'product_cat',
    'product_brand',
    'product_title',
    'product_price',
    'product_desc',
    'product_keywords'
];

foreach ($fields as $field) {
    if (
        !isset($_POST[$field]) ||
        !is_string($_POST[$field])
    ) {
        update_product_error(
            'Invalid or missing product information.',
            $id
        );
    }
}

// Read and validate the category and brand.
$cat = filter_var(
    $_POST['product_cat'],
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$brand = filter_var(
    $_POST['product_brand'],
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($cat === false || $brand === false) {
    update_product_error(
        'Select a valid category and brand.',
        $id
    );
}

// Read the remaining fields.
$title = trim(strip_tags($_POST['product_title']));
$priceInput = trim($_POST['product_price']);
$desc = trim(strip_tags($_POST['product_desc']));
$keywords = trim(strip_tags($_POST['product_keywords']));

// Validate title.
if (
    $title === '' ||
    preg_match('/\A.{1,200}\z/us', $title) !== 1
) {
    update_product_error(
        'Product title must be between 1 and 200 characters.',
        $id
    );
}

// Validate positive price with up to two decimal places.
if (
    !preg_match(
        '/\A(?:0|[1-9][0-9]{0,8})(?:\.[0-9]{1,2})?\z/',
        $priceInput
    ) ||
    (float) $priceInput <= 0
) {
    update_product_error(
        'Enter a valid price greater than zero.',
        $id
    );
}

$price = (float) $priceInput;

// Validate description and keywords.
if (preg_match('/\A.{0,500}\z/us', $desc) !== 1) {
    update_product_error(
        'Description cannot exceed 500 characters.',
        $id
    );
}

if (preg_match('/\A.{0,100}\z/us', $keywords) !== 1) {
    update_product_error(
        'Keywords cannot exceed 100 characters.',
        $id
    );
}

// Prepare variables for image management.
$newFilename = null;
$destination = null;

try {
    $controller = new ProductController();

    // Confirm the product exists.
    $existingProduct = $controller->getProductById($id);

    if (!$existingProduct) {
        update_product_error('Product not found.');
    }

    // Confirm the category and brand exist.
    $categories = $controller->getAllCategories();
    $brands = $controller->getAllBrands();

    $categoryIds = array_map(
        'intval',
        array_column($categories, 'cat_id')
    );

    $brandIds = array_map(
        'intval',
        array_column($brands, 'brand_id')
    );

    if (
        !in_array($cat, $categoryIds, true) ||
        !in_array($brand, $brandIds, true)
    ) {
        update_product_error(
            'Selected category or brand does not exist.',
            $id
        );
    }

    // Keep the existing image by default.
    $filename = $existingProduct['product_image'] ?? '';

    // Check whether a new image was submitted.
    $file = $_FILES['product_image'] ?? null;

    $uploadError = is_array($file)
        ? ($file['error'] ?? UPLOAD_ERR_NO_FILE)
        : UPLOAD_ERR_NO_FILE;

    if ($uploadError !== UPLOAD_ERR_NO_FILE) {

        if (
            !is_array($file) ||
            $uploadError !== UPLOAD_ERR_OK
        ) {
            update_product_error(
                'The new image could not be uploaded.',
                $id
            );
        }

        // Maximum file size: 2 MB.
        $maxSize = 2 * 1024 * 1024;

        if (
            !is_int($file['size']) ||
            $file['size'] <= 0 ||
            $file['size'] > $maxSize
        ) {
            update_product_error(
                'Image must be smaller than 2 MB.',
                $id
            );
        }

        $tmpName = $file['tmp_name'];

        if (
            !is_string($tmpName) ||
            !is_uploaded_file($tmpName)
        ) {
            update_product_error(
                'Invalid image upload.',
                $id
            );
        }

        // Check the actual file MIME type.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedTypes[$mimeType])) {
            update_product_error(
                'Only JPG, PNG, GIF and WebP images are allowed.',
                $id
            );
        }

        // Verify that the file is a valid image.
        $imageInfo = @getimagesize($tmpName);

        if (
            $imageInfo === false ||
            ($imageInfo['mime'] ?? '') !== $mimeType
        ) {
            update_product_error(
                'The uploaded file is not a valid image.',
                $id
            );
        }

        // Generate a unique filename.
        $extension = $allowedTypes[$mimeType];

        $newFilename = 'product_'
            . bin2hex(random_bytes(12))
            . '.'
            . $extension;

        $uploadDir = __DIR__ . '/../images/products/';

        if (
            !is_dir($uploadDir) ||
            !is_writable($uploadDir)
        ) {
            update_product_error(
                'Product image directory is not writable.',
                $id
            );
        }

        $destination = $uploadDir . $newFilename;

        // Move the new image.
        if (!move_uploaded_file($tmpName, $destination)) {
            update_product_error(
                'Could not save the new product image.',
                $id
            );
        }

        $filename = $newFilename;
    }

    // Save the product through the Controller.
    $success = $controller->updateProduct(
        $id,
        $cat,
        $brand,
        $title,
        $price,
        $desc,
        $filename,
        $keywords
    );

    if (!$success) {

        // Remove newly uploaded file if DB update fails.
        if (
            $newFilename !== null &&
            is_file($destination)
        ) {
            @unlink($destination);
        }

        update_product_error(
            'Product could not be updated.',
            $id
        );
    }

    $_SESSION['success'] = 'Product updated successfully.';

} catch (Throwable $exception) {

    // Remove a new file if an exception occurs.
    if (
        $newFilename !== null &&
        $destination !== null &&
        is_file($destination)
    ) {
        @unlink($destination);
    }

    error_log(
        'Update product failed: ' . $exception->getMessage()
    );

    update_product_error(
        'Product could not be updated.',
        $id
    );
}

// Return to Product Management.
redirect(app_url('views/admin/product.php'));

