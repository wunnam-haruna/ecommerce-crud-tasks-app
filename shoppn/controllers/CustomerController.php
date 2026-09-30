<?php

require_once __DIR__ . '/../classes/CustomerClass.php';

class CustomerController
{
    private $customer;

    public function __construct()
    {
        $this->customer = new CustomerClass();
    }


    public function register($data)
    {
        $email = $data['email'];
        $password = $data['password'];

        if ($this->customer->emailExists($email)) {
            return [
                'success' => false,
                'error' => 'Email already registered'
            ];
        }

        $hashedPassword = password_hash(
            $password,
            PASSWORD_BCRYPT
        );

        $customerId = $this->customer->addCustomer(
            $data['name'],
            $email,
            $hashedPassword,
            $data['country'],
            $data['city'],
            $data['contact']
        );

        if (!$customerId) {
            return [
                'success' => false,
                'error' => 'Registration failed'
            ];
        }

        return [
            'success' => true,
            'customer_id' => $customerId,
            'user_role' => 2
        ];
    }


    public function login($email, $password)
    {
        $customer = $this->customer->login(
            $email,
            $password
        );

        if (!$customer) {
            return [
                'success' => false,
                'error' => 'Invalid email or password'
            ];
        }

        return [
            'success' => true,
            'customer' => $customer
        ];
    }
}

?>
