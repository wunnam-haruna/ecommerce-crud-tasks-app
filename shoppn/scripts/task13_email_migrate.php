<?php
// CLI only. No credentials are stored in this script.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../core/db_cred.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS order_confirmation_emails (
    order_id INT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'attempting',
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL DEFAULT NULL,
    last_error VARCHAR(255) NULL,
    PRIMARY KEY (order_id),
    KEY idx_confirmation_status (status),
    CONSTRAINT fk_confirmation_order FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
    );
    echo "SUCCESS: order_confirmation_emails table exists. No customer/order rows were changed.\n";
    $db->close();
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: Email table migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
