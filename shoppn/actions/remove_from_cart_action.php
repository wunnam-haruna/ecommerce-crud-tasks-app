<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

function cart_remove_reply($message, $type = 'error')
{
    $_SESSION['cart_flash'] = ['type' => $type, 'message' => $message];
    redirect(app_url('views/cart.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || empty($_SESSION['cart_csrf']) ||
    !hash_equals($_SESSION['cart_csrf'], $token)) {
    cart_remove_reply('Your form expired. Reload the cart and try again.');
}

$ip = get_ip();
if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP) || strlen($ip) > 50) {
    cart_remove_reply('Unable to identify your cart.');
}

$posted = $_POST['remove_ids'] ?? null;
if (!is_array($posted) || !$posted || count($posted) > 100) {
    cart_remove_reply('Select at least one product to remove.');
}

try {
    $controller = new CartController();
    $available = [];
    foreach ($controller->getCartItems($ip) as $item) {
        $available[(int) $item['p_id']] = true;
    }

    $ids = [];
    foreach ($posted as $value) {
        $id = is_string($value) || is_int($value)
            ? filter_var($value, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]])
            : false;
        if ($id === false || !isset($available[$id])) {
            cart_remove_reply('Invalid product selection. Reload your cart.');
        }
        $ids[$id] = true;
    }

    $removed = 0;
    foreach (array_keys($ids) as $id) {
        if ($controller->removeFromCart($id, $ip)) {
            $removed++;
        }
    }

    cart_remove_reply($removed . ' product(s) removed from your cart.', 'success');
} catch (Throwable $exception) {
    error_log('Remove from cart failed: ' . $exception->getMessage());
    cart_remove_reply('Unable to remove items right now.');
}
