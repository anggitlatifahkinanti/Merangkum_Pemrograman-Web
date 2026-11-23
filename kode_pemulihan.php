<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/validasi.php';

$kode = $_SESSION['kode_pemulihan_tampil'] ?? null;
unset($_SESSION['kode_pemulihan_tampil']);

if (!is_string($kode) || $kode === '') {
    header('Location: ' . (apakahLogin() ? 'daftar.php' : 'login.php'));
    exit;
}

header('Cache-Control: no-store');

$judulHalaman = 'Kode Pemulihan Akun';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah-sempit">
    <div class="kartu-brutal">
        <div class="lencana lencana-kuning mb-2">KODE PEMULIHAN AKUN</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Simpan Kode Ini!</h1>
        <p style="color: var(--teks-muted);">
            Kode ini dipakai untuk mengatur ulang kata sandi jika Anda lupa. 
            <strong>Hanya ditampilkan sekali</strong> dan tidak bisa dilihat lagi. Catat atau simpan di tempat aman.
        </p>

        <div class="kotak-kode"><?= e($kode) ?></div>

        <button type="button" class="tombol tombol-kecil mb-2"
                onclick="navigator.clipboard.writeText('<?= e($kode) ?>'); this.textContent='✅ Tersalin';">
            📋 Salin Kode
        </button>

        <a href="<?= apakahLogin() ? 'daftar.php' : 'login.php' ?>" class="tombol tombol-utama tombol-blok">
            Saya sudah menyimpannya →
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>