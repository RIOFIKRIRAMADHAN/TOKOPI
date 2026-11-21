<?php
// company/contact.php — Cabang & Kontak
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Cabang & kontak';
$page_desc  = 'Alamat, jam buka, dan kontak semua cabang Tokopi. Kirim pesan untuk kerja sama atau pertanyaan.';
$active_nav = 'contact';
require_once __DIR__ . '/../includes/header_public.php';

$success = flash_get('success');
$error   = flash_get('error');
?>

<section class="page-intro">
    <div class="wrap">
        <div class="kicker"><b><?= count($PUBLIC_OUTLETS) ?></b> cabang · <?= e($PUBLIC_HOURS['label']) ?></div>
        <h1>Cabang &amp; kontak</h1>
        <p class="lead">Datang langsung, telepon cabang, atau tulis pesan lewat form di bawah.</p>
    </div>
</section>

<?php
$map_q = 'outlet_map_query';
$first = $PUBLIC_OUTLETS[0] ?? null;
?>
<?php if ($PUBLIC_OUTLETS): ?>
<section class="section-tight">
    <div class="wrap">
        <div class="locator">
            <div class="locator-list" role="tablist" aria-label="Pilih cabang">
                <?php foreach ($PUBLIC_OUTLETS as $i => $o): ?>
                    <button type="button" class="loc-item <?= $i === 0 ? 'active' : '' ?>" role="tab"
                        id="cabang-<?= (int)$o['id'] ?>"
                        aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="mapFrame"
                        data-q="<?= e($map_q($o)) ?>" data-name="<?= e(outlet_short($o['nama'])) ?>">
                        <span class="loc-head">
                            <span class="loc-name"><?= e(outlet_short($o['nama'])) ?></span>
                            <span class="code"><?= e($o['code']) ?></span>
                        </span>
                        <span class="loc-addr"><?= e($o['alamat'] ?: 'Jakarta') ?></span>
                        <span class="loc-meta">
                            <span><i class="bi bi-clock" aria-hidden="true"></i> <?= e($PUBLIC_HOURS['label']) ?></span>
                            <?php if (!empty($o['no_hp'])): ?><span><i class="bi bi-telephone" aria-hidden="true"></i> <?= e($o['no_hp']) ?></span><?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="locator-map">
                <iframe id="mapFrame" title="Peta lokasi Tokopi <?= e(outlet_short($first['nama'])) ?>"
                    src="https://maps.google.com/maps?q=<?= urlencode($map_q($first)) ?>&amp;z=15&amp;output=embed"
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section-tight" style="background:var(--p-paper-2);">
    <div class="wrap contact-wrap">
        <div class="contact-aside">
            <div class="kicker"><b>Pesan</b></div>
            <h2 class="h-lg">Tulis ke kami.</h2>
            <p class="muted" style="margin-top:12px;">Untuk kerja sama, acara, pesanan jumlah besar, atau masukan. Kami membalas lewat email.</p>
            <ul class="contact-direct">
                <li><i class="bi bi-envelope" aria-hidden="true"></i><a href="mailto:hello@tokopi.id">hello@tokopi.id</a></li>
                <li><i class="bi bi-telephone" aria-hidden="true"></i><a href="tel:+622112345678">+62 21 1234 5678</a></li>
            </ul>
        </div>

        <div class="form-card">
            <?php if ($success): ?>
                <div class="notice ok" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <span><?= e($success) ?></span></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="notice err" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> <span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form action="<?= $COMPANY_BASE ?>/contact_submit.php" method="POST" novalidate id="contactForm" class="form-grid">
                <?= csrf_field() ?>
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <label>Website <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="field">
                    <label for="cNama">Nama</label>
                    <input type="text" id="cNama" name="nama" required autocomplete="name" placeholder="Nama kamu">
                </div>
                <div class="field">
                    <label for="cEmail">Email</label>
                    <input type="email" id="cEmail" name="email" required autocomplete="email" placeholder="nama@email.com">
                </div>
                <div class="field">
                    <label for="cPhone">No. HP <span class="muted" style="font-weight:500;">(opsional)</span></label>
                    <input type="tel" id="cPhone" name="phone" autocomplete="tel" inputmode="tel" placeholder="08xx xxxx xxxx">
                </div>
                <div class="field">
                    <label for="cSubject">Topik</label>
                    <input type="text" id="cSubject" name="subject" required list="topikList" placeholder="Pilih atau ketik topik">
                    <datalist id="topikList">
                        <option value="Kerja sama">
                        <option value="Acara / reservasi">
                        <option value="Pesanan jumlah besar">
                        <option value="Masukan">
                    </datalist>
                </div>
                <div class="field span-2">
                    <label for="cMsg">Pesan</label>
                    <textarea id="cMsg" name="message" required rows="4" placeholder="Ceritakan kebutuhanmu…"></textarea>
                </div>
                <div class="form-foot span-2">
                    <span class="muted">Balasan dikirim ke email di atas.</span>
                    <button type="submit" class="btn-p">Kirim pesan <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
// Pilih cabang -> peta berpindah
(function () {
    var items = document.querySelectorAll('.loc-item');
    var frame = document.getElementById('mapFrame');
    if (!frame) return;
    function select(btn, focus) {
        items.forEach(function (b) { var on = b === btn; b.classList.toggle('active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); b.tabIndex = on ? 0 : -1; });
        var q = encodeURIComponent(btn.dataset.q);
        frame.src = 'https://maps.google.com/maps?q=' + q + '&z=15&output=embed';
        frame.title = 'Peta lokasi Tokopi ' + btn.dataset.name;
        if (focus) btn.focus();
    }
    items.forEach(function (b, i) {
        b.tabIndex = i === 0 ? 0 : -1;
        b.addEventListener('click', function () { select(b); });
        b.addEventListener('keydown', function (e) {
            var k = e.key, n = null;
            if (k === 'ArrowDown' || k === 'ArrowRight') n = items[(i + 1) % items.length];
            if (k === 'ArrowUp' || k === 'ArrowLeft') n = items[(i - 1 + items.length) % items.length];
            if (n) { e.preventDefault(); select(n, true); }
        });
    });
    // Link dari footer (#cabang-ID) langsung memilih cabang
    var h = location.hash && document.querySelector(location.hash + '.loc-item');
    if (h) select(h);
})();
</script>
<script>
// Validasi inline: pesan error muncul di bawah field yang bermasalah
(function () {
    var f = document.getElementById('contactForm');
    f.addEventListener('submit', function (e) {
        var first = null;
        f.querySelectorAll('[required]').forEach(function (el) {
            var box = el.closest('.field'), msg = box.querySelector('.err-msg');
            var bad = !el.value.trim() || (el.type === 'email' && !/^\S+@\S+\.\S+$/.test(el.value));
            if (bad) {
                if (!msg) { msg = document.createElement('div'); msg.className = 'err-msg hint'; msg.style.color = '#8A2626'; msg.id = el.id + 'Err'; box.appendChild(msg); }
                msg.textContent = el.type === 'email' && el.value.trim() ? 'Format email belum benar.' : 'Bagian ini perlu diisi.';
                el.setAttribute('aria-invalid', 'true');
                el.setAttribute('aria-describedby', msg.id);
                el.style.borderColor = '#A83232';
                first = first || el;
            } else if (msg) {
                msg.remove(); el.removeAttribute('aria-invalid'); el.style.borderColor = '';
            }
        });
        if (first) { e.preventDefault(); first.focus(); }
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
