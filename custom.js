Dropzone.autoDiscover = false;
// Global loadReusable: accept selector string, DOM element, or jQuery object
window.loadReusable = function (type, selectTarget, search = '') {
    let $select;
    if (typeof selectTarget === 'string') {
        $select = $(selectTarget);
    } else if (selectTarget instanceof jQuery) {
        $select = selectTarget;
    } else if (selectTarget instanceof Element) {
        $select = $(selectTarget);
    } else {
        $select = $([]);
    }
    $.ajax({
        url: 'api/reusable.php?type=' + encodeURIComponent(type) + '&search=' + encodeURIComponent(search),
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            if (!Array.isArray(data)) data = [];
            if ($select.length === 0) return;
            $select.each(function () {
                let $s = $(this);
                $s.empty();
                if (data.length === 0) {
                    $s.append('<option value="">(Không có dụng cụ tái sử dụng)</option>');
                } else {
                    data.forEach(img => {
                        if (typeof img === 'object') {
                            $s.append(`<option value="${img.id}">${img.name}</option>`);
                        } else {
                            $s.append(`<option value="${img}">${img}</option>`);
                        }
                    });
                }
            });
        },
        error: function () {
            if ($select.length) $select.empty().append('<option value="">Lỗi khi tải dụng cụ</option>');
        }
    });
};
// INIT DROPZONE
function initDropzone(selector, type = 'general', opts = {}) {
    const el = $(selector)[0];
    if (!el) return null;
    const conf = Object.assign({
        url: 'upload.php?type=' + encodeURIComponent(type),
        autoProcessQueue: true,
        paramName: 'file',
        acceptedFiles: 'image/*',
        maxFiles: (type === 'dung_cu' ? 10 : 1),
        addRemoveLinks: true,
        dictDefaultMessage: 'Kéo/thả hoặc Click để Insert',
        init: function () {
            this.on('success', function (file, resp) {
                let data = resp;
                if (typeof resp === 'string') {
                    try { data = JSON.parse(resp); } catch (e) { data = resp; }
                }
                if (!data) return;
                const elId = $(this.element).attr('id') || '';
                const last = elId.split('-').pop();
                if (elId === 'dz-ban-ve') {
                    $('#hinh_ban_ve_input').val(data.path || '');
                } else if (elId === 'dz-dung-cu') {
                    let cur = $('#hinh_dung_cu_ids').val() || '[]';
                    let arr = [];
                    try { arr = JSON.parse(cur); } catch (e) { arr = []; }
                    if (data.id) arr.push(data.id);
                    $('#hinh_dung_cu_ids').val(JSON.stringify(arr));
                } else if (elId.startsWith('dz-phuong-ngoai')) {
                    $(`[name="ngoai_quan_hinh_phuong[${last}]"]`).val(data.path);
                } else if (elId.startsWith('dz-ok')) {
                    $(`[name="ngoai_quan_hinh_ok[${last}]"]`).val(data.path);
                } else if (elId.startsWith('dz-ng')) {
                    $(`[name="ngoai_quan_hinh_ng[${last}]"]`).val(data.path);
                } else if (elId.startsWith('dz-dung-cu-')) {
                    $(`[name="kich_thuoc_hinh_dung_cu[${last}]"]`).val(data.path);
                } else if (elId.startsWith('dz-phuong-kt')) {
                    $(`[name="kich_thuoc_hinh_phuong[${last}]"]`).val(data.path);
                }
            });
        }
    }, opts);
    try {
        return new Dropzone(el, conf);
    } catch (e) {
        console.warn('Dropzone init failed for', selector, e);
        return null;
    }
}
// Init top-level dropzones
if ($('#dz-ban-ve').length) {
    initDropzone('#dz-ban-ve', 'hinh_ban_ve');
}
if ($('#dz-dung-cu').length) {
    const dzDungCu = initDropzone('#dz-dung-cu', 'dung_cu');
    if (dzDungCu) {
        dzDungCu.options.maxFiles = 10;
        dzDungCu.options.dictMaxFilesExceeded = "Bạn không thể thêm quá 10 hình!";
    }
}
// Init existing dropzones and load reusable
$(document).ready(function() {
    $('[id^="dz-"]').each(function() {
        let id = $(this).attr('id');
        if (id.match(/^dz-phuong-ngoai-\d+/)) initDropzone('#' + id, 'phuong_phap');
        else if (id.match(/^dz-ok-\d+/)) initDropzone('#' + id, 'ok');
        else if (id.match(/^dz-ng-\d+/)) initDropzone('#' + id, 'ng');
        else if (id.match(/^dz-dung-cu-\d+/)) initDropzone('#' + id, 'dung_cu');
        else if (id.match(/^dz-phuong-kt-\d+/)) initDropzone('#' + id, 'phuong_phap');
    });
    $('.dung-cu-select').each(function() { window.loadReusable('dung_cu', this); });
    $('.thong-so, .dung-sai').trigger('input'); // Calc min/max for existing
});
// === Add Ngoại Quan row ===
$('#add-ngoai-quan').click(function () {
    let id = Date.now();
    let row = `<tr data-id="${id}" class="fade-in-row">
        <td><select class="form-control" name="ngoai_quan_hang_muc[${id}]" required>
            <option value="">Chọn hạng mục</option>
            <option value="Ngoại quan bề mặt tổng thể">Ngoại quan bề mặt tổng thể</option>
            <option value="Ngoại quan cạnh/góc">Ngoại quan cạnh/góc</option>
            <option value="Ngoại quan rãnh">Ngoại quan rãnh</option>
            <option value="Ngoại quan ren ngoài">Ngoại quan ren ngoài</option>
            <option value="Ngoại quan ren trong">Ngoại quan ren trong</option>
            <option value="Ngoại quan lỗ">Ngoại quan lỗ</option>
            <option value="Ngoại quan mặt phay">Ngoại quan mặt phay</option>
            <option value="Ngoại quan rolect">Ngoại quan rolect</option>
            <option value="Ngoại quan mặt đầu">Ngoại quan mặt đầu</option>
        </select></td>
        <td>
            <select multiple class="form-control dung-cu-select" name="ngoai_quan_dung_cu[${id}][]"></select>
            <input type="text" class="form-control mt-1 find-reusable-input" placeholder="Tìm dụng cụ"
                oninput="window.loadReusable('dung_cu', this.parentNode.querySelector('select'), this.value)">
        </td>
        <td><div class="dropzone" id="dz-phuong-ngoai-${id}"></div><input type="hidden" name="ngoai_quan_hinh_phuong[${id}]"></td>
        <td><div class="dropzone" id="dz-ok-${id}"></div><input type="hidden" name="ngoai_quan_hinh_ok[${id}]"></td>
        <td>
            <div class="dropzone" id="dz-ng-${id}"></div><input type="hidden" name="ngoai_quan_hinh_ng[${id}]">
            <textarea name="notes_ng[${id}]" class="form-control mt-1" placeholder="Notes NG"></textarea>
        </td>
        <td><select class="form-control" name="ngoai_quan_tan_suat[${id}]" required>
            <option value="">Chọn tần suất</option>
            <option value="10%">10%</option>
            <option value="20%">20%</option>
            <option value="30%">30%</option>
            <option value="50%">50%</option>
            <option value="100%">100%</option>
        </select></td>
        <td><textarea name="ngoai_quan_ghi_chu[${id}]" class="form-control" placeholder="Ghi chú"></textarea></td>
        <td><button type="button" class="btn btn-delete remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    $('#ngoai-quan-table tbody').append(row);
    initDropzone(`#dz-phuong-ngoai-${id}`, 'phuong_phap');
    initDropzone(`#dz-ok-${id}`, 'ok');
    initDropzone(`#dz-ng-${id}`, 'ng');
    window.loadReusable('dung_cu', $(`[name="ngoai_quan_dung_cu[${id}][]"]`));
});
// === Add Kích Thước row ===
$('#add-kich-thuoc').click(function () {
    let id = Date.now();
    let row = `<tr data-id="${id}" class="fade-in-row">
        <td><select class="form-control" name="kich_thuoc_hang_muc[${id}]" required>
            <option value="">Chọn hạng mục</option>
            <option value="Đo tổng chiều dài">Đo tổng chiều dài</option>
            <option value="Đo đường kính ngoài">Đo đường kính ngoài</option>
            <option value="Đo đường kính trong">Đo đường kính trong</option>
            <option value="Kiểm tra ren">Kiểm tra ren</option>
            <option value="Kiểm tra lỗ bằng Pin">Kiểm tra lỗ bằng Pin</option>
            <option value="Kiểm tra đường kính bằng Ring">Kiểm tra đường kính bằng Ring</option>
            <option value="Đo đường kính rãnh">Đo đường kính rãnh</option>
            <option value="Đo kích thước chiều dài">Đo kích thước chiều dài</option>
        </select></td>
        <td><select class="form-control" name="kich_thuoc_tan_suat[${id}]" required>
            <option value="">Chọn tần suất</option>
            <option value="10%">10%</option>
            <option value="20%">20%</option>
            <option value="30%">30%</option>
            <option value="50%">50%</option>
            <option value="100%">100%</option>
        </select></td>
        <td><input type="number" step="0.001" name="thong_so_hang_muc[${id}]" class="form-control thong-so"></td>
        <td><input type="number" step="0.001" name="dung_sai_tren[${id}]" class="form-control dung-sai"></td>
        <td><input type="number" step="0.001" name="dung_sai_duoi[${id}]" class="form-control dung-sai"></td>
        <td><span class="min badge bg-info">Min: 0.000</span></td>
        <td><span class="max badge bg-info">Max: 0.000</span></td>
        <td>
            <select multiple class="form-control dung-cu-select" name="kich_thuoc_dung_cu[${id}][]"></select>
            <input type="text" class="form-control mt-1 find-reusable-input" placeholder="Tìm dụng cụ"
                oninput="window.loadReusable('dung_cu', this.parentNode.querySelector('select'), this.value)">
            <div class="dropzone mt-1" id="dz-dung-cu-${id}"></div>
            <input type="hidden" name="kich_thuoc_hinh_dung_cu[${id}]">
        </td>
        <td><div class="dropzone" id="dz-phuong-kt-${id}"></div><input type="hidden" name="kich_thuoc_hinh_phuong[${id}]"></td>
        <td><textarea name="kich_thuoc_ghi_chu[${id}]" class="form-control" placeholder="Ghi chú"></textarea></td>
        <td><button type="button" class="btn btn-delete remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    $('#kich-thuoc-table tbody').append(row);
    initDropzone(`#dz-dung-cu-${id}`, 'dung_cu');
    initDropzone(`#dz-phuong-kt-${id}`, 'phuong_phap');
    window.loadReusable('dung_cu', $(`[name="kich_thuoc_dung_cu[${id}][]"]`));
});
// Auto calc min/max
$(document).on('input', '.thong-so, .dung-sai', function () {
    let row = $(this).closest('tr');
    let val = parseFloat(row.find('.thong-so').val()) || 0;
    let tren = parseFloat(row.find('[name^="dung_sai_tren"]').val()) || 0;
    let duoi = parseFloat(row.find('[name^="dung_sai_duoi"]').val()) || 0;
    row.find('.min').text('Min: ' + (val + duoi).toFixed(3));
    row.find('.max').text('Max: ' + (val + tren).toFixed(3));
});
// Remove row
$(document).on('click', '.remove-row', function () {
    $(this).closest('tr').remove();
});
// Reindex functions
function reindexNgoaiQuan() {
    $('#ngoai-quan-table tbody tr').each(function (i) {
        $(this).find('select, input, textarea').each(function () {
            let name = $(this).attr('name');
            if (!name) return;
            name = name.replace(/$$ \d+ $$/, '[' + i + ']');
            $(this).attr('name', name);
        });
    });
}
function reindexKichThuoc() {
    $('#kich-thuoc-table tbody tr').each(function (i) {
        $(this).find('select, input, textarea').each(function () {
            let name = $(this).attr('name');
            if (!name) return;
            name = name.replace(/$$ \d+ $$/, '[' + i + ']');
            $(this).attr('name', name);
        });
    });
}
// Form submit
$('#inputForm').on('submit', function (e) {
    reindexNgoaiQuan();
    reindexKichThuoc();
    let valid = true;
    $('select[required], input[required]').each(function () {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            valid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    if (!valid) {
        e.preventDefault();
        alert('Vui lòng điền đầy đủ các trường bắt buộc.');
    }
});
// DataTables for list.php
if ($('#standards-table').length) {
    let table = $('#standards-table').DataTable({
        ajax: 'api/list.php',
        columns: [
            { data: 'ma_san_pham' },
            { data: 'ma_khach_hang' },
            { data: 'created_at' },
            {
                data: null,
                render: data =>
                    `<a href="template.php?id=${data.id}" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> Xem</a>
                     <a href="view.php?id=${data.id}" class="btn btn-success btn-sm"><i class="fas fa-print"></i> In</a>`
            }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json' },
        searching: true,
        paging: true,
        order: [[2, 'desc']]
    });
    setInterval(() => table.ajax.reload(null, false), 5000);
}
// Add CSS animation
if ($('style[data-added-fade]').length === 0) {
    $('head').append('<style data-added-fade>.fade-in-row { animation: fadeIn 0.3s; } @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }</style>');
}
// Cleanup duplicate column if exists
$('#kich-thuoc-table th').filter(function () { return $(this).text().trim() === 'Dụng Cụ'; }).each(function (i, th) {
    let idx = $(th).index();
    $(th).remove();
    $('#kich-thuoc-table tbody tr').each(function () { $(this).find('td').eq(idx).remove(); });
});