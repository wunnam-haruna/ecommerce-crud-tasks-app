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

    public function addProduct($cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO products
            (product_cat, product_brand, product_title, product_price,
             product_desc, product_image, product_keywords)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iisdsss",
            $cat, $brand, $title, $price,
            $desc, $image_filename, $keywords
        );

        $success = $stmt->execute();
        $id = $success ? $this->conn->insert_id : false;
        $stmt->close();

        return $id;
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        $stmt = $this->conn->prepare(
            "UPDATE products SET
                product_cat = ?,
                product_brand = ?,
                product_title = ?,
                product_price = ?,
                product_desc = ?,
                product_image = ?,
                product_keywords = ?
             WHERE product_id = ?"
        );

        $stmt->bind_param(
            "iisdsssi",
            $cat, $brand, $title, $price,
            $desc, $image_filename, $keywords, $id
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function getProductById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, c.cat_name, b.brand_name
             FROM products p
             LEFT JOIN categories c ON p.product_cat = c.cat_id
             LEFT JOIN brands b ON p.product_brand = b.brand_id
             WHERE p.product_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $product ?: false;
    }

    public function getAllProducts()
    {
        $result = $this->conn->query(
            "SELECT p.*, c.cat_name, b.brand_name
             FROM products p
             LEFT JOIN categories c ON p.product_cat = c.cat_id
             LEFT JOIN brands b ON p.product_brand = b.brand_id
             ORDER BY p.product_id DESC"
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function getFeaturedProducts($limit = 6)
    {
        $limit = max(1, min(24, (int) $limit));

        $stmt = $this->conn->prepare(
            "SELECT p.*, c.cat_name, b.brand_name
             FROM products p
             LEFT JOIN categories c
                 ON p.product_cat = c.cat_id
             LEFT JOIN brands b
                 ON p.product_brand = b.brand_id
             ORDER BY RAND()
             LIMIT ?"
        );

        $stmt->bind_param("i", $limit);
        $stmt->execute();

        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $products;
    }


    public function getProductsByCategory($cat_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, c.cat_name, b.brand_name
             FROM products p
             LEFT JOIN categories c
                 ON p.product_cat = c.cat_id
             LEFT JOIN brands b
                 ON p.product_brand = b.brand_id
             WHERE p.product_cat = ?
             ORDER BY p.product_id DESC"
        );

        $stmt->bind_param("i", $cat_id);
        $stmt->execute();

        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $products;
    }


    public function getProductsByBrand($brand_id)
    {
        $stmt = $this->conn->prepare(
            "SELECT p.*, c.cat_name, b.brand_name
             FROM products p
             LEFT JOIN categories c
                 ON p.product_cat = c.cat_id
             LEFT JOIN brands b
                 ON p.product_brand = b.brand_id
             WHERE p.product_brand = ?
             ORDER BY p.product_id DESC"
        );

        $stmt->bind_param("i", $brand_id);
        $stmt->execute();

        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $products;
    }


    public function searchProducts($query)
    {
        $query = trim((string) $query);

        if ($query === '') {
            return [];
        }

        $pattern = '%' . $query . '%';

        $stmt = $this->conn->prepare(
            "SELECT p.*, c.cat_name, b.brand_name
             FROM products p
             LEFT JOIN categories c
                 ON p.product_cat = c.cat_id
             LEFT JOIN brands b
                 ON p.product_brand = b.brand_id
             WHERE p.product_title LIKE ?
                OR p.product_keywords LIKE ?
             ORDER BY p.product_id DESC"
        );

        $stmt->bind_param("ss", $pattern, $pattern);
        $stmt->execute();

        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $products;
    }

}

?>
