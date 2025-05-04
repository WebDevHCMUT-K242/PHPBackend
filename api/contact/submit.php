<?php
    // api/contact/submit.php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *"); // Thay đổi * trong production
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }

    require_once __DIR__ . '/../common/ContactManager.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Phương thức yêu cầu không hợp lệ. Chỉ chấp nhận POST.']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    // Kiểm tra dữ liệu đầu vào
    if (!isset($input['name']) || !isset($input['email']) || !isset($input['message']) ||
        empty(trim($input['name'])) || !filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL) || empty(trim($input['message'])))
    {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại tên, email và nội dung tin nhắn.']);
        exit;
    }

    $name = trim($input['name']);
    $email = trim($input['email']);
    $message = trim($input['message']);

    try {
        $manager = new ContactManager();
        $success = $manager->addContact($name, $email, $message);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Tin nhắn của bạn đã được gửi thành công!']);
            // TODO: Gửi email thông báo cho admin (nếu cần)
        } else {
             http_response_code(500);
             echo json_encode(['success' => false, 'error' => 'Không thể gửi tin nhắn lúc này. Vui lòng thử lại sau.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error (contact/submit.php): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ nội bộ khi gửi tin nhắn.']);
    }

    ?>
    