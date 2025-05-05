<?php
header("Content-Type: application/json");
require_once __DIR__ . "/../common/contact_submission.php"; // Adjust path if needed

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['name']) || !isset($input['email']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing required fields (name, email, message)."]);
    exit;
}

$name = trim($input['name']);
$email = trim($input['email']);
$message = trim($input['message']);

if (empty($name) || empty($email) || empty($message)) {
     http_response_code(400);
     echo json_encode(["success" => false, "message" => "All fields are required."]);
     exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
     http_response_code(400);
     echo json_encode(["success" => false, "message" => "Invalid email format."]);
     exit;
}


try {
    $submissionId = ContactSubmission::createSubmission($name, $email, $message);

    if ($submissionId) {
        echo json_encode(["success" => true, "message" => "Message sent successfully!"]);
    } else {
         // Check if the error was due to validation (e.g., invalid email handled in createSubmission)
         if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             http_response_code(400);
             echo json_encode(["success" => false, "message" => "Invalid email format."]);
         } else {
             http_response_code(500);
             echo json_encode(["success" => false, "message" => "Failed to save message. Please try again."]);
         }
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in contact.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "An internal error occurred. Please try again later."]);
}
?>