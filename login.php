<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = sanitize($_POST['username']);
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->execute([$user]);
    $row = $stmt->fetch();
    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Sai thông tin đăng nhập';
    }
}
include 'includes/header.php';
?>
<div class="card p-4 mx-auto" style="max-width: 400px;">
    <h1 class="text-center mb-4">LOGIN HỆ THỐNG</h1>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>TÊN ĐĂNG NHẬP</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>PASSWORD</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">LOGIN</button>
    </form>
    <p class="text-center mt-3">CHƯA CÓ TÀI KHOẢN? <a href="register.php">CLICK ĐĂNG KÝ</a></p>
    <p class="text-sm text-gray-500 mt-4 text-center">© VNjsc All rights reserved.</p>
<p class="text-red-700 text-sm mt-4 font-semibold text-center">Lưu ý: Nếu bạn không thuộc nhân sự của VNjsc, xin vui lòng không truy cập hệ thống!</p>
</div>
<?php include 'includes/footer.php'; ?>