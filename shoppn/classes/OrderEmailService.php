<?php
require_once __DIR__ . '/../core/db_class.php';
require_once __DIR__ . '/../controllers/CartController.php';
require_once __DIR__ . '/OrderConfirmationMailer.php';

/** Send at most one confirmation per verified paid order. */
final class OrderEmailService extends Database
{
    public function sendOnce(int $orderId, int $customerId): string
    {
        if ($orderId < 1 || $customerId < 1) {
            throw new InvalidArgumentException('Invalid order identity.');
        }

        // The current CartController already enforces customer ownership.
        $order = (new CartController())->getOrderById($orderId, $customerId);
        if (!$order || ($order['order_status'] ?? '') !== 'paid' ||
            ($order['currency'] ?? '') !== 'GHS' ||
            empty($order['paystack_reference'])) {
            throw new RuntimeException('Cannot email an unverified order.');
        }

        // Address comes from the authoritative customer row, not a browser field.
        $lookup = $this->conn->prepare(
            'SELECT c.customer_email FROM customer c
             INNER JOIN orders o ON o.customer_id = c.customer_id
             WHERE o.order_id = ? AND o.customer_id = ? LIMIT 1'
        );
        $lookup->bind_param('ii', $orderId, $customerId);
        $lookup->execute();
        $recipientRow = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        $recipient = (string) ($recipientRow['customer_email'] ?? '');
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Customer email is unavailable.');
        }

        $mailer = new OrderConfirmationMailer();
        // Validate email details before claiming this order for delivery.
        $mailer->render($order);

        // Unique order_id makes simultaneous callbacks safe from duplicates.
        // We record the attempt before calling an external mail transport.
        $claim = $this->conn->prepare(
            "INSERT IGNORE INTO order_confirmation_emails
             (order_id, status) VALUES (?, 'attempting')"
        );
        $claim->bind_param('i', $orderId);
        $claim->execute();
        $claimed = $claim->affected_rows === 1;
        $claim->close();
        if (!$claimed) {
            return 'already_attempted';
        }

        $accepted = false;
        try {
            $accepted = $mailer->send($recipient, $order);
        } catch (Throwable $error) {
            error_log('Order email transport exception: ' . $error->getMessage());
        }

        $status = $accepted ? 'accepted' : 'failed';
        $errorDetail = $accepted ? null : 'Local mail transport did not accept the message';
        $update = $this->conn->prepare(
            "UPDATE order_confirmation_emails
             SET status = ?,
                 accepted_at = IF(? = 'accepted', CURRENT_TIMESTAMP, NULL),
                 last_error = ?
             WHERE order_id = ?"
        );
        $update->bind_param('sssi', $status, $status, $errorDetail, $orderId);
        $update->execute();
        $update->close();
        return $status;
    }
}
