<?php
require '../config.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

try {
    // Lấy danh sách standards theo user
    $stmt = $pdo->prepare("SELECT id, ma_san_pham, ma_khach_hang, created_at 
                           FROM standards 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format lại cho DataTables
    echo json_encode([
        "data" => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}