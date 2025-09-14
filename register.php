<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = sanitize($_POST['username']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$user, $pass]);
        header('Location: login.php?success=1');
        exit;
    } catch (PDOException $e) {
        $error = 'Tài khoản đã tồn tại';
    }
}
include 'includes/header.php';
?>
<div class="card p-4 mx-auto" style="max-width: 400px;">
    <h1 class="text-center mb-4">ĐĂNG KÝ TÀI KHOẢN</h1>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>TÊN TÀI KHOẢN</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>MẬT KHẨU</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">ĐĂNG KÝ</button>
    </form>
    <p class="text-center mt-3">ĐÃ CÓ TÀI KHOẢN? <a href="login.php">CLICK ĐĂNG NHẬP</a></p>
</div>
<?php include 'includes/footer.php'; ?>