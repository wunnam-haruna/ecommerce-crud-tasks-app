<?php
/**
 * The university lab website currently uses HTTP. Restrict checkout endpoints
 * to loopback during local testing, or a web server with direct HTTPS.
 * Do not trust X-Forwarded-Proto from arbitrary visitors.
 */
function shoppn_task13_transport_allowed(): bool
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (is_string($remote) && in_array($remote, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    $https = $_SERVER['HTTPS'] ?? '';
    return (is_string($https) && $https !== '' && strtolower($https) !== 'off') ||
        (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function shoppn_task13_require_secure_transport(): void
{
    if (shoppn_task13_transport_allowed()) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store, private');
    echo 'Paystack checkout is disabled on this HTTP server. Use localhost for test payments.';
    exit;
}
