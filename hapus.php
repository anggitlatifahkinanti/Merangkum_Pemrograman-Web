<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

wajibLogin();

// Hanya izinkan metode POST demi keamanan dari penghapusan tidak disengaja / CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: daftar.php');
    exit;
}

if (!validasiCsrfToken()) {
    tolakCsrf();
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id) {
    setPesan('Permintaan penghapusan gagal: ID catatan tidak valid.', 'bahaya');
    header('Location: daftar.php');
    exit;
}

// 1. Ambil data catatan untuk verifikasi hak akses kepemilikan
$stmt = $db->prepare('SELECT id, pengguna_id, judul FROM catatan_kuliah WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$hasil = $stmt->get_result();
$catatan = $hasil->fetch_assoc();
$stmt->close();

if (!$catatan) {
    setPesan('Catatan yang hendak dihapus tidak ditemukan atau sudah dihapus sebelumnya.', 'peringatan');
    header('Location: daftar.php');
    exit;
}

// 2. VERIFIKASI HAK AKSES SERVER SECARA KETAT
if (!bisaKelolaCatatan((int)$catatan['pengguna_id'])) {
    tampilkanAksesDitolak('Anda tidak memiliki izin untuk menghapus rangkuman ini. Rangkuman ini merupakan milik mahasiswa lain.');
}

// 3. Eksekusi penghapusan permanen dari basis data
$stmtHapus = $db->prepare('DELETE FROM catatan_kuliah WHERE id = ?');
$stmtHapus->bind_param('i', $id);
$stmtHapus->execute();
$stmtHapus->close();

setPesan("Rangkuman '{$catatan['judul']}' telah berhasil dihapus secara permanen.", 'sukses');
header('Location: daftar.php');
exit;