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

        /**
         * Thêm liên hệ mới vào CSDL
         * @param string $name
         * @param string $email
         * @param string $message
         * @return bool True nếu thành công, False nếu thất bại
         */
        public function addContact(string $name, string $email, string $message): bool {
            // Validate input cơ bản
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

        /**
         * Lấy danh sách tất cả liên hệ, sắp xếp theo ngày gửi mới nhất
         * @return array Danh sách liên hệ
         */
        public function getContacts(): array {
             try {
                $stmt = $this->pdo->query("SELECT contact_id, name, email, message, submitted_at, status FROM contacts ORDER BY submitted_at DESC");
                return $stmt->fetchAll();
            } catch (\PDOException $e) {
                error_log("Get Contacts Error: " . $e->getMessage());
                return []; // Trả về mảng rỗng nếu lỗi
            }
        }

        /**
         * Cập nhật trạng thái của một liên hệ
         * @param int $contactId ID liên hệ
         * @param string $status Trạng thái mới ('unread', 'read', 'responded')
         * @return bool True nếu thành công, False nếu thất bại
         */
        public function updateStatus(int $contactId, string $status): bool {
            $allowedStatus = ['unread', 'read', 'responded'];
            if (!in_array($status, $allowedStatus)) {
                return false; // Trạng thái không hợp lệ
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

        /**
         * Xóa một liên hệ
         * @param int $contactId ID liên hệ
         * @return bool True nếu thành công, False nếu thất bại
         */
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
    }
?>
    