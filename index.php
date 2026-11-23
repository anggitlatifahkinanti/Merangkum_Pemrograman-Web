<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/validasi.php';

// Pengguna yang sudah login langsung dialihkan ke dashboard rangkuman
if (apakahLogin()) {
    header('Location: daftar.php');
    exit;
}

$judulHalaman = 'Selamat Datang';
require_once __DIR__ . '/kepala.php';
?>

<section class="kartu-brutal kartu-kuning text-center" style="padding: 3rem 1.5rem; margin-top: 1rem;">
    <div class="lencana lencana-admin mb-2">BANK CATATAN & RANGKUMAN KULIAH</div>
    <h1 style="font-size: 2.75rem; letter-spacing: -1px; margin-bottom: 1rem;">
        Kuasai Kuliahmu dengan <span style="background: var(--biru-lembut); padding: 0.1em 0.3em; border: var(--border-sedang); border-radius: 4px;">Merangkum</span>
    </h1>
    <p class="teks-lead" style="font-size: 1.2rem; max-width: 720px; margin: 0 auto 2rem; color: inherit; font-weight: 500;">
        Penyimpanan terstruktur untuk catatan kuliah, ringkasan bab, dan panduan belajar mahasiswa. Cepat, interaktif, responsif, dan aman berbasis PHP 8 native & MySQLi.
    </p>

    <div class="d-flex justify-center gap-2 flex-wrap">
        <a href="login.php" class="tombol tombol-utama" style="font-size: 1.1rem; padding: 0.85rem 1.75rem;">
            🔑 Masuk ke Akun
        </a>
        <a href="registrasi.php" class="tombol tombol-mint" style="font-size: 1.1rem; padding: 0.85rem 1.75rem;">
            📝 Daftar Anggota Baru
        </a>
    </div>
</section>

<!-- Penjelasan Hak Akses Sistem -->
<section class="kartu-brutal kartu-biru mt-3">
    <div class="lencana lencana-kuning mb-2">KEBIJAKAN HAK AKSES SISTEM</div>
    <h2 style="font-size: 1.6rem; margin-bottom: 0.75rem;">Dua Peran Pengguna yang Jelas dan Terlindungi di Server</h2>
    <p>Aplikasi Merangkum menerapkan validasi hak akses ketat di sisi server (PHP), bukan sekadar menyembunyikan elemen visual peramban:</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-top: 1.25rem;">
        <div class="kartu-brutal kartu-peran">
            <span class="lencana lencana-admin mb-1">PERAN ADMINISTRATOR</span>
            <h3 style="font-size: 1.2rem; margin-top: 0.5rem;">Akses Penuh Seluruh Sistem</h3>
            <ul style="padding-left: 1.25rem; margin-bottom: 0; line-height: 1.7;">
                <li>Bisa melihat semua rangkuman dari semua anggota.</li>
                <li>Bisa membuat rangkuman baru.</li>
                <li>Bisa mengedit dan menghapus <strong>seluruh rangkuman</strong>, termasuk milik anggota.</li>
            </ul>
        </div>

        <div class="kartu-brutal kartu-peran">
            <span class="lencana lencana-anggota mb-1">PERAN ANGGOTA (MAHASISWA)</span>
            <h3 style="font-size: 1.2rem; margin-top: 0.5rem;">Akses Kolaboratif Terproteksi</h3>
            <ul style="padding-left: 1.25rem; margin-bottom: 0; line-height: 1.7;">
                <li>Wajib login untuk menggunakan aplikasi.</li>
                <li>Bisa melihat semua rangkuman publik.</li>
                <li>Bisa membuat rangkuman baru.</li>
                <li>Hanya bisa mengedit & menghapus <strong>rangkuman miliknya sendiri</strong>.</li>
            </ul>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/kaki.php'; ?>