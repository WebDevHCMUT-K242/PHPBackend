<?php
    // api/contact/update_status.php
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

    if (!isset($input['contact_id']) || !isset($input['status']) || !in_array($input['status'], ['unread', 'read', 'responded'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu contact_id hoặc status không hợp lệ.']);
        exit;
    }

    $contactId = (int)$input['contact_id'];
    $status = $input['status'];

    try {
        $manager = new ContactManager();
        $success = $manager->updateStatus($contactId, $status);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công.']);
        } else {
             http_response_code(500); // Hoặc 404 nếu ID không tồn tại
             echo json_encode(['success' => false, 'error' => 'Không thể cập nhật trạng thái.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error (contact/update_status.php): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ nội bộ khi cập nhật trạng thái.']);
    }
    ?>
    