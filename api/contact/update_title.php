<?php
    // api/page/update_title.php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }

    require_once __DIR__ . '/../common/auth.php';
    require_once __DIR__ . '/../common/PageContentManager.php';

    requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Chỉ chấp nhận POST.']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['page_slug']) || !isset($input['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu page_slug hoặc title.']);
        exit;
    }

    $pageSlug = trim($input['page_slug']);
    $title = trim($input['title']);

    if (empty($title)) {
         http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tiêu đề không được để trống.']);
        exit;
    }


    try {
        $manager = new PageContentManager();
        $success = $manager->updateTitle($pageSlug, $title);

        if ($success) {
            echo json_encode(['success' => true, 'message' => "Đã cập nhật tiêu đề trang '{$pageSlug}'."]);
        } else {
             http_response_code(500);
             echo json_encode(['success' => false, 'error' => "Không thể cập nhật tiêu đề trang '{$pageSlug}'."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log("API Error (update_title.php): " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ nội bộ khi cập nhật tiêu đề.']);
    }
    ?>
    