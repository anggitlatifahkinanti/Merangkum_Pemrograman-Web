<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

wajibLogin();

if (!apakahAdmin()) {
    tampilkanAksesDitolak('Hanya Administrator yang dapat mereset kata sandi pengguna.');
}

$galat = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    $usernameInput = ambilPost('username');
    $sandiBaru     = ambilPost('sandi_baru');

    if ($error = validasiUsername($usernameInput)) {
        $galat = $error;
    } elseif ($error = validasiKataSandi($sandiBaru)) {
        $galat = $error;
    } else {
        $stmt = $db->prepare('SELECT id FROM pengguna WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $usernameInput);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target) {
            $galat = 'Username tidak ditemukan.';
        } else {
            $hash = password_hash($sandiBaru, PASSWORD_DEFAULT);
            $idTarget = (int)$target['id'];

            $stmtUbah = $db->prepare('UPDATE pengguna SET hash_sandi = ? WHERE id = ?');
            $stmtUbah->bind_param('si', $hash, $idTarget);
            $stmtUbah->execute();
            $stmtUbah->close();

            setPesan("Kata sandi untuk '{$usernameInput}' berhasil direset. Sampaikan sandi baru ke pemilik akun.", 'sukses');
            header('Location: reset_sandi.php');
            exit;
        }
    }
}

$judulHalaman = 'Reset Kata Sandi';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah-sempit">
    <div class="kartu-brutal">
        <div class="lencana lencana-admin mb-2">KHUSUS ADMINISTRATOR</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Reset Kata Sandi Pengguna</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Masukkan username pengguna dan kata sandi baru untuknya.
        </p>

        <?php if ($galat !== ''): ?>
            <div class="pesan-kilat pesan-bahaya mb-2" role="alert">
                <span>⚠️ <?= e($galat) ?></span>
            </div>
        <?php endif; ?>

        <form action="reset_sandi.php" method="POST">
            <?= inputCsrfToken() ?>

            <div class="form-grup">
                <label for="username" class="label-input">
                    Username Pengguna <span class="wajib">*</span>
                </label>
                <input type="text" id="username" name="username" class="kontrol-input"
                       value="<?= e($usernameInput) ?>" required placeholder="Contoh: anggota1">
            </div>

            <div class="form-grup">
                <label for="sandi_baru" class="label-input">
                    Kata Sandi Baru <span class="wajib">*</span>
                </label>
                <div class="input-sandi-wrap">
                    <input type="password" id="sandi_baru" name="sandi_baru" class="kontrol-input"
                           required placeholder="Minimal 6 karakter">
                    <button type="button" class="tombol-intip-sandi" data-target="sandi_baru" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
            </div>

            <button type="submit" class="tombol tombol-mint tombol-blok">
                🔑 Reset Kata Sandi
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>