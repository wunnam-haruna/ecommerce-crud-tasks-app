<?php

require_once __DIR__ . '/../classes/CartClass.php';

class CartController
{
    private $cart;

    public function __construct()
    {
        $this->cart = new CartClass();
    }

    // Check if a product is already in the cart.
    public function isInCart($product_id, $ip)
    {
        return $this->cart->isInCart($product_id, $ip);
    }

    // Add a product to the cart.
    public function addToCart($product_id, $qty, $ip, $customer_id = null)
    {
        return $this->cart->addToCart(
            $product_id,
            $qty,
            $ip,
            $customer_id
        );
    }

    // Retrieve all products in the cart.
    public function getCartItems($ip)
    {
        return $this->cart->getCartItems($ip);
    }

    // Count the products in the cart.
    public function getCartCount($ip)
    {
        return $this->cart->getCartCount($ip);
    }

    // Calculate the total cart price.
    public function getCartTotal($ip)
    {
        return $this->cart->getCartTotal($ip);
    }


    public function updateCartQuantity($product_id, $qty, $ip)
    {
        return $this->cart->updateCartQuantity($product_id, $qty, $ip);
    }

    public function removeFromCart($product_id, $ip)
    {
        return $this->cart->removeFromCart($product_id, $ip);
    }

}
