<?php

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            // Only for local development, hard-coding this for now.
            self::$instance = new mysqli('localhost', 'root', '', 'db');
        }
        return self::$instance;
    }

    public static function maybeCreateUserTable() {
        self::getConnection()->query("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                display_name VARCHAR(255) NOT NULL,
                hashed_password VARCHAR(255) NOT NULL
            );
        ");
    }

    public static function createUser($username, $display_name, $plaintext_password) {
        $connection = self::getConnection();
        self::maybeCreateUserTable();  // Ensure the table exists

        $user = new UserData(null, $username, $display_name, password_hash($plaintext_password, PASSWORD_BCRYPT));

        $stmt = $connection->prepare("INSERT INTO users (username, display_name, hashed_password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user->username, $user->display_name, $user->hashed_password);
        if ($stmt->execute()) {
            $user->set_id($stmt->insert_id);
            return $user;
        } else {
            if ($stmt->errno === 1062) {
                return "Username already taken.";
            }
            return false;
        }
    }

    public static function doUserLogin($username, $plaintext_password) {
        $conn = self::getConnection();

        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, username, display_name, hashed_password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        $fakeHash = '$2b$10$ZhWlZFv/4K5EbHsydb/M9ONXSgkrD/Oen40XCh2cqh6J1JogHwkvq';
        $user = null;

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($plaintext_password, $row['hashed_password'])) {
                $user = new UserData(
                    $row['id'],
                    $row['username'],
                    $row['display_name'],
                    null
                );
            } else {
                password_verify($plaintext_password, $fakeHash);
            }
        } else {
            password_verify($plaintext_password, $fakeHash);
        }

        return $user;
    }
}