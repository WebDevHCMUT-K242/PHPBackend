<?php

include_once __DIR__ . "/db.php";

class Order {
    
    public static function maybeCreateOrderTable() {
        $conn = Database::getConnection();
    
        $conn->query("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_id VARCHAR(100) NOT NULL,
        product_id INT NOT NULL,
        variant_id INT NOT NULL,
        amount INT NOT NULL DEFAULT 1,
        status ENUM('shopping_cart', 'pending', 'shipping', 'delivered', 'canceled') NOT NULL DEFAULT 'shopping_cart',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
");

    }
    

}
