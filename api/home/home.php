<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // api/page/home.php (Tương tự cho contact.php, chỉ đổi $pageSlug)
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *"); // Cho phép truy cập từ mọi nguồn (thay đổi * thành domain React của bạn trong production)
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // Xử lý preflight request của CORS
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }

    require_once __DIR__ . '/../../common/HomePageContentManager.php';

    $pageSlug = 'home'; // Đặt slug tương ứng với trang

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'error' => 'Phương thức yêu cầu không hợp lệ. Chỉ chấp nhận GET.']);
        exit;
    }

    try {
        $manager = new HomePageContentManager();
        $content = $manager->getContent($pageSlug);

        if (empty($content)) {
             http_response_code(404); // Not Found
             echo json_encode(['success' => false, 'error' => "Không tìm thấy nội dung cho trang '{$pageSlug}'."]);
        } else {
            echo json_encode(['success' => true, 'data' => $content]);
        }

    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        error_log("API Error (page/{$pageSlug}/{$pageSlug}.php): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ nội bộ khi lấy dữ liệu trang.']);
    }

    class EditableAbout {
        private static string $path = __DIR__ . '/json.php';
    
        public static function buildJSON(): array {
            if (!file_exists(self::$path)) return [];
            return json_decode(file_get_contents(self::$path), true);
        }
    
        public static function updateField(string $key, $value): bool {
            $data = self::buildJSON();
            $data[$key] = $value;
            return file_put_contents(self::$path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
        }
    }

    ?>
    