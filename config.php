<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'oixcvblphosting_tckt_user');
define('DB_PASS', '_7w>_kv`QM4]J}#');
define('DB_NAME', 'oixcvblphosting_tckt_db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('SITE_URL', 'https://vnjsc.org/tieuchuankiemtra/');
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
// Upload and resize image (GD, max 800px, WebP for compression)
function uploadImage($file, $type = 'general', $resizeWidth = 800) {
    global $pdo;
    if (!function_exists('imagewebp')) return false; // Check GD extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return false;
    $name = uniqid() . '.webp';
    $path = UPLOAD_DIR . $name;
    $tmp = $file['tmp_name'];
    list($w, $h) = getimagesize($tmp);
    if ($w > $resizeWidth) {
        $ratio = $resizeWidth / $w;
        $new_h = (int)($h * $ratio);
        $img = imagecreatefromstring(file_get_contents($tmp));
        $resized = imagecreatetruecolor($resizeWidth, $new_h);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $resizeWidth, $new_h, $w, $h);
        imagewebp($resized, $path, 80);
        imagedestroy($img);
        imagedestroy($resized);
    } else {
        imagewebp(imagecreatefromstring(file_get_contents($tmp)), $path, 80);
    }
    // Nếu là loại cần tái sử dụng thì lưu DB
    if (in_array($type, ['dung_cu', 'phuong_phap'])) {
        $stmt = $pdo->prepare("INSERT INTO images_reusable (name, path, type, uploaded_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$file['name'], 'uploads/' . $name, $type, $_SESSION['user_id'] ?? 0]);
    }
    return 'uploads/' . $name;
}
// Get reusable images with filter
function getReusableImages($type, $search = '') {
    global $pdo;
    $search = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT id, name, path FROM images_reusable WHERE type = ? AND name LIKE ?");
    $stmt->execute([$type, $search]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Yêu cầu login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
function sanitize($data) {
    return htmlspecialchars(strip_tags($data));
}
// Lưu image tái sử dụng thủ công
function saveReusableImage($path, $name, $type = 'dung_cu') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO images_reusable (name, path, type, uploaded_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $path, $type, $_SESSION['user_id'] ?? 0]);
    return $pdo->lastInsertId();
}