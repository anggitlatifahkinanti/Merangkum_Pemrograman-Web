<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

if (apakahLogin()) {
    header('Location: ganti_sandi.php');
    exit;
}

$galat = '';
$usernameInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    $usernameInput = ambilPost('username');
    $kodeInput     = normalisasiKodePemulihan(ambilPost('kode_pemulihan'));
    $sandiBaru     = ambilPost('sandi_baru');
    $konfirmasi    = ambilPost('konfirmasi_sandi');

    if ($usernameInput === '' || $kodeInput === '') {
        $galat = 'Username dan kode pemulihan wajib diisi.';
    } elseif ($error = validasiKataSandi($sandiBaru)) {
        $galat = $error;
    } elseif ($sandiBaru !== $konfirmasi) {
        $galat = 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.';
    } else {
        $stmt = $db->prepare('SELECT id, hash_kode_pemulihan FROM pengguna WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $usernameInput);
        $stmt->execute();
        $baris = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $valid = $baris
            && !empty($baris['hash_kode_pemulihan'])
            && password_verify($kodeInput, $baris['hash_kode_pemulihan']);

        if (!$valid) {
            usleep(500000); // perlambat tebak-tebakan
            $galat = 'Username atau kode pemulihan tidak cocok.';
        } else {
            $idTarget  = (int)$baris['id'];
            $hashSandi = password_hash($sandiBaru, PASSWORD_DEFAULT);

            // Kode lama hangus; buat kode baru
            $kodeBaru = buatKodePemulihan();
            $hashKode = password_hash(normalisasiKodePemulihan($kodeBaru), PASSWORD_DEFAULT);

            $stmtUbah = $db->prepare('UPDATE pengguna SET hash_sandi = ?, hash_kode_pemulihan = ? WHERE id = ?');
            $stmtUbah->bind_param('ssi', $hashSandi, $hashKode, $idTarget);
            $stmtUbah->execute();
            $stmtUbah->close();

            $_SESSION['kode_pemulihan_tampil'] = $kodeBaru;
            setPesan('Kata sandi berhasil diatur ulang. Kode lama sudah tidak berlaku, simpan kode baru di bawah.', 'sukses');
            header('Location: kode_pemulihan.php');
            exit;
        }
    }
}

$judulHalaman = 'Lupa Kata Sandi';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah-sempit">
    <div class="kartu-brutal">
        <div class="lencana lencana-kuning mb-2">PEMULIHAN AKUN</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Lupa Kata Sandi?</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Masukkan username, kode pemulihan yang Anda dapat saat mendaftar, dan kata sandi baru.
        </p>

        <?php if ($galat !== ''): ?>
            <div class="pesan-kilat pesan-bahaya mb-2" role="alert">
                <span>⚠️ <?= e($galat) ?></span>
            </div>
        <?php endif; ?>

        <form action="lupa_sandi.php" method="POST">
            <?= inputCsrfToken() ?>

            <div class="form-grup">
                <label for="username" class="label-input">Nama Pengguna (Username) <span class="wajib">*</span></label>
                <input type="text" id="username" name="username" class="kontrol-input"
                       value="<?= e($usernameInput) ?>" required autofocus>
            </div>

            <div class="form-grup">
                <label for="kode_pemulihan" class="label-input">Kode Pemulihan <span class="wajib">*</span></label>
                <input type="text" id="kode_pemulihan" name="kode_pemulihan" class="kontrol-input"
                       required autocomplete="off" placeholder="XXXX-XXXX-XXXX">
            </div>

            <div class="form-grup">
                <label for="sandi_baru" class="label-input">Kata Sandi Baru <span class="wajib">*</span></label>
                <div class="input-sandi-wrap">
                    <input type="password" id="sandi_baru" name="sandi_baru" class="kontrol-input"
                           required placeholder="Minimal 6 karakter">
                    <button type="button" class="tombol-intip-sandi" data-target="sandi_baru" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
            </div>

            <div class="form-grup">
                <label for="konfirmasi_sandi" class="label-input">Ulangi Kata Sandi Baru <span class="wajib">*</span></label>
                <div class="input-sandi-wrap">
                    <input type="password" id="konfirmasi_sandi" name="konfirmasi_sandi" class="kontrol-input" required>
                    <button type="button" class="tombol-intip-sandi" data-target="konfirmasi_sandi" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
            </div>

            <button type="submit" class="tombol tombol-mint tombol-blok">🔑 Atur Ulang Kata Sandi</button>
        </form>

        <div style="border-top: var(--border-sedang); margin-top: 1.5rem; padding-top: 1rem;">
            <p style="font-size: 0.9rem; margin-bottom: 0.75rem;">
                <strong>Kode pemulihan hilang atau belum punya?</strong> Hubungi Administrator Merangkum agar dibuatkan kata sandi baru.
            </p>
            <a href="login.php" class="tombol tombol-kecil">⬅️ Kembali ke Halaman Masuk</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>