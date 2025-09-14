<?php
require 'config.php';
requireLogin();
include 'includes/header.php';
$id = $_GET['id'] ?? null;
$standard = null;
$ngoai_quan = [];
$kich_thuoc = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM standards WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $standard = $stmt->fetch();
    if (!$standard) header('Location: list.php');
    $stmt = $pdo->prepare("SELECT * FROM ngoai_quan_items WHERE standard_id = ?");
    $stmt->execute([$id]);
    $ngoai_quan = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM kich_thuoc_items WHERE standard_id = ?");
    $stmt->execute([$id]);
    $kich_thuoc = $stmt->fetchAll();
}
// Save form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ma_sp = sanitize($_POST['ma_san_pham']);
    $ma_kh = sanitize($_POST['ma_khach_hang']);
    // Hình bản vẽ và dụng cụ từ hidden (Dropzone set value)
    $hinh_ban_ve = $_POST['hinh_ban_ve'] ?? ($standard['hinh_ban_ve'] ?? '');
    $hinh_dung_cu_json = $_POST['hinh_dung_cu'] ?? ($standard['hinh_dung_cu'] ?? '[]');
    // Update hoặc Insert standards
    if ($id) {
        $stmt = $pdo->prepare("UPDATE standards
            SET ma_san_pham=?, ma_khach_hang=?, hinh_ban_ve=?, hinh_dung_cu=? WHERE id=?");
        $stmt->execute([$ma_sp, $ma_kh, $hinh_ban_ve, $hinh_dung_cu_json, $id]);
        $pdo->prepare("DELETE FROM ngoai_quan_items WHERE standard_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM kich_thuoc_items WHERE standard_id=?")->execute([$id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO standards (user_id, ma_san_pham, ma_khach_hang, hinh_ban_ve, hinh_dung_cu)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $ma_sp, $ma_kh, $hinh_ban_ve, $hinh_dung_cu_json]);
        $id = $pdo->lastInsertId();
    }
    // Lưu Ngoại Quan
    $hang_muc = $_POST['ngoai_quan_hang_muc'] ?? [];
    foreach ($hang_muc as $key => $hm) {
        $dung_cu = json_encode($_POST['ngoai_quan_dung_cu'][$key] ?? []);
        $hinh_phuong = $_POST['ngoai_quan_hinh_phuong'][$key] ?? '';
        $hinh_ok = $_POST['ngoai_quan_hinh_ok'][$key] ?? '';
        $hinh_ng = $_POST['ngoai_quan_hinh_ng'][$key] ?? '';
        $notes_ng = $_POST['notes_ng'][$key] ?? '';
        $tan_suat = $_POST['ngoai_quan_tan_suat'][$key] ?? '';
        $ghi_chu = $_POST['ngoai_quan_ghi_chu'][$key] ?? '';
        if ($hm) {
            $stmt = $pdo->prepare("INSERT INTO ngoai_quan_items (standard_id, hang_muc, dung_cu, hinh_phuong_phap, hinh_ok, hinh_ng, notes_ng, tan_suat, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $hm, $dung_cu, $hinh_phuong, $hinh_ok, $hinh_ng, $notes_ng, $tan_suat, $ghi_chu]);
        }
    }
    // Lưu Kích Thước
    $hang_muc_kt = $_POST['kich_thuoc_hang_muc'] ?? [];
    foreach ($hang_muc_kt as $key => $hm) {
        $dung_cu = json_encode($_POST['kich_thuoc_dung_cu'][$key] ?? []);
        $tan_suat = $_POST['kich_thuoc_tan_suat'][$key] ?? '';
        $thong_so = floatval($_POST['thong_so_hang_muc'][$key] ?? 0);
        $dung_sai_tren = floatval($_POST['dung_sai_tren'][$key] ?? 0);
        $dung_sai_duoi = floatval($_POST['dung_sai_duoi'][$key] ?? 0);
        $min = $thong_so + $dung_sai_duoi;
        $max = $thong_so + $dung_sai_tren;
        $hinh_dung_cu = $_POST['kich_thuoc_hinh_dung_cu'][$key] ?? '';
        $hinh_phuong = $_POST['kich_thuoc_hinh_phuong'][$key] ?? '';
        $ghi_chu = $_POST['kich_thuoc_ghi_chu'][$key] ?? '';
        if ($hm) {
            $stmt = $pdo->prepare("INSERT INTO kich_thuoc_items (standard_id, hang_muc, dung_cu, tan_suat, thong_so_hang_muc, dung_sai_tren, dung_sai_duoi, min, max, hinh_dung_cu, hinh_phuong_phap, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, $hm, $dung_cu, $tan_suat, $thong_so, $dung_sai_tren, $dung_sai_duoi, $min, $max, $hinh_dung_cu, $hinh_phuong, $ghi_chu]);
        }
    }
    header("Location: template.php?id=$id");
    exit;
}
?>
<div class="card p-4">
    <h1 class="mb-4"><i class="fas fa-file-alt"></i> <?php echo $id ? 'SỬA' : 'NHẬP'; ?> TIÊU CHUẨN KIỂM TRA</h1>
    <form id="inputForm" method="POST" enctype="multipart/form-data">
        <div class="row mb-3">
            <div class="col-md-6">
                <label>MÃ SẢN PHẨM</label>
                <input type="text" name="ma_san_pham" class="form-control" value="<?php echo $standard['ma_san_pham'] ?? ''; ?>" required>
            </div>
            <div class="col-md-6">
                <label>MÃ KHÁCH HÀNG</label>
                <input type="text" name="ma_khach_hang" class="form-control" value="<?php echo $standard['ma_khach_hang'] ?? ''; ?>">
            </div>
        </div>
        <div class="mb-3">
            <label>HÌNH BẢN VẼ GIẢN LƯỢC (1 PIC)</label>
            <div id="dz-ban-ve" class="dropzone"></div>
            <input type="hidden" id="hinh_ban_ve_input" name="hinh_ban_ve" value="<?php echo $standard['hinh_ban_ve'] ?? ''; ?>">
            <?php if ($standard && $standard['hinh_ban_ve']): ?>
                <img src="<?php echo $standard['hinh_ban_ve']; ?>" class="img-preview">
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label>HÌNH CÔNG CỤ KIỂM TRA (10 PIC - MAX)</label>
            <div id="dz-dung-cu" class="dropzone"></div>
            <input type="hidden" id="hinh_dung_cu_ids" name="hinh_dung_cu" value='<?php echo htmlspecialchars($standard['hinh_dung_cu'] ?? '[]'); ?>'>
            <?php if ($standard && $standard['hinh_dung_cu']): ?>
                <?php foreach (json_decode($standard['hinh_dung_cu'], true) as $id_dc): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT path FROM images_reusable WHERE id=?");
                    $stmt->execute([$id_dc]);
                    if ($row = $stmt->fetch()): ?>
                        <img src="<?php echo $row['path']; ?>" class="img-preview">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <h2 class="mt-4">I. TIÊU CHUẨN KIỂM TRA NGOẠI QUAN</h2>
        <button type="button" id="add-ngoai-quan" class="btn btn-add mb-2"><i class="fas fa-plus"></i> THÊM HẠNG MỤC</button>
        <table id="ngoai-quan-table" class="table table-bordered">
            <thead>
                <tr>
                    <th>Hạng Mục</th>
                    <th>Dụng Cụ (Click chọn)</th>
                    <th>Hình Phương Pháp</th>
                    <th>Hình OK</th>
                    <th>Hình NG & Notes</th>
                    <th>Tần Suất</th>
                    <th>Ghi Chú</th>
                    <th>Manager</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ngoai_quan as $key => $item): ?>
                    <tr data-id="<?php echo $key; ?>">
                        <td>
                            <input type="text" name="ngoai_quan_hang_muc[<?php echo $key; ?>]" value="<?= htmlspecialchars($item['hang_muc']) ?>" class="form-control" required>
                        </td>
                        <td>
                            <select multiple name="ngoai_quan_dung_cu[<?php echo $key; ?>][]" class="form-control dung-cu-select">
                                <?php
                                $sel = json_decode($item['dung_cu'], true) ?: [];
                                if ($sel) {
                                    $in = str_repeat('?,', count($sel)-1) . '?';
                                    $st = $pdo->prepare("SELECT id, name FROM images_reusable WHERE id IN ($in)");
                                    $st->execute($sel);
                                    foreach ($st->fetchAll() as $row) {
                                        echo "<option value='{$row['id']}' selected>{$row['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <input type="text" class="form-control mt-1" placeholder="Tìm dụng cụ" oninput="window.loadReusable('dung_cu', this.parentNode.querySelector('select'), this.value)">
                        </td>
                        <td>
                            <div class="dropzone" id="dz-phuong-ngoai-<?php echo $key; ?>"></div>
                            <input type="hidden" name="ngoai_quan_hinh_phuong[<?php echo $key; ?>]" value="<?= $item['hinh_phuong_phap'] ?>">
                            <?php if($item['hinh_phuong_phap']) echo "<img src='{$item['hinh_phuong_phap']}' class='img-preview'>"; ?>
                        </td>
                        <td>
                            <div class="dropzone" id="dz-ok-<?php echo $key; ?>"></div>
                            <input type="hidden" name="ngoai_quan_hinh_ok[<?php echo $key; ?>]" value="<?= $item['hinh_ok'] ?>">
                            <?php if($item['hinh_ok']) echo "<img src='{$item['hinh_ok']}' class='img-preview'>"; ?>
                        </td>
                        <td>
                            <div class="dropzone" id="dz-ng-<?php echo $key; ?>"></div>
                            <input type="hidden" name="ngoai_quan_hinh_ng[<?php echo $key; ?>]" value="<?= $item['hinh_ng'] ?>">
                            <textarea name="notes_ng[<?php echo $key; ?>]" class="form-control mt-1"><?= htmlspecialchars($item['notes_ng']) ?></textarea>
                            <?php if($item['hinh_ng']) echo "<img src='{$item['hinh_ng']}' class='img-preview'>"; ?>
                        </td>
                        <td>
                            <input type="text" name="ngoai_quan_tan_suat[<?php echo $key; ?>]" value="<?= htmlspecialchars($item['tan_suat']) ?>" class="form-control">
                        </td>
                        <td>
                            <input type="text" name="ngoai_quan_ghi_chu[<?php echo $key; ?>]" value="<?= htmlspecialchars($item['ghi_chu']) ?>" class="form-control">
                        </td>
                        <td>
                            <button type="button" class="btn btn-delete remove-row"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2 class="mt-4">II. TIÊU CHUẨN KIỂM TRA KÍCH THƯỚC</h2>
        <button type="button" id="add-kich-thuoc" class="btn btn-add mb-2"><i class="fas fa-plus"></i> THÊM HẠNG MỤC</button>
        <table id="kich-thuoc-table" class="table table-bordered">
            <thead>
                <tr>
                    <th>Hạng Mục</th>
                    <th>Tần Suất</th>
                    <th>Thông Số</th>
                    <th>Dung Sai Trên</th>
                    <th>Dung Sai Dưới</th>
                    <th>Min (Auto)</th>
                    <th>Max (Auto)</th>
                    <th>Dụng Cụ & Hình</th>
                    <th>Hình Phương Pháp</th>
                    <th>Ghi Chú</th>
                    <th>Manager</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kich_thuoc as $key => $item): ?>
                    <tr data-id="<?php echo $key; ?>">
                        <td>
                            <input type="text" name="kich_thuoc_hang_muc[<?php echo $key; ?>]" value="<?= htmlspecialchars($item['hang_muc']) ?>" class="form-control" required>
                        </td>
                        <td>
                            <input type="text" name="kich_thuoc_tan_suat[<?php echo $key; ?>]" value="<?= htmlspecialchars($item['tan_suat']) ?>" class="form-control">
                        </td>
                        <td>
                            <input type="number" step="0.001" name="thong_so_hang_muc[<?php echo $key; ?>]" value="<?= number_format($item['thong_so_hang_muc'], 3) ?>" class="form-control thong-so">
                        </td>
                        <td>
                            <input type="number" step="0.001" name="dung_sai_tren[<?php echo $key; ?>]" value="<?= number_format($item['dung_sai_tren'], 3) ?>" class="form-control dung-sai">
                        </td>
                        <td>
                            <input type="number" step="0.001" name="dung_sai_duoi[<?php echo $key; ?>]" value="<?= number_format($item['dung_sai_duoi'], 3) ?>" class="form-control dung-sai">
                        </td>
                        <td>
                            <span class="min badge bg-info">Min: <?= number_format($item['min'], 3) ?></span>
                        </td>
                        <td>
                            <span class="max badge bg-info">Max: <?= number_format($item['max'], 3) ?></span>
                        </td>
                        <td>
                            <select multiple name="kich_thuoc_dung_cu[<?php echo $key; ?>][]" class="form-control dung-cu-select">
                                <?php
                                $sel = json_decode($item['dung_cu'], true) ?: [];
                                if ($sel) {
                                    $in = str_repeat('?,', count($sel)-1) . '?';
                                    $st = $pdo->prepare("SELECT id, name FROM images_reusable WHERE id IN ($in)");
                                    $st->execute($sel);
                                    foreach ($st->fetchAll() as $row) {
                                        echo "<option value='{$row['id']}' selected>{$row['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <input type="text" class="form-control mt-1" placeholder="Tìm dụng cụ" oninput="window.loadReusable('dung_cu', this.parentNode.querySelector('select'), this.value)">
                            <div class="dropzone mt-1" id="dz-dung-cu-<?php echo $key; ?>"></div>
                            <input type="hidden" name="kich_thuoc_hinh_dung_cu[<?php echo $key; ?>]" value="<?= $item['hinh_dung_cu'] ?>">
                            <?php if($item['hinh_dung_cu']) echo "<img src='{$item['hinh_dung_cu']}' class='img-preview mt-1'>"; ?>
                        </td>
                        <td>
                            <div class="dropzone" id="dz-phuong-kt-<?php echo $key; ?>"></div>
                            <input type="hidden" name="kich_thuoc_hinh_phuong[<?php echo $key; ?>]" value="<?= $item['hinh_phuong_phap'] ?>">
                            <?php if($item['hinh_phuong_phap']) echo "<img src='{$item['hinh_phuong_phap']}' class='img-preview'>"; ?>
                        </td>
                        <td>
                            <input type="text" name="kich_thuoc_ghi_chu[<?php echo $key; ?>]" value="<?= htmlspecialchars($item['ghi_chu']) ?>" class="form-control">
                        </td>
                        <td>
                            <button type="button" class="btn btn-delete remove-row"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success btn-lg mt-4"><i class="fas fa-save"></i> HOÀN THÀNH & LƯU</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
