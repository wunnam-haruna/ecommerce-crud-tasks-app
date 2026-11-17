<?php
/** Plain-text test-order confirmation. No payment credentials are used here. */
final class OrderConfirmationMailer
{
    public function render(array $order): string
    {
        if (($order['order_status'] ?? null) !== 'paid' ||
            ($order['currency'] ?? null) !== 'GHS' ||
            empty($order['paystack_reference']) ||
            empty($order['order_id']) || empty($order['invoice_no']) ||
            !isset($order['paid_amount']) || (float) $order['paid_amount'] <= 0 ||
            empty($order['items']) || !is_array($order['items'])) {
            throw new RuntimeException('Order is not a verified paid test order.');
        }

        $lines = [
            'SHOPPN - TEST PAYMENT CONFIRMATION',
            '---------------------------------',
            '',
            'Your Paystack TEST payment was verified and your order was recorded.',
            'This is a university lab demonstration. No real money was collected.',
            '',
            'Invoice number: ' . (int) $order['invoice_no'],
            'Order ID: ' . (int) $order['order_id'],
            'Order date: ' . (string) ($order['order_date'] ?? ''),
            'Payment status: Paid (TEST)',
            'Payment reference: ' . (string) $order['paystack_reference'],
            '',
            'Purchased items:',
        ];

        foreach ($order['items'] as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price_at_purchase'] ?? 0);
            if ($qty < 1 || $unitPrice <= 0) {
                throw new RuntimeException('Invalid historical order item.');
            }
            // Product names are email body text, never message headers.
            $title = (string) ($item['product_title'] ?? ('Product #' . (int) ($item['product_id'] ?? 0)));
            $title = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $title);
            $title = trim($title ?? 'Product');
            $lines[] = sprintf(
                ' - %s | Qty: %d | Unit: GHS %.2f | Subtotal: GHS %.2f',
                $title,
                $qty,
                $unitPrice,
                $qty * $unitPrice
            );
        }

        $lines[] = '';
        $lines[] = 'TOTAL PAID (TEST): GHS ' . number_format((float) $order['paid_amount'], 2, '.', ',');
        $lines[] = '';
        $lines[] = 'Please retain this email as a test invoice summary.';
        $lines[] = 'Thank you for using Shoppn.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** mail() returning true means accepted by local transport, NOT delivered. */
    public function send(string $recipient, array $order): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) ||
            strpbrk($recipient, "\r\n") !== false) {
            throw new InvalidArgumentException('Invalid confirmation recipient.');
        }
        if (!function_exists('mail')) {
            return false;
        }

        $message = $this->render($order);
        $subject = 'Shoppn TEST Order Confirmation - Invoice ' . (int) $order['invoice_no'];
        $headers = [
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];

        // Optional sender must be an email address for a domain you control.
        $sender = getenv('SHOPPN_MAIL_FROM');
        if (is_string($sender) && filter_var($sender, FILTER_VALIDATE_EMAIL) &&
            strpbrk($sender, "\r\n") === false) {
            $headers['From'] = 'Shoppn <' . $sender . '>';
        }

        return @mail($recipient, $subject, $message, $headers);
    }
}
