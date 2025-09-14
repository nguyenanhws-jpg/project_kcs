<?php
require 'config.php';
requireLogin();
include 'includes/header.php';
?>

<div class="card p-4 animation fadeIn">
    <h1 class="mb-4"><i class="fas fa-list"></i> DANH SÁCH CÁC TIÊU CHUẨN KIỂM TRA ĐÃ THIẾT LẬP</h1>
    <table id="standards-table" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>MÃ SẢN PHẨM</th>
                <th>MÃ KHÁCH HÀNG</th>
                <th>NGÀY TẠO</th>
                <th>MANAGER</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>