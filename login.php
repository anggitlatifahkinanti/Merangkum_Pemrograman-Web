<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

// Jika sudah login, langsung ke daftar catatan
if (apakahLogin()) {
    header('Location: daftar.php');
    exit;
}

$galat = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    $usernameInput = ambilPost('username');
    $sandiInput    = ambilPost('sandi');

    if ($usernameInput === '' || $sandiInput === '') {
        $galat = 'Nama pengguna dan kata sandi wajib diisi.';
    } else {
        $stmt = $db->prepare('SELECT id, username, nama, peran, hash_sandi FROM pengguna WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $usernameInput);
        $stmt->execute();
        $hasil = $stmt->get_result();
        $penggunaDb = $hasil->fetch_assoc();
        $stmt->close();

        if ($penggunaDb && password_verify($sandiInput, $penggunaDb['hash_sandi'])) {
            // Regenerasi ID sesi untuk mencegah Session Fixation
            session_regenerate_id(true);

            $_SESSION['pengguna_id']       = (int)$penggunaDb['id'];
            $_SESSION['pengguna_username'] = $penggunaDb['username'];
            $_SESSION['pengguna_nama']     = $penggunaDb['nama'];
            $_SESSION['pengguna_peran']    = $penggunaDb['peran'];

            // Segarkan token CSRF setelah sesi baru aktif
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            $peranLabel = ambilPeranLabel($penggunaDb['peran']);
            setPesan("Selamat datang kembali, {$penggunaDb['nama']}! Anda berhasil masuk sebagai {$peranLabel}.", 'sukses');
            header('Location: daftar.php');
            exit;
        } else {
            $galat = 'Nama pengguna atau kata sandi yang Anda masukkan salah.';
        }
    }
}

$judulHalaman = 'Masuk Akun';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah-sempit">
    <div class="kartu-brutal">
        <div class="lencana lencana-kuning mb-2">AUTENTIKASI PENGGUNA</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Masuk ke Merangkum</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Silakan masukkan nama pengguna dan kata sandi Anda untuk mengakses catatan kuliah.
        </p>

        <?php if ($galat !== ''): ?>
            <div class="pesan-kilat pesan-bahaya mb-2" role="alert">
                <span>⚠️ <?= e($galat) ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?= inputCsrfToken() ?>

            <div class="form-grup">
                <label for="username" class="label-input">
                    Nama Pengguna (Username) <span class="wajib">*</span>
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="kontrol-input <?= $galat !== '' ? 'input-ada-galat' : '' ?>" 
                    value="<?= e($usernameInput) ?>" 
                    required 
                    autofocus 
                    placeholder="Contoh: admin atau anggota1"
                >
            </div>

            <div class="form-grup">
                <div class="baris-label">
                    <label for="sandi" class="label-input">
                        Kata Sandi <span class="wajib">*</span>
                    </label>
                    <a href="lupa_sandi.php" class="tautan-lupa">Lupa password?</a>
                </div>
                <div class="input-sandi-wrap">
                    <input 
                        type="password" 
                        id="sandi" 
                        name="sandi" 
                        class="kontrol-input <?= $galat !== '' ? 'input-ada-galat' : '' ?>" 
                        required 
                        placeholder="Masukkan kata sandi Anda"
                    >
                    <button type="button" class="tombol-intip-sandi" data-target="sandi" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
            </div>

            <button type="submit" class="tombol tombol-utama tombol-blok mt-2" style="font-size: 1.05rem;">
                🚀 Masuk Sekarang
            </button>
        </form>

        <div style="border-top: var(--border-sedang); margin-top: 1.5rem; padding-top: 1rem; text-align: center;">
            <p style="margin-bottom: 0.5rem;">Belum memiliki akun mahasiswa?</p>
            <a href="registrasi.php" class="tombol tombol-mint tombol-kecil">
                📝 Buat Akun Anggota Baru
            </a>
        </div>
    </div>

    <!-- Informasi Akun Demo Bawaan -->
    <div class="kartu-brutal kartu-kuning">
        <div class="lencana lencana-admin mb-1">AKUN DEMO BAWAAN</div>
        <p style="margin-bottom: 0.75rem; font-size: 0.95rem;">
            Free Akses Nih
        </p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.9rem;">
            <div style="background: var(--bg-kartu); color: var(--teks-utama); padding: 0.75rem; border: var(--border-sedang); border-radius: 4px;">
                <strong>👑 Akun Admin:</strong><br>
                Username: <code>admin</code><br>
                Sandi: <code>Admin123!</code><br>
                <small style="color: var(--teks-muted);">(Bisa kelola semua data)</small>
            </div>
            <div style="background: var(--bg-kartu); color: var(--teks-utama); padding: 0.75rem; border: var(--border-sedang); border-radius: 4px;">
                <strong>🎓 Akun Anggota 1:</strong><br>
                Username: <code>anggota1</code><br>
                Sandi: <code>Anggota123!</code><br>
                <small style="color: var(--teks-muted);">(Hanya kelola data sendiri)</small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>