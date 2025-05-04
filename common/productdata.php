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
    public static function maybeCreateProductTables() {
        $conn = Database::getConnection();
    
        $conn->query("
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT
            ) ENGINE=InnoDB;
        ");
    
        $conn->query("
            CREATE TABLE IF NOT EXISTS variants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                image VARCHAR(255),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }
    

}
