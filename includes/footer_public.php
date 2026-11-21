<?php
// includes/footer_public.php
?>
    </main>

    <footer class="p-foot">
        <div class="wrap">
            <div class="p-foot-grid">
                <div>
                    <div class="big">Tokopi.</div>
                    <p style="max-width:30ch;">Kedai kopi lokal di Jakarta. Pesan dari meja, bayar QRIS atau tunai, dan kumpulkan poin.</p>
                    <p class="open-dot <?= $IS_OPEN_NOW ? '' : 'closed' ?>" style="color:var(--p-night-ink);"><?= $IS_OPEN_NOW ? 'Sedang buka' : 'Sedang tutup' ?> · <?= e($PUBLIC_HOURS['label']) ?></p>
                    <div class="p-social">
                        <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram Tokopi"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                        <a href="https://tiktok.com" target="_blank" rel="noopener" aria-label="TikTok Tokopi"><i class="bi bi-tiktok" aria-hidden="true"></i></a>
                        <a href="mailto:hello@tokopi.id" aria-label="Email Tokopi"><i class="bi bi-envelope" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div>
                    <h2>Cabang</h2>
                    <ul>
                        <?php foreach ($PUBLIC_OUTLETS as $__o): ?>
                            <li><a href="<?= $COMPANY_BASE ?>/contact.php#cabang-<?= (int)$__o['id'] ?>"><?= e(outlet_short($__o['nama'])) ?></a></li>
                        <?php endforeach; ?>
                        <?php if (!$PUBLIC_OUTLETS): ?><li>Jakarta</li><?php endif; ?>
                    </ul>
                </div>
                <div>
                    <h2>Jelajahi</h2>
                    <ul>
                        <li><a href="<?= $COMPANY_BASE ?>/menu.php">Menu</a></li>
                        <li><a href="<?= $COMPANY_BASE ?>/about.php">Cerita kami</a></li>
                        <li><a href="<?= $COMPANY_BASE ?>/blog.php">Jurnal</a></li>
                        <li><a href="<?= $APP_BASE ?>/customer/register.php">Daftar member</a></li>
                    </ul>
                </div>
                <div>
                    <h2>Bantuan</h2>
                    <ul>
                        <li><a href="<?= $COMPANY_BASE ?>/faq.php">FAQ</a></li>
                        <li><a href="<?= $COMPANY_BASE ?>/contact.php">Kontak</a></li>
                        <li><a href="<?= $COMPANY_BASE ?>/terms.php">Syarat &amp; ketentuan</a></li>
                        <li><a href="<?= $COMPANY_BASE ?>/privacy.php">Kebijakan privasi</a></li>
                    </ul>
                </div>
            </div>
            <div class="p-foot-bottom">
                <span>&copy; <?= date('Y') ?> Tokopi</span>
                <span>hello@tokopi.id · +62 21 1234 5678</span>
            </div>
        </div>
    </footer>

    <script>
    (function () {
        // Navbar: garis bawah saat discroll
        var nav = document.getElementById('pNav');
        var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 8); };
        onScroll(); window.addEventListener('scroll', onScroll, { passive: true });

        // Menu mobile
        var burger = document.getElementById('pBurger'), menu = document.getElementById('pMenu');
        function setOpen(o) {
            menu.classList.toggle('open', o);
            burger.setAttribute('aria-expanded', o ? 'true' : 'false');
            burger.innerHTML = '<i class="bi ' + (o ? 'bi-x-lg' : 'bi-list') + '" aria-hidden="true"></i>';
        }
        burger.addEventListener('click', function () { setOpen(!menu.classList.contains('open')); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        menu.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });

        // Reveal (dipakai hemat). Konten tetap terlihat bila JS/IO tidak ada.
        var els = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('in'); }); return; }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
        }, { rootMargin: '0px 0px -40px 0px' });
        els.forEach(function (el) { io.observe(el); });
    })();
    </script>
</body>
</html>
