<?php
    // api/common/ContactManager.php

    require_once __DIR__ . '/../common/db.php';

    class ContactManager {
        private $pdo;

        public function __construct() {
            $this->pdo = connectDB();
             if ($this->pdo === null) {
                throw new Exception("Không thể kết nối đến cơ sở dữ liệu.");
            }
        }

        
        public function addContact(string $name, string $email, string $message): bool {
            if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
                return false;
            }

            try {
                $sql = "INSERT INTO contacts (name, email, message, status) VALUES (:name, :email, :message, 'unread')";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    'name' => trim($name),
                    'email' => trim($email),
                    'message' => trim($message)
                ]);
            } catch (\PDOException $e) {
                error_log("Add Contact Error: " . $e->getMessage());
                return false;
            }
        }

        public function getContacts(): array {
             try {
                $stmt = $this->pdo->query("SELECT contact_id, name, email, message, submitted_at, status FROM contacts ORDER BY submitted_at DESC");
                return $stmt->fetchAll();
            } catch (\PDOException $e) {
                error_log("Get Contacts Error: " . $e->getMessage());
                return [];
            }
        }

        
        public function updateStatus(int $contactId, string $status): bool {
            $allowedStatus = ['unread', 'read', 'responded'];
            if (!in_array($status, $allowedStatus)) {
                return false;
            }

            try {
                $sql = "UPDATE contacts SET status = :status WHERE contact_id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['status' => $status, 'id' => $contactId]);
                return $stmt->rowCount() > 0;
            } catch (\PDOException $e) {
                error_log("Update Contact Status Error (ID: {$contactId}): " . $e->getMessage());
                return false;
            }
        }

        
        public function deleteContact(int $contactId): bool {
             try {
                $sql = "DELETE FROM contacts WHERE contact_id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['id' => $contactId]);
                return $stmt->rowCount() > 0;
            } catch (\PDOException $e) {
                error_log("Delete Contact Error (ID: {$contactId}): " . $e->getMessage());
                return false;
            }
        }

        public static function buildJSON() {
            $conn = Database::getConnection();
    
            $titleResult = $conn->query("
                SELECT text_value FROM editable_about_props
                WHERE property = 'title'
            ");
            $titleRow = $titleResult->fetch_assoc();
            $title = $titleRow ? $titleRow['text_value'] : '';
    
            $updatedResult = $conn->query("
                SELECT integer_value FROM editable_about_props
                WHERE property = 'last_updated'
            ");
            $updatedRow = $updatedResult->fetch_assoc();
            $lastUpdated = $updatedRow ? (int)$updatedRow['integer_value'] : 0;
    
            $contentResult = $conn->query("
                SELECT id, type, text FROM editable_about_contents
                ORDER BY display_order ASC
            ");
            $contents = [];
            while ($row = $contentResult->fetch_assoc()) {
                $contents[] = [
                    'id' => (int)$row['id'],
                    'type' => $row['type'],
                    'text' => $row['text'],
                ];
            }
    
            return [
                'title' => $title,
                'last_updated' => $lastUpdated,
                'contents' => $contents
            ];
        }

    }
?>
    