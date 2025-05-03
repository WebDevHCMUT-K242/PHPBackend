<?php

include_once __DIR__ . "/db.php";

class ProductData {
    public $id;
    public $name;
    public $description;

    function __construct($id = null, $name = null, $description = null) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    public static function getById($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result) {
            return new ProductData($result['id'], $result['name'], $result['description']);
        }
        return null;
    }

    public static function getVariants($productId) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM variants WHERE product_id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function save() {
        $conn = Database::getConnection();
        if ($this->id) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $this->name, $this->description, $this->id);
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $this->name, $this->description);
        }
        $stmt->execute();
        if (!$this->id) {
            $this->id = $conn->insert_id;
        }
        return $this->id;
    }

    public static function delete($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public static function deleteVariants($productId) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM variants WHERE product_id = ?");
        $stmt->bind_param("i", $productId);
        return $stmt->execute();
    }

    public static function insertVariants($productId, $variants) {
        $conn = Database::getConnection();
        foreach ($variants as $v) {
            $stmt = $conn->prepare("INSERT INTO variants (product_id, name, price, image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $productId, $v['name'], $v['price'], $v['image']);
            $stmt->execute();
        }
    }
}
