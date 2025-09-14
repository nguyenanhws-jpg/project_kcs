<?php
require 'config.php';
requireLogin();

$type = $_GET['type'] ?? 'general';

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$path = uploadImage($_FILES['file'], $type);
if ($path) {
    $response = ['path' => $path];
    if (in_array($type, ['dung_cu', 'phuong_phap'])) {
        $id = saveReusableImage($path, $_FILES['file']['name'], $type);
        $response['id'] = $id;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed']);
}