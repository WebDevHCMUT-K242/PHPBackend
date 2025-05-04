<?php
    require_once __DIR__ . '/db.php';

    class HomePageContentManager {
        private $pdo;

        public function __construct() {
            // Sử dụng class Database đã có trong dự án
            $this->pdo = Database::getConnection();
            if ($this->pdo === null) {
                // Xử lý lỗi kết nối CSDL nghiêm trọng
                throw new Exception("Không thể kết nối đến cơ sở dữ liệu.");
            }
        }

        /**
         * Tạo bảng pages và page_elements nếu chưa tồn tại
         */
        public static function maybeCreateHomePageContentManagerTable() {
            $conn = Database::getConnection();
            
            // Tạo bảng pages
            $conn->query("
                CREATE TABLE IF NOT EXISTS pages (
                    page_id INT AUTO_INCREMENT PRIMARY KEY,
                    page_slug VARCHAR(100) NOT NULL UNIQUE,
                    page_title VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;
            ");
            
            // Tạo bảng page_elements
            $conn->query("
                CREATE TABLE IF NOT EXISTS page_elements (
                    element_id INT AUTO_INCREMENT PRIMARY KEY,
                    page_id INT NOT NULL,
                    element_key VARCHAR(100) NOT NULL,
                    content TEXT,
                    element_type ENUM('text', 'textarea', 'image', 'html') DEFAULT 'text',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (page_id) REFERENCES pages(page_id) ON DELETE CASCADE,
                    UNIQUE KEY (page_id, element_key)
                ) ENGINE=InnoDB;
            ");
        }

        /**
         * Lấy page_id từ page_slug
         * @param string $pageSlug
         * @return int|null
         */
        private function getPageId(string $pageSlug): ?int {
            $stmt = $this->pdo->prepare("SELECT page_id FROM pages WHERE page_slug = ?");
            $stmt->bind_param("s", $pageSlug);
            $stmt->execute();
            
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                return (int)$row['page_id'];
            }
            return null;
        }

        /**
         * Lấy toàn bộ nội dung của một trang dưới dạng key => content
         * @param string $pageSlug Slug của trang (ví dụ: 'home', 'contact')
         * @return array Mảng kết hợp [element_key => content]
         */
        public function getContent(string $pageSlug): array {
            $content = [];
            $pageId = $this->getPageId($pageSlug);

            if ($pageId === null) {
                return ['page_title' => 'Trang không tồn tại']; // Hoặc ném lỗi
            }

            // Lấy tiêu đề trang trước
            $stmtTitle = $this->pdo->prepare("SELECT page_title FROM pages WHERE page_id = ?");
            $stmtTitle->bind_param("i", $pageId);
            $stmtTitle->execute();
            
            $result = $stmtTitle->get_result();
            if ($row = $result->fetch_assoc()) {
                $content['page_title'] = $row['page_title'];
            } else {
                $content['page_title'] = '';
            }

            // Lấy các element khác
            $stmt = $this->pdo->prepare("SELECT element_key, content FROM page_elements WHERE page_id = ?");
            $stmt->bind_param("i", $pageId);
            $stmt->execute();

            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $content[$row['element_key']] = $row['content'];
            }

            return $content;
        }

        /**
         * Cập nhật nội dung của một phần tử trên trang
         * @param string $pageSlug Slug của trang
         * @param string $elementKey Key của phần tử cần cập nhật
         * @param string $content Nội dung mới
         * @return bool True nếu thành công, False nếu thất bại
         */
        public function updateElement(string $pageSlug, string $elementKey, string $content): bool {
            $pageId = $this->getPageId($pageSlug);
            if ($pageId === null) {
                error_log("Update failed: Page slug '{$pageSlug}' not found.");
                return false;
            }

            try {
                // Kiểm tra xem element đã tồn tại chưa
                $checkStmt = $this->pdo->prepare("SELECT element_key FROM page_elements WHERE page_id = ? AND element_key = ?");
                $checkStmt->bind_param("is", $pageId, $elementKey);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                // Xác định element_type dựa trên key
                $elementType = 'text'; // Mặc định
                if (strpos($elementKey, 'image') !== false || strpos($elementKey, 'logo') !== false) {
                    $elementType = 'image';
                } elseif (strpos($elementKey, 'text') !== false || strpos($elementKey, 'subtitle') !== false || strpos($elementKey, 'message') !== false) {
                    $elementType = 'textarea';
                }
                
                if ($result->num_rows > 0) {
                    // Cập nhật nếu đã tồn tại
                    $updateStmt = $this->pdo->prepare("UPDATE page_elements SET content = ? WHERE page_id = ? AND element_key = ?");
                    $updateStmt->bind_param("sis", $content, $pageId, $elementKey);
                    return $updateStmt->execute();
                } else {
                    // Thêm mới nếu chưa tồn tại
                    $insertStmt = $this->pdo->prepare("INSERT INTO page_elements (page_id, element_key, content, element_type) VALUES (?, ?, ?, ?)");
                    $insertStmt->bind_param("isss", $pageId, $elementKey, $content, $elementType);
                    return $insertStmt->execute();
                }

            } catch (\Exception $e) {
                error_log("Update Element Error ({$pageSlug}/{$elementKey}): " . $e->getMessage());
                return false;
            }
        }

        /**
         * Cập nhật tiêu đề của trang
         * @param string $pageSlug Slug của trang
         * @param string $title Tiêu đề mới
         * @return bool True nếu thành công, False nếu thất bại
         */
        public function updateTitle(string $pageSlug, string $title): bool {
            try {
                $sql = "UPDATE pages SET page_title = ? WHERE page_slug = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bind_param("ss", $title, $pageSlug);
                $stmt->execute();
                return $stmt->affected_rows > 0;
            } catch (\Exception $e) {
                error_log("Update Title Error ({$pageSlug}): " . $e->getMessage());
                return false;
            }
        }
    }
?>