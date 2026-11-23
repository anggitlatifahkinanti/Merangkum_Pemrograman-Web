<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

// Pengguna yang sudah login diarahkan ke daftar catatan
if (apakahLogin()) {
    header('Location: daftar.php');
    exit;
}

$galat = [];
$namaInput     = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    $namaInput           = ambilPost('nama');
    $usernameInput       = ambilPost('username');
    $sandiInput          = ambilPost('sandi');
    $konfirmasiSandi     = ambilPost('konfirmasi_sandi');

    // 1. Validasi Nama
    if ($error = validasiTeksWajib($namaInput, 'Nama lengkap', 3, 100)) {
        $galat['nama'] = $error;
    }

    // 2. Validasi Username
    if ($error = validasiUsername($usernameInput)) {
        $galat['username'] = $error;
    } else {
        // Periksa apakah username sudah terdaftar
        $stmtCek = $db->prepare('SELECT id FROM pengguna WHERE username = ? LIMIT 1');
        $stmtCek->bind_param('s', $usernameInput);
        $stmtCek->execute();
        $stmtCek->store_result();
        if ($stmtCek->num_rows > 0) {
            $galat['username'] = 'Nama pengguna sudah digunakan oleh mahasiswa lain. Silakan pilih nama pengguna lain.';
        }
        $stmtCek->close();
    }

    // 3. Validasi Sandi
    if ($error = validasiKataSandi($sandiInput)) {
        $galat['sandi'] = $error;
    } elseif ($sandiInput !== $konfirmasiSandi) {
        $galat['konfirmasi_sandi'] = 'Konfirmasi kata sandi tidak cocok dengan kata sandi.';
    }

    // 4. Jika valid, simpan ke database
    if (empty($galat)) {
        $hashSandi = password_hash($sandiInput, PASSWORD_DEFAULT);
        $peranTegas = 'anggota';

        $kodePemulihan = buatKodePemulihan();
        $hashKode = password_hash(normalisasiKodePemulihan($kodePemulihan), PASSWORD_DEFAULT);

        $stmtSimpan = $db->prepare('INSERT INTO pengguna (nama, username, peran, hash_sandi, hash_kode_pemulihan) VALUES (?, ?, ?, ?, ?)');
        $stmtSimpan->bind_param('sssss', $namaInput, $usernameInput, $peranTegas, $hashSandi, $hashKode);
        $stmtSimpan->execute();
        $stmtSimpan->close();

        $_SESSION['kode_pemulihan_tampil'] = $kodePemulihan;
        setPesan('Pendaftaran berhasil! Simpan kode pemulihan Anda di bawah ini.', 'sukses');
        header('Location: kode_pemulihan.php');
        exit;
    }
}

$judulHalaman = 'Pendaftaran Akun Anggota';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah-sempit">
    <div class="kartu-brutal">
        <div class="lencana lencana-mint mb-2">REGISTRASI ANGGOTA BARU</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Buat Akun Mahasiswa</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Bergabunglah dengan Merangkum untuk membaca, mencatat, dan mengelola materi kuliah Anda.
        </p>

        <?php if (!empty($galat)): ?>
            <div class="pesan-kilat pesan-bahaya mb-2" role="alert">
                <span>⚠️ Harap periksa kembali beberapa isian formulir di bawah ini.</span>
            </div>
        <?php endif; ?>

        <form action="registrasi.php" method="POST">
            <?= inputCsrfToken() ?>

            <div class="form-grup">
                <label for="nama" class="label-input">
                    Nama Lengkap <span class="wajib">*</span>
                </label>
                <input 
                    type="text" 
                    id="nama" 
                    name="nama" 
                    class="kontrol-input <?= isset($galat['nama']) ? 'input-ada-galat' : '' ?>" 
                    value="<?= e($namaInput) ?>" 
                    required 
                    placeholder="Contoh: Muhammad Ihsan"
                >
                <?php if (isset($galat['nama'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['nama']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grup">
                <label for="username" class="label-input">
                    Nama Pengguna (Username) <span class="wajib">*</span>
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="kontrol-input <?= isset($galat['username']) ? 'input-ada-galat' : '' ?>" 
                    value="<?= e($usernameInput) ?>" 
                    required 
                    placeholder="Contoh: ihsan_2026"
                >
                <div class="hint-input">3-30 karakter, hanya huruf, angka, dan garis bawah (_).</div>
                <?php if (isset($galat['username'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['username']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grup">
                <label for="sandi" class="label-input">
                    Kata Sandi <span class="wajib">*</span>
                </label>
                <div class="input-sandi-wrap">
                    <input 
                        type="password" 
                        id="sandi" 
                        name="sandi" 
                        class="kontrol-input <?= isset($galat['sandi']) ? 'input-ada-galat' : '' ?>" 
                        required 
                        placeholder="Minimal 6 karakter"
                    >
                    <button type="button" class="tombol-intip-sandi" data-target="sandi" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
                <?php if (isset($galat['sandi'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['sandi']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grup">
                <label for="konfirmasi_sandi" class="label-input">
                    Ulangi Kata Sandi <span class="wajib">*</span>
                </label>
                <div class="input-sandi-wrap">
                    <input 
                        type="password" 
                        id="konfirmasi_sandi" 
                        name="konfirmasi_sandi" 
                        class="kontrol-input <?= isset($galat['konfirmasi_sandi']) ? 'input-ada-galat' : '' ?>" 
                        required 
                        placeholder="Ketik ulang kata sandi Anda"
                    >
                    <button type="button" class="tombol-intip-sandi" data-target="konfirmasi_sandi" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
                <?php if (isset($galat['konfirmasi_sandi'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['konfirmasi_sandi']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Keterangan Peran Tetap -->
            <div class="kartu-brutal kartu-mint" style="padding: 0.75rem 1rem; margin-bottom: 1.25rem;">
                <span class="lencana lencana-kuning mb-1">KETENTUAN PERAN</span>
                <p style="font-size: 0.85rem; margin: 0; color: inherit; font-weight: 600;">
                    Pendaftaran mandiri secara otomatis menghasilkan peran <strong>Anggota</strong>. Hak akses Administrator diatur secara tepercaya oleh pengelola sistem.
                </p>
            </div>

            <button type="submit" class="tombol tombol-mint tombol-blok" style="font-size: 1.05rem;">
                ✨ Daftar Sekarang
            </button>
        </form>

        <div style="border-top: var(--border-sedang); margin-top: 1.5rem; padding-top: 1rem; text-align: center;">
            <p style="margin-bottom: 0.5rem;">Sudah memiliki akun?</p>
            <a href="login.php" class="tombol tombol-utama tombol-kecil">
                🔑 Masuk ke Akun Anda
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>