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

}
