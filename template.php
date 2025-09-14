<?php
require 'config.php';
requireLogin();

// Helper: load dụng cụ từ DB reusable (copy từ view.php để template/print chạy độc lập)
function getReusableNames($pdo, $idsJson) {
    $ids = json_decode($idsJson, true);
    if (!$ids || !is_array($ids)) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, name, path FROM images_reusable WHERE id IN ($in)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['id']] = $r;
    }
    $result = [];
    foreach ($ids as $i) {
        if (isset($map[$i])) $result[] = $map[$i];
    }
    return $result;
}

$id = $_GET['id'] ?? null;
if (!$id) header('Location: list.php');

// lấy standard
$stmt = $pdo->prepare("SELECT * FROM standards WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$standard = $stmt->fetch();
if (!$standard) header('Location: list.php');

// lấy danh sách items
$stmt = $pdo->prepare("SELECT * FROM ngoai_quan_items WHERE standard_id = ?");
$stmt->execute([$id]);
$ngoai_quan = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM kich_thuoc_items WHERE standard_id = ?");
$stmt->execute([$id]);
$kich_thuoc = $stmt->fetchAll();

// hàm lấy path từ images_reusable
function getImagePathsFromIds($ids, $pdo) {
    if (!$ids) return [];
    $arr = json_decode($ids, true);
    if (!is_array($arr) || empty($arr)) return [];
    $in = str_repeat('?,', count($arr) - 1) . '?';
    $stmt = $pdo->prepare("SELECT path FROM images_reusable WHERE id IN ($in)");
    $stmt->execute($arr);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

include 'includes/header.php';
?>

<div class="print-container">
    <!-- Header chuyên nghiệp theo ISO/IATF -->
    <header class="print-header">
        <div class="logo"><img src="images/logo.png" alt="Company Logo" style="max-height: 50px;"></div>
        <h1>TIÊU CHUẨN KIỂM TRA SẢN PHẨM</h1>
        <div class="doc-info">
            <p>Mã Tài Liệu: TCKT-<?php echo $standard['id']; ?> | Phiên Bản: 1.0 | Ngày Ban Hành: <?php echo date('Y-m-d'); ?></p>
            <p>Theo IATF 16949:2016 Clause 8.6.2 (Layout Inspection) và ISO 9001:2015</p>
        </div>
    </header>

    <!-- Section 1: Thông tin sản phẩm -->
    <section class="section-product">
        <h2>Thông Tin Sản Phẩm</h2>
        <table class="table table-bordered">
            <colgroup>
                <col style="width: 25%;">
                <col style="width: 75%;">
            </colgroup>
            <tr>
                <th>Mã Sản Phẩm</th>
                <td><?php echo htmlspecialchars($standard['ma_san_pham']); ?></td>
            </tr>
            <tr>
                <th>Mã Khách Hàng</th>
                <td><?php echo htmlspecialchars($standard['ma_khach_hang']); ?></td>
            </tr>
            <tr>
                <th>Bản Vẽ Giản Lược</th>
                <td>
                    <?php if ($standard['hinh_ban_ve']): ?>
                        <img src="<?php echo $standard['hinh_ban_ve']; ?>" class="img-drawing">
                        <p class="caption">Hình 1: Bản vẽ giản lược sản phẩm</p>
                    <?php else: ?>
                        <em>Không có</em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Dụng Cụ Kiểm Tra</th>
                <td class="image-container">
                    <?php
                    $dung_cu = getReusableNames($pdo, $standard['hinh_dung_cu']);
                    if ($dung_cu): ?>
                        <?php foreach ($dung_cu as $dc): ?>
                            <div class="d-inline-block text-center me-3">
                                <img src="<?= $dc['path'] ?>" class="img-print">
                                <p class="caption"><?= htmlspecialchars($dc['name']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em>Không có</em>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <!-- Section 2: Ngoại Quan -->
    <section class="section-ngoai-quan">
        <h2>Phần 1: Kiểm Tra Ngoại Quan (Visual Inspection)</h2>
        <table class="table table-bordered print-table">
            <colgroup>
                <col style="width: 15%;"> <!-- Hạng Mục -->
                <col style="width: 15%;"> <!-- Dụng Cụ -->
                <col style="width: 15%;"> <!-- Phương Pháp -->
                <col style="width: 15%;"> <!-- OK -->
                <col style="width: 15%;"> <!-- NG -->
                <col style="width: 10%;"> <!-- Tần Suất -->
                <col style="width: 15%;"> <!-- Ghi Chú -->
            </colgroup>
            <thead>
                <tr>
                    <th>Hạng Mục</th>
                    <th>Dụng Cụ</th>
                    <th>Phương Pháp</th>
                    <th>OK</th>
                    <th>NG (Notes)</th>
                    <th>Tần Suất</th>
                    <th>Ghi Chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ngoai_quan as $key => $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['hang_muc']) ?></td>
                        <td class="image-container">
                            <?php
                            $imgs = getImagePathsFromIds($item['dung_cu'], $pdo);
                            $count = 1;
                            foreach($imgs as $img) {
                                echo "<img src='{$img}' class='img-print' alt='Dụng cụ {$count}'>";
                                echo "<p class='caption'>Hình {$count}: Dụng cụ</p>";
                                $count++;
                            }
                            ?>
                        </td>
                        <td class="image-container">
                            <?php if($item['hinh_phuong_phap']): ?>
                                <img src='<?php echo $item['hinh_phuong_phap']; ?>' class='img-print' alt='Phương pháp'>
                                <p class='caption'>Hình Phương Pháp</p>
                            <?php endif; ?>
                        </td>
                        <td class="image-container">
                            <?php if($item['hinh_ok']): ?>
                                <img src='<?php echo $item['hinh_ok']; ?>' class='img-print' alt='OK'>
                                <p class='caption'>Hình OK</p>
                            <?php endif; ?>
                        </td>
                        <td class="image-container">
                            <?php if($item['hinh_ng']): ?>
                                <img src='<?php echo $item['hinh_ng']; ?>' class='img-print' alt='NG'>
                                <p class='caption'>Hình NG</p>
                            <?php endif; ?>
                            <span class="notes-ng"><?= htmlspecialchars($item['notes_ng']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($item['tan_suat']) ?></td>
                        <td><?= htmlspecialchars($item['ghi_chu']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Section 3: Kích Thước -->
    <section class="section-kich-thuoc">
        <h2>Phần 2: Kiểm Tra Kích Thước (Dimensional Inspection)</h2>
        <table class="table table-bordered print-table">
            <colgroup>
                <col style="width: 12%;"> <!-- Hạng Mục -->
                <col style="width: 8%;"> <!-- Tần Suất -->
                <col style="width: 8%;"> <!-- Thông Số -->
                <col style="width: 8%;"> <!-- Dung Sai Trên -->
                <col style="width: 8%;"> <!-- Dung Sai Dưới -->
                <col style="width: 6%;"> <!-- Min -->
                <col style="width: 6%;"> <!-- Max -->
                <col style="width: 15%;"> <!-- Dụng Cụ -->
                <col style="width: 15%;"> <!-- Hình Dụng Cụ -->
                <col style="width: 15%;"> <!-- Hình Phương Pháp -->
                <col style="width: 9%;"> <!-- Ghi Chú -->
            </colgroup>
            <thead>
                <tr>
                    <th>Hạng Mục</th>
                    <th>Tần Suất</th>
                    <th>Thông Số</th>
                    <th>Dung Sai Trên</th>
                    <th>Dung Sai Dưới</th>
                    <th>Min</th>
                    <th>Max</th>
                    <th>Dụng Cụ</th>
                    <th>Hình Dụng Cụ</th>
                    <th>Hình Phương Pháp</th>
                    <th>Ghi Chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($kich_thuoc as $key => $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['hang_muc']) ?></td>
                        <td><?= htmlspecialchars($item['tan_suat']) ?></td>
                        <td><?= number_format($item['thong_so_hang_muc'], 3) ?></td>
                        <td><?= number_format($item['dung_sai_tren'], 3) ?></td>
                        <td><?= number_format($item['dung_sai_duoi'], 3) ?></td>
                        <td><?= number_format($item['min'], 3) ?></td>
                        <td><?= number_format($item['max'], 3) ?></td>
                        <td class="image-container">
                            <?php
                            $imgs = getImagePathsFromIds($item['dung_cu'], $pdo);
                            $count = 1;
                            foreach($imgs as $img) {
                                echo "<img src='{$img}' class='img-print' alt='Dụng cụ {$count}'>";
                                echo "<p class='caption'>Hình {$count}: Dụng cụ</p>";
                                $count++;
                            }
                            ?>
                        </td>
                        <td class="image-container">
                            <?php if($item['hinh_dung_cu']): ?>
                                <img src='<?php echo $item['hinh_dung_cu']; ?>' class='img-print' alt='Hình Dụng Cụ'>
                                <p class='caption'>Hình Dụng Cụ</p>
                            <?php endif; ?>
                        </td>
                        <td class="image-container">
                            <?php if($item['hinh_phuong_phap']): ?>
                                <img src='<?php echo $item['hinh_phuong_phap']; ?>' class='img-print' alt='Hình Phương Pháp'>
                                <p class='caption'>Hình Phương Pháp</p>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($item['ghi_chu']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Section 4: Tham chiếu và phê duyệt (ISO/IATF compliance) -->
    <section class="section-references">
        <h2>Tham Chiếu Tiêu Chuẩn</h2>
        <p>- IATF 16949:2016 Clause 8.6.2: Layout Inspection và Functional Testing.</p>
        <p>- ISO 9001:2015 Clause 8.5: Production and Service Provision (Kiểm soát quá trình kiểm tra).</p>
        <p>Kế Hoạch Sampling: Theo ISO 2859 (AQL Level: 1.0, General Inspection Level II) - Chi tiết tùy sản phẩm rủi ro cao/thấp.</p>
    </section>

    <!-- Footer chuyên nghiệp -->
    <footer class="print-footer">
        <p>Phê Duyệt Bởi: ________________________ Ngày: ________________________</p>
        <p>Kiểm Tra Bởi: ________________________ Ngày: ________________________</p>
        <p> Trang <span class="page-number"></span> / <span class="total-pages"></span> | Bảo Mật: Nội Bộ VNJSC</p>
    </footer>

    <div class="mt-3 no-print">
        <a href="list.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay Lại</a>
        <a href="template.php?id=<?= $standard['id'] ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Sửa</a>
        <a href="print.php?id=<?= $standard['id'] ?>" class="btn btn-success" target="_blank"><i class="fas fa-print"></i> In</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>