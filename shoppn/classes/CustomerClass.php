<?php

require_once __DIR__ . '/../core/db_class.php';

class CustomerClass extends Database
{
    public function emailExists($email)
    {
        $stmt = $this->conn->prepare(
            "SELECT customer_email
             FROM customer
             WHERE customer_email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;

        $stmt->close();

        return $exists;
    }


    public function addCustomer(
        $name,
        $email,
        $hashedPassword,
        $country,
        $city,
        $contact
    ) {
        $stmt = $this->conn->prepare(
            "INSERT INTO customer (
                customer_name,
                customer_email,
                customer_pass,
                customer_country,
                customer_city,
                customer_contact
            ) VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssss",
            $name,
            $email,
            $hashedPassword,
            $country,
            $city,
            $contact
        );

        $success = $stmt->execute();

        $customerId = $success
            ? $this->conn->insert_id
            : false;

        $stmt->close();

        return $customerId;
    }


    public function getCustomerByEmail($email)
    {
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM customer
             WHERE customer_email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();

        $stmt->close();

        return $customer ?: false;
    }


    public function login($email, $password)
    {
        $customer = $this->getCustomerByEmail($email);

        if (!$customer) {
            return false;
        }

        if (!password_verify(
            $password,
            $customer['customer_pass']
        )) {
            return false;
        }

        return $customer;
    }
}

?>
