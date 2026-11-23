<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/validasi.php';

$judulHalaman = isset($judulHalaman) ? $judulHalaman . ' — Merangkum' : 'Merangkum — Bank Rangkuman & Catatan Kuliah';
$temaAktif = ambilTemaAktif();
$pengguna = penggunaSaatIni();
$pesanFlash = ambilPesan();
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($temaAktif) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Merangkum — Bank rangkuman dan catatan kuliah untuk mahasiswa">
    <title><?= e($judulHalaman) ?></title>
    <!-- Lembar Gaya Neo-Brutalism -->
    <link rel="stylesheet" href="css/gaya.css">
    <!-- Font Web Google (Space Grotesk & Plus Jakarta Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800;900&display=swap" rel="stylesheet">
</head>
<body class="<?= $temaAktif === 'gelap' ? 'tema-gelap' : '' ?>">

<!-- Navigasi Atas -->
<header class="nav-utama">
    <div class="nav-kontainer">
        <a href="daftar.php" class="nav-merek" title="Beranda Aplikasi Merangkum">
            <span class="nav-merek-logo">📚</span>
            <span>MERANGKUM</span>
        </a>

        <nav class="nav-menu">
            <?php if ($pengguna !== null): ?>
                <a href="daftar.php" class="tombol tombol-kecil <?= basename($_SERVER['PHP_SELF']) === 'daftar.php' ? 'tombol-aksen' : '' ?>">
                    📋 Daftar Rangkuman
                </a>
                <a href="tambah.php" class="tombol tombol-kecil tombol-mint <?= basename($_SERVER['PHP_SELF']) === 'tambah.php' ? 'tombol-aksen' : '' ?>">
                    ➕ Buat Catatan
                </a>
                <a href="ganti_sandi.php" class="tombol tombol-kecil <?= basename($_SERVER['PHP_SELF']) === 'ganti_sandi.php' ? 'tombol-aksen' : '' ?>">
                    🔐 Ganti Sandi
                </a>
                <div class="info-pengguna-bar">
                    <span class="info-pengguna-nama" title="<?= e($pengguna['nama']) ?>"><?= e($pengguna['nama']) ?></span>
                    <span class="lencana <?= $pengguna['peran'] === 'admin' ? 'lencana-admin' : 'lencana-anggota' ?>">
                        <?= $pengguna['peran'] === 'admin' ? 'ADMIN' : 'ANGGOTA' ?>
                    </span>
                </div>
            <?php else: ?>
                <a href="login.php" class="tombol tombol-kecil tombol-aksen">
                    🔑 Masuk
                </a>
                <a href="registrasi.php" class="tombol tombol-kecil tombol-mint">
                    📝 Daftar Akun
                </a>
            <?php endif; ?>

            <!-- Tombol Alih Tema Terang / Gelap -->
            <button type="button" id="tombol-alih-tema" class="tombol tombol-kecil" aria-label="Ganti tema tampilan">
                <?= $temaAktif === 'gelap' ? '☀️ Terang' : '🌙 Gelap' ?>
            </button>

            <?php if ($pengguna !== null): ?>
                <!-- Form Logout Aman (POST + CSRF) -->
                <form action="logout.php" method="POST" style="display:inline; margin:0;">
                    <?= inputCsrfToken() ?>
                    <button type="submit" class="tombol tombol-kecil tombol-bahaya" title="Keluar dari akun">
                        🚪 Keluar
                    </button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="wadah">
    <?php if ($pesanFlash !== null): ?>
        <div class="pesan-kilat pesan-<?= e($pesanFlash['tipe']) ?>" role="alert">
            <span style="font-size: 1.3rem;">
                <?= $pesanFlash['tipe'] === 'sukses' ? '✅' : ($pesanFlash['tipe'] === 'bahaya' ? '❌' : '⚠️') ?>
            </span>
            <span><?= e($pesanFlash['teks']) ?></span>
        </div>
    <?php endif; ?>