<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Accra');

require_once __DIR__ . '/db_class.php';


function get_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }

    return $_SERVER['REMOTE_ADDR'] ?? '';
}


function redirect($url)
{
    header('Location: ' . $url);
    exit;
}


function is_logged_in()
{
    return isset($_SESSION['customer_id']);
}


function is_admin()
{
    return isset($_SESSION['user_role'])
        && (int) $_SESSION['user_role'] === 1;
}


function require_login()
{
    if (!is_logged_in()) {
        $_SESSION['error'] = 'Please login to access that page.';
        redirect(app_url('views/login.php'));
    }
}


function require_admin()
{
    if (!is_admin()) {
        $_SESSION['error'] = 'Administrator access required.';
        redirect(app_url('index.php'));
    }
}


function app_base()
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $marker = '/shoppn';

    $position = strpos($script, $marker);

    if ($position !== false) {
        return substr(
            $script,
            0,
            $position + strlen($marker)
        );
    }

    return '/shoppn';
}


function app_url($path = '')
{
    return rtrim(app_base(), '/')
        . '/'
        . ltrim($path, '/');
}

?>
