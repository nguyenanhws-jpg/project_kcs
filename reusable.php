<?php
require '../config.php';
requireLogin(); // call if reusable data should be restricted to logged in users
header('Content-Type: application/json; charset=utf-8');

$type = isset($_GET['type']) ? preg_replace('/[^a-z0-9_\-]/i','',$_GET['type']) : '';
$search = $_GET['search'] ?? '';

try {
    // nếu project có hàm helper getReusableImages, ưu tiên dùng
    if (function_exists('getReusableImages')) {
        $images = getReusableImages($type, $search);
    } else {
        // fallback: query table reusable_images
        $sql = "SELECT id, name, path, type FROM reusable_images WHERE 1=1";
        $params = [];
        if ($type !== '') {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        if ($search !== '') {
            $sql .= " AND (name LIKE ? OR path LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $sql .= " ORDER BY created_at DESC LIMIT 200";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (!is_array($images)) $images = [];
    echo json_encode($images, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}