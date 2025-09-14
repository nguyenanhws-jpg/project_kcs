<?php
require 'template.php';
?>
<script>
    window.onload = function() {
        let sections = document.querySelectorAll('.section-product, .section-ngoai-quan, .section-kich-thuoc, .section-references');
        let totalPages = Math.ceil(sections.length / 2) + 1; // Estimate
        document.querySelectorAll('.total-pages').forEach(el => el.textContent = totalPages);
        let pageNum = 1;
        document.querySelectorAll('.page-number').forEach(el => el.textContent = pageNum++);
        window.print();
    }
</script>