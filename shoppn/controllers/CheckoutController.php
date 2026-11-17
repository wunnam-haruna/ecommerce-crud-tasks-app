<?php

require_once __DIR__ . '/../classes/CheckoutClass.php';

class CheckoutController
{
    private CheckoutClass $checkout;

    public function __construct()
    {
        $this->checkout = new CheckoutClass();
    }

    public function createAttempt(int $customerId, string $ip, string $email, array $cartItems): array
    {
        return $this->checkout->createAttempt($customerId, $ip, $email, $cartItems);
    }

    public function getAttempt(string $reference, int $customerId)
    {
        return $this->checkout->getAttempt($reference, $customerId);
    }

    public function markInitializationFailure(string $reference, int $customerId): void
    {
        $this->checkout->markInitializationFailure($reference, $customerId);
    }
}
