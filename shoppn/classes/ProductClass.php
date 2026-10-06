<?php

require_once __DIR__ . '/../core/db_class.php';

class ProductClass extends Database
{
    public function addBrand($name)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO brands (brand_name) VALUES (?)"
        );

        $stmt->bind_param("s", $name);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }


    public function getAllBrands()
    {
        $result = $this->conn->query(
            "SELECT * FROM brands ORDER BY brand_name ASC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getBrandById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM brands WHERE brand_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $brand = $result->fetch_assoc();

        $stmt->close();

        return $brand ?: false;
    }


    public function updateBrand($id, $name)
    {
        $stmt = $this->conn->prepare(
            "UPDATE brands SET brand_name = ? WHERE brand_id = ?"
        );

        $stmt->bind_param("si", $name, $id);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    public function addCategory($name)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO categories (cat_name) VALUES (?)"
        );

        $stmt->bind_param("s", $name);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }


    public function getAllCategories()
    {
        $result = $this->conn->query(
            "SELECT * FROM categories ORDER BY cat_name ASC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategoryById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM categories WHERE cat_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $category = $result->fetch_assoc();

        $stmt->close();

        return $category ?: false;
    }


    public function updateCategory($id, $name)
    {
        $stmt = $this->conn->prepare(
            "UPDATE categories SET cat_name = ? WHERE cat_id = ?"
        );

        $stmt->bind_param("si", $name, $id);
        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }
}

?>
