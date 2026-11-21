<?php
// includes/footer.php
?>
    </main><!-- /.content -->
</div><!-- /.main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Filter tabel sederhana: <input data-table-filter="#idTabel">
document.querySelectorAll('[data-table-filter]').forEach(function (inp) {
    if (document.querySelector(inp.dataset.tableFilter + '[data-enhance]')) return;
    var table = document.querySelector(inp.dataset.tableFilter);
    if (!table) return;
    inp.addEventListener('input', function () {
        var q = inp.value.trim().toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            tr.style.display = !q || tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
});
</script>
<script src="/tokopi/assets/js/admin-table.js?v=20261121b"></script>
<?php if (!empty($__scripts)) echo $__scripts; ?>
</body>
</html>
