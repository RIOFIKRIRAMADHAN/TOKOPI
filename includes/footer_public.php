<?php
// includes/footer_public.php
?>

    <!-- FOOTER PUBLIK -->
    <footer class="footer-tokopi">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="d-flex align-items-center gap-2 mb-3" style="color:#fff; font-family:'Playfair Display', serif; font-weight:700;">
                        <i class="bi bi-cup-hot-fill"></i> <span>Tokopi</span>
                    </h5>
                    <p class="small mb-3" style="opacity:.85;">Kopi pilihan, suasana hangat. Dibuat dengan penuh perhatian untuk setiap cangkir yang kamu nikmati.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="footer-social" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="footer-social" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="footer-social" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--sand);opacity:.7;letter-spacing:.1em;">Jelajahi</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/about.php">About</a></li>
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/menu.php">Menu</a></li>
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/blog.php">Blog</a></li>
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--sand);opacity:.7;letter-spacing:.1em;">Legal</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/faq.php">FAQ</a></li>
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/terms.php">Syarat &amp; Ketentuan</a></li>
                        <li class="mb-2"><a href="<?= $COMPANY_BASE ?>/privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-uppercase small fw-bold mb-3" style="color:var(--sand);opacity:.7;letter-spacing:.1em;">Kunjungi Kami</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Lippo Mall Kemang, Lantai GF No. 12, Jl. Pangeran Antasari No.36, Jakarta Selatan 12150</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>+62 21 1234 5678</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i>hello@tokopi.id</li>
                        <li class="mb-2"><i class="bi bi-clock me-2"></i>Setiap hari, 08.00 – 22.00 WIB</li>
                    </ul>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small" style="opacity:.7;">
                <span>&copy; <?= date('Y') ?> Tokopi. Semua hak dilindungi.</span>
                <span class="mt-2 mt-md-0">Dibuat dengan <i class="bi bi-cup-hot"></i> untuk pecinta kopi.</span>
            </div>
        </div>
    </footer>

    <style>
        .footer-tokopi { background: var(--espresso-dark); color: var(--sand); padding: 4rem 0 1.5rem; margin-top: 6rem; }
        .footer-tokopi a { color: var(--sand); text-decoration: none; transition: color 0.2s; }
        .footer-tokopi a:hover { color: #fff; }
        .footer-divider { border-color: rgba(225,220,201,.15); margin: 2rem 0 1rem; opacity: 1; }
        .footer-social { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .footer-social:hover { background: rgba(225,220,201,.15); }
        @media (max-width: 576px) { .footer-tokopi { padding: 3rem 0 1.25rem; } }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        'use strict';

        // Fade-in on scroll
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });

            document.querySelectorAll('.fade-in-up').forEach(function (el) {
                observer.observe(el);
            });
        } else {
            document.querySelectorAll('.fade-in-up').forEach(function (el) {
                el.classList.add('is-visible');
            });
        }

        // Auto-close mobile nav
        var navCollapse = document.getElementById('navPublic');
        if (navCollapse && window.bootstrap) {
            navCollapse.querySelectorAll('.nav-link, .btn').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 992) {
                        var bs = window.bootstrap.Collapse.getInstance(navCollapse);
                        if (bs) bs.hide();
                    }
                });
            });
        }
    })();
    </script>
</body>
</html>