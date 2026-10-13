<?php

require_once __DIR__ . '/../classes/ProductClass.php';

class ProductController
{
    private $product;

    public function __construct()
    {
        $this->product = new ProductClass();
    }


    public function addBrand($name)
    {
        return $this->product->addBrand($name);
    }


    public function getAllBrands()
    {
        return $this->product->getAllBrands();
    }

    public function getBrandById($id)
    {
        return $this->product->getBrandById($id);
    }


    public function updateBrand($id, $name)
    {
        return $this->product->updateBrand($id, $name);
    }

    public function addCategory($name)
    {
        return $this->product->addCategory($name);
    }


    public function getAllCategories()
    {
        return $this->product->getAllCategories();
    }

    public function getCategoryById($id)
    {
        return $this->product->getCategoryById($id);
    }


    public function updateCategory($id, $name)
    {
        return $this->product->updateCategory($id, $name);
    }

    public function addProduct($cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        return $this->product->addProduct(
            $cat,
            $brand,
            $title,
            $price,
            $desc,
            $image_filename,
            $keywords
        );
    }

    public function updateProduct($id, $cat, $brand, $title, $price, $desc, $image_filename, $keywords)
    {
        return $this->product->updateProduct(
            $id,
            $cat,
            $brand,
            $title,
            $price,
            $desc,
            $image_filename,
            $keywords
        );
    }

    public function getProductById($id)
    {
        return $this->product->getProductById($id);
    }

    public function getAllProducts()
    {
        return $this->product->getAllProducts();
    }


    public function getFeaturedProducts($limit = 6)
    {
        return $this->product->getFeaturedProducts($limit);
    }

    public function getProductsByCategory($cat_id)
    {
        return $this->product->getProductsByCategory($cat_id);
    }

    public function getProductsByBrand($brand_id)
    {
        return $this->product->getProductsByBrand($brand_id);
    }

    public function searchProducts($query)
    {
        return $this->product->searchProducts($query);
    }

}

?>
