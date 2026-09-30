<?php

// Start output buffering so redirects can still work
ob_start();

// Start the session once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ghana timezone
date_default_timezone_set('Africa/Accra');

// Load the shared database base class
require_once __DIR__ . '/db_class.php';


// Get the visitor's IP address
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


// Redirect to another page
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}


// Check whether a customer is logged in
function is_logged_in()
{
    return isset($_SESSION['customer_id']);
}


// Check whether the logged-in user is an admin
function is_admin()
{
    return isset($_SESSION['user_role'])
        && (int) $_SESSION['user_role'] === 1;
}


// Find the app's base URL.
// Locally: /shoppn
// Live: /~wun-nam.haruna/ecommerce-class/shoppn
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


// Build a URL within the application
function app_url($path = '')
{
    return rtrim(app_base(), '/')
        . '/'
        . ltrim($path, '/');
}

?>
