<?php

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $connection = new mysqli('localhost', 'root', '');
            if ($connection->connect_error) {
                die("Database connection failed: " . $connection->connect_error);
            }
            $connection->query("CREATE DATABASE IF NOT EXISTS db");
            $connection->select_db('db');
            self::$instance = $connection;
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
        $max_username_length = 50;
        $max_display_name_length = 255;
        $min_password_length = 6;
        $max_password_length = 255;

        if (empty($username) || empty($display_name) || empty($plaintext_password)) {
            return "No blanks allowed.";
        }

        if (!preg_match('/^[a-z0-9_]+$/', $username)) {
            return "Username can only contain lowercase alphanumeric characters and underscores.";
        }
        if (strlen($username) > $max_username_length) {
            return "Username cannot exceed {$max_username_length} characters.";
        }
        if (strlen($display_name) > $max_display_name_length) {
            return "Display name cannot exceed {$max_display_name_length} characters.";
        }
        if (strlen($plaintext_password) < $min_password_length) {
            return "Password must be at least {$min_password_length} characters long.";
        }
        if (strlen($plaintext_password) > $max_password_length) {
            return "Password cannot exceed {$max_password_length} characters.";
        }

        $connection = self::getConnection();
        self::maybeCreateUserTable();

        $user = new UserData(null, $username, $display_name, password_hash($plaintext_password, PASSWORD_BCRYPT));

        try {
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
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                return "Username already taken.";
            }
            return "Error occurred while creating user: " . $e->getMessage();
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