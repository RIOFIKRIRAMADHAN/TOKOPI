<?php
// includes/report_filter.php
// Filter bar seragam untuk dashboard & laporan.
// Butuh: $date_from, $date_to. Opsional: $filter_outlet, $outlets, $show_outlet (bool), $report_actions (HTML tombol kanan)
$__fo = (int)($filter_outlet ?? 0);
$__show_outlet = ($show_outlet ?? is_role('owner')) && !empty($outlets);
$__q = function ($from, $to) use ($__fo) {
    return '?date_from=' . $from . '&date_to=' . $to . ($__fo ? '&outlet=' . $__fo : '');
};
$__presets = [
    'Hari ini'   => [date('Y-m-d'), date('Y-m-d')],
    '7 hari'     => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')],
    '30 hari'    => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    'Bulan ini'  => [date('Y-m-01'), date('Y-m-d')],
    'Bulan lalu' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
    'Tahun ini'  => [date('Y-01-01'), date('Y-m-d')],
];
?>
<form method="GET" class="filter-bar" aria-label="Filter periode">
    <nav class="presets" aria-label="Periode cepat">
        <?php foreach ($__presets as $__label => $__r): $__on = ($date_from === $__r[0] && $date_to === $__r[1]); ?>
            <a href="<?= e($__q($__r[0], $__r[1])) ?>" class="<?= $__on ? 'active' : '' ?>" <?= $__on ? 'aria-current="true"' : '' ?>><?= e($__label) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="range">
        <label class="visually-hidden" for="fFrom">Dari tanggal</label>
        <input type="date" id="fFrom" name="date_from" class="form-control form-control-sm" value="<?= e($date_from) ?>" required>
        <span class="text-muted small">–</span>
        <label class="visually-hidden" for="fTo">Sampai tanggal</label>
        <input type="date" id="fTo" name="date_to" class="form-control form-control-sm" value="<?= e($date_to) ?>" required>
    </div>
    <?php if ($__show_outlet): ?>
        <label class="visually-hidden" for="fOutlet">Outlet</label>
        <select name="outlet" id="fOutlet" class="form-select form-select-sm" style="width:auto;">
            <option value="0">Semua outlet</option>
            <?php foreach ($outlets as $__o): ?>
                <option value="<?= (int)$__o['id'] ?>" <?= $__fo === (int)$__o['id'] ? 'selected' : '' ?>><?= e($__o['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <button type="submit" class="btn btn-espresso btn-sm"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Terapkan</button>
    <span class="spacer"></span>
    <?= $report_actions ?? '' ?>
</form>
