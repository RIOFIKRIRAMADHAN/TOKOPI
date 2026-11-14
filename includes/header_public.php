<?php
// includes/header_public.php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- PATH SETUP (Aman dari undefined) ---
if (!isset($COMPANY_BASE)) {
    $__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(.*?)/company/#', $__script, $__m)) {
        $__app = rtrim($__m[1], '/');
    } else {
        $__app = rtrim(dirname($__script), '/');
    }
    $APP_BASE     = $__app;
    $COMPANY_BASE = $__app . '/company';
}

$page_title   = $page_title ?? 'Tokopi';
$active_nav   = $active_nav ?? '';
$is_logged_in = function_exists('is_logged_in') ? is_logged_in() : !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> · Tokopi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --espresso: #412D15;
            --espresso-dark: #2E1F0E;
            --sand: #E1DCC9;
            --bg: #EDEAE3;
            --bg-alt: #FAF9F5;
            --text: #2B1F12;
            --text-muted: #6B5B4A;
            --accent: #B8853A;
        }

        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        html,
        body {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            background: var(--bg-alt);
            line-height: 1.6;
            padding-top: 76px;
            /* Offset untuk navbar sticky */
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Playfair Display', serif;
            color: var(--espresso);
        }

        .money {
            font-family: 'JetBrains Mono', monospace;
            font-variant-numeric: tabular-nums;
        }

        /* ---------- NAVBAR PUBLIK ---------- */
        .navbar-tokopi {
            background: rgba(250, 249, 245, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(65, 45, 21, 0.08);
        }

        .navbar-tokopi .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--espresso) !important;
            letter-spacing: -0.02em;
        }

        .navbar-tokopi .nav-link {
            color: var(--text);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.2s;
        }

        .navbar-tokopi .nav-link:hover,
        .navbar-tokopi .nav-link.active {
            color: var(--espresso);
        }

        .navbar-tokopi .navbar-toggler:focus {
            box-shadow: none;
        }

        /* ---------- BUTTON ---------- */
        .btn-espresso {
            background: var(--espresso);
            color: #fff !important;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .btn-espresso:hover {
            background: var(--espresso-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(65, 45, 21, 0.25);
            color: #fff;
        }

        .btn-outline-light:hover {
            color: var(--espresso) !important;
        }

        /* ---------- UTILITY ---------- */
        .eyebrow {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 700;
            color: var(--accent);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--espresso);
        }

        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .fade-in-up.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ---------- CTA BAND (global component) ---------- */
        .cta-band {
            background: linear-gradient(135deg, var(--espresso) 0%, var(--espresso-dark) 100%);
            border-radius: 1.5rem;
            padding: 3rem;
            color: #fff;
        }

        @media (max-width: 576px) {
            .navbar-tokopi .btn-espresso {
                width: 100%;
                margin-top: 0.5rem;
            }

            .cta-band {
                padding: 2rem 1.5rem;
                border-radius: 1rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .fade-in-up {
                opacity: 1;
                transform: none;
                transition: none;
            }

            html,
            body {
                scroll-behavior: auto;
            }
        }

        /* --- FIX: Teks di atas background gelap (Hero) --- */
        .hero h1,
        .hero h2,
        .hero h3,
        .hero p,
        .hero span:not(.eyebrow),
        .page-hero h1,
        .page-hero h2,
        .page-hero h3,
        .page-hero p,
        .page-hero span:not(.eyebrow) {
            color: #FFFFFF !important;
        }

        /* Biar teks pendukung agak transparan/lembut */
        .hero p,
        .page-hero p {
            opacity: 0.85;
        }
    </style>
</head>

<body>

    <!-- NAVBAR PUBLIK -->
    <nav class="navbar navbar-expand-lg navbar-tokopi fixed-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $COMPANY_BASE ?>/index.php">
                <i class="bi bi-cup-hot-fill"></i> <span>Tokopi</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navPublic">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navPublic">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 mt-3 mt-lg-0">
                    <li class="nav-item"><a class="nav-link <?= $active_nav === 'home' ? 'active' : '' ?>" href="<?= $COMPANY_BASE ?>/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active_nav === 'about' ? 'active' : '' ?>" href="<?= $COMPANY_BASE ?>/about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active_nav === 'menu' ? 'active' : '' ?>" href="<?= $COMPANY_BASE ?>/menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active_nav === 'blog' ? 'active' : '' ?>" href="<?= $COMPANY_BASE ?>/blog.php">Blog</a></li>
                    <li class="nav-item"><a class="nav-link <?= $active_nav === 'contact' ? 'active' : '' ?>" href="<?= $COMPANY_BASE ?>/contact.php">Contact</a></li>

                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <?php if ($is_logged_in): ?>
                            <a href="<?= $APP_BASE ?>/customer/home.php" class="btn btn-espresso btn-sm px-3 py-2">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a href="<?= $APP_BASE ?>/customer/login.php" class="btn btn-espresso btn-sm px-3 py-2">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>