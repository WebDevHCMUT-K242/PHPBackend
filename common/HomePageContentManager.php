<?php
    require_once __DIR__ . '/db.php';

    class HomePageContentManager {
        private $pdo;

        public function __construct() {
            $this->pdo = Database::getConnection();
            if ($this->pdo === null) {
                throw new Exception("Không thể kết nối đến cơ sở dữ liệu.");
            }
        }

        
        public static function maybeCreateHomePageContentManagerTable() {
            $conn = Database::getConnection();
            
            $conn->query("
                CREATE TABLE IF NOT EXISTS pages (
                    page_id INT AUTO_INCREMENT PRIMARY KEY,
                    page_slug VARCHAR(100) NOT NULL UNIQUE,
                    page_title VARCHAR(255) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB;
            ");
            
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

        
        public function getContent(string $pageSlug): array {
            $content = [];
            $pageId = $this->getPageId($pageSlug);

            if ($pageId === null) {
                return ['page_title' => 'Trang không tồn tại']; 
            }

            $stmtTitle = $this->pdo->prepare("SELECT page_title FROM pages WHERE page_id = ?");
            $stmtTitle->bind_param("i", $pageId);
            $stmtTitle->execute();
            
            $result = $stmtTitle->get_result();
            if ($row = $result->fetch_assoc()) {
                $content['page_title'] = $row['page_title'];
            } else {
                $content['page_title'] = '';
            }

            $stmt = $this->pdo->prepare("SELECT element_key, content FROM page_elements WHERE page_id = ?");
            $stmt->bind_param("i", $pageId);
            $stmt->execute();

            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $content[$row['element_key']] = $row['content'];
            }

            return $content;
        }

        public function updateElement(string $pageSlug, string $elementKey, string $content): bool {
            $pageId = $this->getPageId($pageSlug);
            if ($pageId === null) {
                error_log("Update failed: Page slug '{$pageSlug}' not found.");
                return false;
            }

            try {
                $checkStmt = $this->pdo->prepare("SELECT element_key FROM page_elements WHERE page_id = ? AND element_key = ?");
                $checkStmt->bind_param("is", $pageId, $elementKey);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                $elementType = 'text'; 
                if (strpos($elementKey, 'image') !== false || strpos($elementKey, 'logo') !== false) {
                    $elementType = 'image';
                } elseif (strpos($elementKey, 'text') !== false || strpos($elementKey, 'subtitle') !== false || strpos($elementKey, 'message') !== false) {
                    $elementType = 'textarea';
                }
                
                if ($result->num_rows > 0) {
                    $updateStmt = $this->pdo->prepare("UPDATE page_elements SET content = ? WHERE page_id = ? AND element_key = ?");
                    $updateStmt->bind_param("sis", $content, $pageId, $elementKey);
                    return $updateStmt->execute();
                } else {
                    $insertStmt = $this->pdo->prepare("INSERT INTO page_elements (page_id, element_key, content, element_type) VALUES (?, ?, ?, ?)");
                    $insertStmt->bind_param("isss", $pageId, $elementKey, $content, $elementType);
                    return $insertStmt->execute();
                }

            } catch (\Exception $e) {
                error_log("Update Element Error ({$pageSlug}/{$elementKey}): " . $e->getMessage());
                return false;
            }
        }

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

        public static function updateField(string $key, $value): bool {
            $data = self::buildJSON();
            $data[$key] = $value;
            return file_put_contents(self::$path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
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

        // public static function buildJSON(): array {
        //     if (!file_exists(self::$path)) {
        //         file_put_contents(self::$path, json_encode([], JSON_PRETTY_PRINT));
        //         return [];
        //     }
            
        //     $content = file_get_contents(self::$path);
        //     if (empty($content)) {
        //         return [];
        //     }
            
        //     $data = json_decode($content, true);
        //     if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        //         error_log("JSON parsing error in " . self::$path . ": " . json_last_error_msg());
        //         return [];
        //     }
            
        //     return is_array($data) ? $data : [];
        // }
    }
?>