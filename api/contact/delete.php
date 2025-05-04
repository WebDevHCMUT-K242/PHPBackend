<?php
    // api/contact/delete.php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

    require_once __DIR__ . '/../common/auth.php';
    require_once __DIR__ . '/../common/ContactManager.php';

    requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Chỉ chấp nhận POST.']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['contact_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu contact_id.']);
        exit;
    }

    $contactId = (int)$input['contact_id'];

     try {
        $manager = new ContactManager();
        $success = $manager->deleteContact($contactId);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Xóa liên hệ thành công.']);
        } else {
             http_response_code(500); // Hoặc 404
             echo json_encode(['success' => false, 'error' => 'Không thể xóa liên hệ.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error (contact/delete.php): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ nội bộ khi xóa liên hệ.']);
    }
    ?>
    