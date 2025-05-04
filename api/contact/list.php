<?php
    // api/contact/list.php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

     if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

    require_once __DIR__ . '/../common/auth.php';
    require_once __DIR__ . '/../common/ContactManager.php';

    // Yêu cầu admin đăng nhập
    requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Chỉ chấp nhận GET.']);
        exit;
    }

     try {
        $manager = new ContactManager();
        $contacts = $manager->getContacts();
        echo json_encode(['success' => true, 'data' => $contacts]);

    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error (contact/list.php): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ nội bộ khi lấy danh sách liên hệ.']);
    }
    ?>
    