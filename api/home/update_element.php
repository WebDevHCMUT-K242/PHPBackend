<?php
    header('Content-Type: application/json');

    session_start();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Only POST requests are allowed."]);
        exit;
    }

    require_once __DIR__ . '/../home.php';

    $data = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    $key = $data['key'] ?? null;
    $value = $data['value'] ?? null;

    if (!$key || $value === null) {
        echo json_encode(['error' => 'Missing key or value']);
        exit;
    }

    if (EditableAbout::updateField($key, $value)) {
        echo json_encode(['success' => true, 'key' => $key]);
    } else {
        echo json_encode(['error' => 'Failed to update field']);
    }