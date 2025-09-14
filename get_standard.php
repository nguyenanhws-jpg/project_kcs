<?php
require '../config.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

try {
    // standard
    $stmt = $pdo->prepare("SELECT * FROM standards WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $standard = $stmt->fetch(PDO::FETCH_ASSOC);

    // ngoai_quan
    $stmt = $pdo->prepare("SELECT * FROM ngoai_quan_items WHERE standard_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $ngoai_quan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // kich_thuoc
    $stmt = $pdo->prepare("SELECT * FROM kich_thuoc_items WHERE standard_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $kich_thuoc = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['standard' => $standard, 'ngoai_quan' => $ngoai_quan, 'kich_thuoc' => $kich_thuoc], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    // trả lỗi cho dev; production nên log vào file thay vì echo
    echo json_encode(['error' => $e->getMessage()]);
}