<?php

require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Only administrators can add products.
require_admin();

// Send an error back to the product form.
function product_error($message)
{
    $_SESSION['error'] = $message;
    redirect(app_url('views/admin/product.php'));
}

// Only accept POST requests.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect(app_url('views/admin/product.php'));
}

// Check the form's security token.
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['product_csrf']) ||
    !hash_equals($_SESSION['product_csrf'], $token)
) {
    product_error('Invalid form submission. Please try again.');
}

// Check that fields are ordinary text values.
$fields = [
    'product_cat',
    'product_brand',
    'product_title',
    'product_price',
    'product_desc',
    'product_keywords'
];

foreach ($fields as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
        product_error('Invalid or missing product information.');
    }
}

// Read the submitted product information.
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

$title = trim(strip_tags($_POST['product_title']));
$priceInput = trim($_POST['product_price']);
$desc = trim(strip_tags($_POST['product_desc']));
$keywords = trim(strip_tags($_POST['product_keywords']));

// Validate category and brand IDs.
if ($cat === false || $brand === false) {
    product_error('Please select a valid category and brand.');
}

// Validate product title.
if (
    $title === '' ||
    preg_match('/\A.{1,200}\z/us', $title) !== 1
) {
    product_error('Product title must be between 1 and 200 characters.');
}

// Validate product price.
// Allow positive prices with up to 2 decimal places.
if (
    !preg_match('/\A(?:0|[1-9][0-9]{0,8})(?:\.[0-9]{1,2})?\z/', $priceInput) ||
    (float) $priceInput <= 0
) {
    product_error('Enter a valid price greater than zero.');
}

$price = (float) $priceInput;

// Validate description and keywords.
if (preg_match('/\A.{0,500}\z/us', $desc) !== 1) {
    product_error('Description cannot exceed 500 characters.');
}

if (preg_match('/\A.{0,100}\z/us', $keywords) !== 1) {
    product_error('Keywords cannot exceed 100 characters.');
}

// Check that an image was uploaded.
if (
    !isset($_FILES['product_image']) ||
    !is_array($_FILES['product_image']) ||
    ($_FILES['product_image']['error'] ?? null) !== UPLOAD_ERR_OK
) {
    product_error('Please upload a product image.');
}

$file = $_FILES['product_image'];

// Maximum upload size: 2 MB.
$maxSize = 2 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    product_error('Product image must be smaller than 2 MB.');
}

// Check the temporary uploaded file.
$tmpName = $file['tmp_name'];

if (!is_uploaded_file($tmpName)) {
    product_error('Invalid image upload.');
}

// Determine the actual MIME type of the file.
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpName);

// Only allow supported image formats.
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
];

if (!isset($allowedTypes[$mimeType])) {
    product_error('Only JPG, PNG, GIF and WebP images are allowed.');
}

// Confirm the file contains a recognised image.
$imageInfo = @getimagesize($tmpName);

if (
    $imageInfo === false ||
    ($imageInfo['mime'] ?? '') !== $mimeType
) {
    product_error('The uploaded file is not a valid image.');
}

// Create a unique filename.
// Do not trust the original user-supplied filename.
$extension = $allowedTypes[$mimeType];

$filename = 'product_'
    . bin2hex(random_bytes(12))
    . '.'
    . $extension;

// Product images directory.
$uploadDir = __DIR__ . '/../images/products/';

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    product_error('Product image directory is not writable.');
}

$destination = $uploadDir . $filename;

// Save the product through the MVC layers.
try {
    $controller = new ProductController();

    // Confirm selected category and brand exist.
    $categories = $controller->getAllCategories();
    $brands = $controller->getAllBrands();

    $categoryIds = array_column($categories, 'cat_id');
    $brandIds = array_column($brands, 'brand_id');

    if (
        !in_array($cat, $categoryIds) ||
        !in_array($brand, $brandIds)
    ) {
        product_error('Selected category or brand does not exist.');
    }

    // Move the uploaded image to its destination.
    if (!move_uploaded_file($tmpName, $destination)) {
        product_error('Could not save the product image.');
    }

    // Insert the product using ProductController.
    $productId = $controller->addProduct(
        $cat,
        $brand,
        $title,
        $price,
        $desc,
        $filename,
        $keywords
    );

    if (!$productId) {
        // Remove the image if database insertion fails.
        @unlink($destination);
        product_error('Product could not be added.');
    }

    $_SESSION['success'] = 'Product added successfully.';

} catch (Throwable $exception) {

    // Clean up uploaded image on failure.
    if (is_file($destination)) {
        @unlink($destination);
    }

    error_log('Add product failed: ' . $exception->getMessage());

    product_error('Product could not be added.');
}

// Return to the product management page.
redirect(app_url('views/admin/product.php'));
