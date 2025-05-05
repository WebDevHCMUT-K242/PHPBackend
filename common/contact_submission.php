<?php

include_once __DIR__ . "/db.php";

class ContactSubmission {
    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_REPLIED = 'replied';

    public static function maybeCreateTables() {
        $conn = Database::getConnection();
        $conn->query("
            CREATE TABLE IF NOT EXISTS contact_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'unread', -- 'unread', 'read', 'replied'
                submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");
    }

    public static function createSubmission($name, $email, $message) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            INSERT INTO contact_submissions (name, email, message)
            VALUES (?, ?, ?)
        ");
        // Basic validation/sanitization should be added here
        $name = htmlspecialchars(strip_tags($name));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL); // Basic email sanitization
        $message = htmlspecialchars(strip_tags($message));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             return false; // Invalid email
        }

        $stmt->bind_param("sss", $name, $email, $message);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? $conn->insert_id : false;
    }

    public static function getSubmissions($page = 1, $per_page = 20, $status_filter = null) {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $per_page;
        $sql = "SELECT id, name, email, message, status, submitted_at FROM contact_submissions";
        $params = [];
        $types = "";

        if ($status_filter !== null && in_array($status_filter, [self::STATUS_UNREAD, self::STATUS_READ, self::STATUS_REPLIED])) {
            $sql .= " WHERE status = ?";
            $params[] = $status_filter;
            $types .= "s";
        }

        $sql .= " ORDER BY submitted_at DESC LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $submissions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $submissions;
    }

     public static function getTotalSubmissions($status_filter = null) {
         $conn = Database::getConnection();
         $sql = "SELECT COUNT(*) as total FROM contact_submissions";
         $params = [];
         $types = "";

         if ($status_filter !== null && in_array($status_filter, [self::STATUS_UNREAD, self::STATUS_READ, self::STATUS_REPLIED])) {
             $sql .= " WHERE status = ?";
             $params[] = $status_filter;
             $types .= "s";
         }
         $stmt = $conn->prepare($sql);
          if (!empty($params)) {
             $stmt->bind_param($types, ...$params);
         }
         $stmt->execute();
         $result = $stmt->get_result();
         $row = $result->fetch_assoc();
         $stmt->close();
         return (int)$row['total'];
     }


    public static function updateStatus($id, $new_status) {
        if (!in_array($new_status, [self::STATUS_UNREAD, self::STATUS_READ, self::STATUS_REPLIED])) {
            return false; // Invalid status
        }
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE contact_submissions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public static function deleteSubmission($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM contact_submissions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>