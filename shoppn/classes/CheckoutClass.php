<?php

require_once __DIR__ . '/../core/db_class.php';

/** Local lab checkout snapshots; no card details ever touch this class. */
class CheckoutClass extends Database
{
    public function createAttempt(int $customerId, string $ip, string $email, array $cartItems): array
    {
        if ($customerId < 1 || !filter_var($ip, FILTER_VALIDATE_IP) || strlen($ip) > 50 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || !$cartItems) {
            throw new InvalidArgumentException('Invalid checkout information.');
        }

        $snapshot = [];
        $totalPesewas = 0;
        $seen = [];
        foreach ($cartItems as $item) {
            $id = filter_var($item['p_id'] ?? null, FILTER_VALIDATE_INT);
            $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
            $rawPrice = $item['product_price'] ?? null;
            if (!$id || $id < 1 || isset($seen[$id]) || !$qty || $qty < 1 || $qty > 99 ||
                !is_numeric($rawPrice) || !is_finite((float) $rawPrice) ||
                (float) $rawPrice <= 0 || (float) $rawPrice > 9999999) {
                throw new RuntimeException('The cart contains an invalid product or price.');
            }
            $seen[$id] = true;
            // Store exact 2-decimal text for the downstream payment model.
            $price = number_format((float) $rawPrice, 2, '.', '');
            [$major, $minor] = explode('.', $price);
            $unitPesewas = (int) $major * 100 + (int) $minor;
            if ($unitPesewas < 1) {
                throw new RuntimeException('Invalid product price.');
            }
            $totalPesewas += $unitPesewas * $qty;
            if ($totalPesewas > 1000000000) {
                throw new RuntimeException('Cart total exceeds the lab limit.');
            }
            $snapshot[] = [
                'product_id' => (int) $id,
                'qty' => (int) $qty,
                'unit_price' => $price,
            ];
        }

        $reference = 'SHOPPN-' . bin2hex(random_bytes(16));
        $json = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $currency = 'GHS';

        $stmt = $this->conn->prepare(
            'INSERT INTO checkout_attempts
             (reference, customer_id, ip_add, email, amount_pesewas, currency, cart_snapshot, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\')'
        );
        $stmt->bind_param('sississ', $reference, $customerId, $ip, $email,
            $totalPesewas, $currency, $json);
        $stmt->execute();
        $stmt->close();

        return ['reference' => $reference, 'amount_pesewas' => $totalPesewas];
    }

    public function getAttempt(string $reference, int $customerId)
    {
        if ($customerId < 1 || !preg_match('/\A[A-Za-z0-9._=-]{5,120}\z/D', $reference)) {
            return false;
        }
        $stmt = $this->conn->prepare(
            'SELECT id, reference, customer_id, ip_add, email, amount_pesewas,
                    currency, status, order_id, created_at
             FROM checkout_attempts WHERE reference = ? AND customer_id = ? LIMIT 1'
        );
        $stmt->bind_param('si', $reference, $customerId);
        $stmt->execute();
        $attempt = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $attempt ?: false;
    }

    public function markInitializationFailure(string $reference, int $customerId): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE checkout_attempts SET status = 'init_failed'
             WHERE reference = ? AND customer_id = ? AND status = 'pending'"
        );
        $stmt->bind_param('si', $reference, $customerId);
        $stmt->execute();
        $stmt->close();
    }
}
