<?php

require_once __DIR__ . '/../core/db_class.php';

class CartClass extends Database
{
    // Check whether a product is already in the cart.
    public function isInCart($product_id, $ip)
    {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM cart
             WHERE p_id = ? AND ip_add = ?
             LIMIT 1"
        );

        $stmt->bind_param("is", $product_id, $ip);
        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;

        $stmt->close();

        return $exists;
    }


    // Add a product to the cart.
    public function addToCart($product_id, $qty, $ip, $customer_id = null)
    {
        $product_id = (int) $product_id;
        $qty = (int) $qty;

        if (
            $product_id <= 0 ||
            $qty <= 0 ||
            $ip === '' ||
            strlen($ip) > 50
        ) {
            return false;
        }

        // Prevent adding the same product twice.
        if ($this->isInCart($product_id, $ip)) {
            return false;
        }

        if ($customer_id !== null && (int) $customer_id > 0) {

            $customer_id = (int) $customer_id;

            $stmt = $this->conn->prepare(
                "INSERT INTO cart (p_id, ip_add, c_id, qty)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "isii",
                $product_id,
                $ip,
                $customer_id,
                $qty
            );

        } else {

            $stmt = $this->conn->prepare(
                "INSERT INTO cart (p_id, ip_add, qty)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "isi",
                $product_id,
                $ip,
                $qty
            );
        }

        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }


    // Retrieve products currently in the cart.
    public function getCartItems($ip)
    {
        $stmt = $this->conn->prepare(
            "SELECT
                cart.*,
                products.product_title,
                products.product_price,
                products.product_image
             FROM cart
             INNER JOIN products
                ON cart.p_id = products.product_id
             WHERE cart.ip_add = ?
             ORDER BY products.product_title ASC"
        );

        $stmt->bind_param("s", $ip);
        $stmt->execute();

        $result = $stmt->get_result();

        $items = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $items;
    }


    // Count the number of different cart entries.
    public function getCartCount($ip)
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total
             FROM cart
             WHERE ip_add = ?"
        );

        $stmt->bind_param("s", $ip);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return (int) ($row['total'] ?? 0);
    }


    // Calculate the total price of all cart items.
    public function getCartTotal($ip)
    {
        $items = $this->getCartItems($ip);

        $total = 0.0;

        foreach ($items as $item) {

            $price = (float) $item['product_price'];
            $qty = (int) $item['qty'];

            $total += $price * $qty;
        }

        return round($total, 2);
    }


    // Update a cart item's quantity for this lab visitor.
    public function updateCartQuantity($product_id, $qty, $ip)
    {
        $product_id = (int) $product_id;
        $qty = (int) $qty;

        if ($product_id <= 0 || $qty < 1 || $qty > 99 ||
            !is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP) || strlen($ip) > 50) {
            return false;
        }

        if (!$this->isInCart($product_id, $ip)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE cart SET qty = ? WHERE p_id = ? AND ip_add = ?"
        );
        $stmt->bind_param("iis", $qty, $product_id, $ip);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // Remove a cart item belonging to this lab visitor.
    public function removeFromCart($product_id, $ip)
    {
        $product_id = (int) $product_id;
        if ($product_id <= 0 || !is_string($ip) ||
            !filter_var($ip, FILTER_VALIDATE_IP) || strlen($ip) > 50) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM cart WHERE p_id = ? AND ip_add = ?"
        );
        $stmt->bind_param("is", $product_id, $ip);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close();
        return $removed;
    }


    /**
     * Record a verified Paystack TEST payment once, atomically.
     * $verifiedData must come from Paystack's server-to-server Verify API,
     * NEVER from $_GET, $_POST, or a browser-side payment callback.
     * $amountPesewas must come from the previously stored checkout attempt.
     */
    public function processPayment($customerId, $ip, $reference, $amountPesewas, array $verifiedData)
    {
        $customerId = (int) $customerId;
        if (
            $customerId < 1 ||
            !is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP) ||
            strlen($ip) > 50 ||
            !is_string($reference) ||
            !preg_match('/\A[A-Za-z0-9._=-]{5,120}\z/D', $reference) ||
            !is_int($amountPesewas) || $amountPesewas < 1
        ) {
            throw new InvalidArgumentException('Invalid checkout details.');
        }

        // Paystack: data.status (not just top-level status) must be success.
        // The Paystack Verify API response's data object is passed here.
        if (
            ($verifiedData['status'] ?? null) !== 'success' ||
            ($verifiedData['reference'] ?? null) !== $reference ||
            ($verifiedData['currency'] ?? null) !== 'GHS' ||
            ($verifiedData['domain'] ?? null) !== 'test' ||
            !isset($verifiedData['amount']) ||
            !(is_int($verifiedData['amount']) ||
              (is_string($verifiedData['amount']) && ctype_digit($verifiedData['amount']))) ||
            (int) $verifiedData['amount'] !== $amountPesewas
        ) {
            throw new RuntimeException('Paystack verification details do not match.');
        }

        $this->conn->begin_transaction();

        try {
            // Lock this attempt: concurrent callbacks cannot create two orders.
            $stmt = $this->conn->prepare(
                'SELECT * FROM checkout_attempts WHERE reference = ? FOR UPDATE'
            );
            $stmt->bind_param('s', $reference);
            $stmt->execute();
            $attempt = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$attempt ||
                (int) $attempt['customer_id'] !== $customerId ||
                $attempt['ip_add'] !== $ip ||
                (int) $attempt['amount_pesewas'] !== $amountPesewas ||
                $attempt['currency'] !== 'GHS'
            ) {
                throw new RuntimeException('Checkout attempt does not match.');
            }

            // Ensure Paystack verified the same customer's email.
            $paidEmail = $verifiedData['customer']['email'] ?? null;
            if (!is_string($paidEmail) ||
                strcasecmp(trim($paidEmail), trim($attempt['email'])) !== 0
            ) {
                throw new RuntimeException('Verified customer email does not match.');
            }

            // Idempotency: repeated callbacks return the existing order ID.
            if ($attempt['status'] === 'paid' && (int) $attempt['order_id'] > 0) {
                $orderId = (int) $attempt['order_id'];
                $this->conn->commit();
                return $orderId;
            }
            if ($attempt['status'] !== 'pending') {
                throw new RuntimeException('Checkout is no longer pending.');
            }

            $items = json_decode($attempt['cart_snapshot'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($items) || !$items || !array_is_list($items)) {
                throw new RuntimeException('Invalid saved cart snapshot.');
            }

            // Expected snapshot line format:
            // [{"product_id":1,"qty":2,"unit_price":"350.00"}]
            $computedPesewas = 0;
            $seen = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    throw new RuntimeException('Invalid cart item.');
                }
                $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
                $qty = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
                $price = $item['unit_price'] ?? null;
                if (
                    !$productId || $productId < 1 || isset($seen[$productId]) ||
                    !$qty || $qty < 1 || $qty > 99 ||
                    !is_string($price) ||
                    !preg_match('/\A(?:0|[1-9][0-9]{0,9})\.[0-9]{2}\z/D', $price)
                ) {
                    throw new RuntimeException('Invalid item in saved checkout.');
                }
                $seen[$productId] = true;
                [$whole, $cents] = explode('.', $price);
                $unitPesewas = (int) $whole * 100 + (int) $cents;
                if ($unitPesewas < 1) {
                    throw new RuntimeException('Invalid price in saved checkout.');
                }
                $computedPesewas += $unitPesewas * $qty;
            }
            if ($computedPesewas !== $amountPesewas) {
                throw new RuntimeException('Saved cart total differs from payment.');
            }

            // A six-digit invoice fits the existing INT invoice_no column.
            $invoiceNo = random_int(100000, 999999);
            $order = $this->conn->prepare(
                "INSERT INTO orders (customer_id, invoice_no, order_date, order_status)
                 VALUES (?, ?, CURDATE(), 'paid')"
            );
            $order->bind_param('ii', $customerId, $invoiceNo);
            $order->execute();
            $orderId = (int) $this->conn->insert_id;
            $order->close();

            $detail = $this->conn->prepare(
                'INSERT INTO orderdetails (order_id, product_id, qty, unit_price_at_purchase)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];
                $unitPrice = $item['unit_price'];
                $detail->bind_param('iiis', $orderId, $productId, $qty, $unitPrice);
                $detail->execute();
            }
            $detail->close();

            $amountGhs = $amountPesewas / 100;
            $currency = 'GHS';
            $payment = $this->conn->prepare(
                'INSERT INTO payment (amt, customer_id, order_id, currency, payment_date, paystack_reference)
                 VALUES (?, ?, ?, ?, CURDATE(), ?)'
            );
            $payment->bind_param('diiss', $amountGhs, $customerId, $orderId, $currency, $reference);
            $payment->execute();
            $payment->close();

            $attemptId = (int) $attempt['id'];
            $update = $this->conn->prepare(
                "UPDATE checkout_attempts SET status = 'paid', order_id = ?
                 WHERE id = ? AND status = 'pending'"
            );
            $update->bind_param('ii', $orderId, $attemptId);
            $update->execute();
            $changed = $update->affected_rows;
            $update->close();
            if ($changed !== 1) {
                throw new RuntimeException('Could not finalize checkout state.');
            }

            // Do not delete newly added or changed cart entries after checkout.
            // The original IP-based lab design remains unsafe on shared IPs.
            $remove = $this->conn->prepare(
                'DELETE FROM cart WHERE ip_add = ? AND p_id = ? AND qty = ?'
            );
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];
                $remove->bind_param('sii', $ip, $productId, $qty);
                $remove->execute();
            }
            $remove->close();

            $this->conn->commit();
            return $orderId;
        } catch (Throwable $error) {
            $this->conn->rollback();
            throw $error;
        }
    }

    /** Retrieve an order only for its owner, with historical prices. */
    public function getOrderById($orderId, $customerId)
    {
        $orderId = (int) $orderId;
        $customerId = (int) $customerId;
        if ($orderId < 1 || $customerId < 1) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT o.*, p.amt AS paid_amount, p.currency, p.paystack_reference
             FROM orders o LEFT JOIN payment p ON p.order_id = o.order_id
             WHERE o.order_id = ? AND o.customer_id = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $orderId, $customerId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$order) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT d.product_id, d.qty, d.unit_price_at_purchase,
                    p.product_title, p.product_image
             FROM orderdetails d LEFT JOIN products p ON p.product_id = d.product_id
             WHERE d.order_id = ?'
        );
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $order;
    }

}
