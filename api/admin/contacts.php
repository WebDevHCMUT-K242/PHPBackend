<?php
header("Content-Type: application/json");
session_start();
require_once __DIR__ . "/../../common/contact_submission.php"; // Adjust path

// --- Admin Check ---
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
//     http_response_code(401);
//     echo json_encode(["success" => false, "error" => "Unauthorized. Admin privileges required."]);
//     exit;
// }

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

try {
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
        $status_filter = isset($_GET['status']) ? $_GET['status'] : null;

        $submissions = ContactSubmission::getSubmissions($page, $per_page, $status_filter);
        $total = ContactSubmission::getTotalSubmissions($status_filter);

        echo json_encode([
            "success" => true,
            "data" => $submissions,
            "pagination" => [
                "currentPage" => $page,
                "perPage" => $per_page,
                "totalItems" => $total,
                "totalPages" => ceil($total / $per_page)
            ]
        ]);

    } elseif ($method === 'PATCH') {
        // Update status
        if (!isset($input['id']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing 'id' or 'status' for update."]);
            exit;
        }
        $success = ContactSubmission::updateStatus((int)$input['id'], $input['status']);
        echo json_encode(["success" => $success, "message" => $success ? "Status updated." : "Failed to update status."]);

    } elseif ($method === 'DELETE') {
        // Delete submission
         $id_to_delete = null;
         if (isset($_GET['id'])) { // Allow deletion via query param for simplicity sometimes
             $id_to_delete = (int)$_GET['id'];
         } elseif ($input && isset($input['id'])) { // Or via request body
             $id_to_delete = (int)$input['id'];
         }

        if ($id_to_delete === null) {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Missing 'id' for deletion."]);
            exit;
        }
        $success = ContactSubmission::deleteSubmission($id_to_delete);
        echo json_encode(["success" => $success, "message" => $success ? "Submission deleted." : "Failed to delete submission."]);

    } else {
        http_response_code(405);
        echo json_encode(["success" => false, "error" => "Method Not Allowed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in admin/contacts.php: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "An internal server error occurred."]);
}
?>