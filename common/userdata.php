<?php

include_once __DIR__ . "/db.php";

class UserData {
    public $id;
    public $is_admin;
    public $username;
    public $display_name;
    public $hashed_password;

    function __construct($id = null, $is_admin = false, $username = null, $display_name = null, $hashed_password = null) {
        if ($id) {
            $this->set_id($id);
        }
        if ($is_admin) {
            $this->set_is_admin($is_admin);
        }
        if ($username) {
            $this->set_username($username);
        }
        if ($display_name) {
            $this->set_display_name($display_name);
        }
        if ($hashed_password) {
            $this->set_hashed_password($hashed_password);
        }
    }

    function set_id($id) {
        $this->id = $id;
    }

    function set_is_admin($is_admin) {
        $this->is_admin = $is_admin;
    }

    function set_username($name) {
        $this->username = $name;
    }

    function set_display_name($name) {
        $this->display_name = $name;
    }

    function set_hashed_password($hashed_password) {
        $this->hashed_password = $hashed_password;
    }

    public static function maybeCreateUserTable() {
        Database::getConnection()->query("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                is_admin TINYINT(1) NOT NULL,
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
            return "Username cannot exceed $max_username_length characters.";
        }
        if (strlen($display_name) > $max_display_name_length) {
            return "Display name cannot exceed $max_display_name_length characters.";
        }
        if (strlen($plaintext_password) < $min_password_length) {
            return "Password must be at least $min_password_length characters long.";
        }
        if (strlen($plaintext_password) > $max_password_length) {
            return "Password cannot exceed $max_password_length characters.";
        }

        $connection = Database::getConnection();
        self::maybeCreateUserTable();

        $user = new UserData(null, false, $username, $display_name, password_hash($plaintext_password, PASSWORD_BCRYPT));

        try {
            $stmt = $connection->prepare("INSERT INTO users (is_admin, username, display_name, hashed_password) VALUES (?, ?, ?, ?)");
            $is_admin_int = (int)$user->is_admin;
            $stmt->bind_param("isss", $is_admin_int, $user->username, $user->display_name, $user->hashed_password);
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
        $conn = Database::getConnection();

        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, is_admin, username, display_name, hashed_password FROM users WHERE username = ?");
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
                    $row['is_admin'] == 1,
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

    public static function getUser($id) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT id, is_admin, username, display_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows !== 1) {
            return null;
        }

        $row = $result->fetch_assoc();
        return new UserData(
            $row['id'],
            $row['is_admin'] == 1,
            $row['username'],
            $row['display_name'],
            null // hashed_password is not returned for security reasons
        );
    }
}