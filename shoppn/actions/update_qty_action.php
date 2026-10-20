<?php
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/CartController.php';

function cart_update_reply($message, $type = 'error')
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
    cart_update_reply('Your form expired. Reload the cart and try again.');
}

$ip = get_ip();
if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP) || strlen($ip) > 50) {
    cart_update_reply('Unable to identify your cart.');
}

$posted = $_POST['qty'] ?? null;
if (!is_array($posted) || !$posted || count($posted) > 100) {
    cart_update_reply('No valid quantities were submitted.');
}

try {
    $controller = new CartController();
    $available = [];
    foreach ($controller->getCartItems($ip) as $item) {
        $available[(int) $item['p_id']] = true;
    }

    $updates = [];
    foreach ($posted as $key => $value) {
        $id = filter_var((string) $key, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        $qty = is_string($value) || is_int($value)
            ? filter_var($value, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 99]])
            : false;

        if ($id === false || $qty === false || !isset($available[$id])) {
            cart_update_reply('Invalid quantity or product. Check your cart and try again.');
        }
        $updates[$id] = $qty;
    }

    foreach ($updates as $id => $qty) {
        if (!$controller->updateCartQuantity($id, $qty, $ip)) {
            cart_update_reply('Could not update one or more quantities.');
        }
    }
    cart_update_reply('Cart quantities updated successfully.', 'success');
} catch (Throwable $exception) {
    error_log('Update cart quantities failed: ' . $exception->getMessage());
    cart_update_reply('Unable to update your cart right now.');
}
