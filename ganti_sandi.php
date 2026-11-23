<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

wajibLogin();

$pengguna = penggunaSaatIni();
$idSaya = $pengguna['id'];

$galatSandi = [];
$galatKode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    $aksi      = ambilPost('aksi');
    $sandiLama = ambilPost('sandi_lama');

    // Ambil hash sandi saat ini untuk verifikasi
    $stmt = $db->prepare('SELECT hash_sandi FROM pengguna WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $idSaya);
    $stmt->execute();
    $baris = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $sandiLamaBenar = $baris && password_verify($sandiLama, $baris['hash_sandi']);

    if ($aksi === 'ganti_sandi') {
        $sandiBaru  = ambilPost('sandi_baru');
        $konfirmasi = ambilPost('konfirmasi_sandi');

        if (!$sandiLamaBenar) {
            $galatSandi['sandi_lama'] = 'Kata sandi saat ini salah.';
        }

        if ($error = validasiKataSandi($sandiBaru)) {
            $galatSandi['sandi_baru'] = $error;
        } elseif ($sandiBaru !== $konfirmasi) {
            $galatSandi['konfirmasi_sandi'] = 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.';
        } elseif ($sandiBaru === $sandiLama) {
            $galatSandi['sandi_baru'] = 'Kata sandi baru tidak boleh sama dengan kata sandi saat ini.';
        }

        if (empty($galatSandi)) {
            $hashBaru = password_hash($sandiBaru, PASSWORD_DEFAULT);
            $stmtUbah = $db->prepare('UPDATE pengguna SET hash_sandi = ? WHERE id = ?');
            $stmtUbah->bind_param('si', $hashBaru, $idSaya);
            $stmtUbah->execute();
            $stmtUbah->close();

            session_regenerate_id(true);
            setPesan('Kata sandi Anda berhasil diubah.', 'sukses');
            header('Location: ganti_sandi.php');
            exit;
        }
    } elseif ($aksi === 'kode_baru') {
        if (!$sandiLamaBenar) {
            $galatKode = 'Kata sandi saat ini salah.';
        } else {
            $kodeBaru = buatKodePemulihan();
            $hashKode = password_hash(normalisasiKodePemulihan($kodeBaru), PASSWORD_DEFAULT);

            $stmtKode = $db->prepare('UPDATE pengguna SET hash_kode_pemulihan = ? WHERE id = ?');
            $stmtKode->bind_param('si', $hashKode, $idSaya);
            $stmtKode->execute();
            $stmtKode->close();

            $_SESSION['kode_pemulihan_tampil'] = $kodeBaru;
            setPesan('Kode pemulihan baru dibuat. Kode lama sudah tidak berlaku.', 'sukses');
            header('Location: kode_pemulihan.php');
            exit;
        }
    }
}

// Apakah akun ini sudah punya kode pemulihan?
$stmtInfo = $db->prepare('SELECT (hash_kode_pemulihan IS NOT NULL) AS punya_kode FROM pengguna WHERE id = ? LIMIT 1');
$stmtInfo->bind_param('i', $idSaya);
$stmtInfo->execute();
$punyaKode = (bool)($stmtInfo->get_result()->fetch_assoc()['punya_kode'] ?? false);
$stmtInfo->close();

$judulHalaman = 'Ganti Kata Sandi';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah-sempit">
    <!-- Form Ganti Sandi -->
    <div class="kartu-brutal">
        <div class="lencana lencana-mint mb-2">KEAMANAN AKUN</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Ganti Kata Sandi</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Masukkan kata sandi saat ini, lalu tentukan kata sandi yang baru.
        </p>

        <form action="ganti_sandi.php" method="POST">
            <?= inputCsrfToken() ?>
            <input type="hidden" name="aksi" value="ganti_sandi">

            <div class="form-grup">
                <label for="sandi_lama" class="label-input">Kata Sandi Saat Ini <span class="wajib">*</span></label>
                <div class="input-sandi-wrap">
                    <input type="password" id="sandi_lama" name="sandi_lama" required
                           class="kontrol-input <?= isset($galatSandi['sandi_lama']) ? 'input-ada-galat' : '' ?>">
                    <button type="button" class="tombol-intip-sandi" data-target="sandi_lama" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
                <?php if (isset($galatSandi['sandi_lama'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galatSandi['sandi_lama']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grup">
                <label for="sandi_baru" class="label-input">Kata Sandi Baru <span class="wajib">*</span></label>
                <div class="input-sandi-wrap">
                    <input type="password" id="sandi_baru" name="sandi_baru" required placeholder="Minimal 6 karakter"
                           class="kontrol-input <?= isset($galatSandi['sandi_baru']) ? 'input-ada-galat' : '' ?>">
                    <button type="button" class="tombol-intip-sandi" data-target="sandi_baru" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
                <?php if (isset($galatSandi['sandi_baru'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galatSandi['sandi_baru']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-grup">
                <label for="konfirmasi_sandi" class="label-input">Ulangi Kata Sandi Baru <span class="wajib">*</span></label>
                <div class="input-sandi-wrap">
                    <input type="password" id="konfirmasi_sandi" name="konfirmasi_sandi" required
                           class="kontrol-input <?= isset($galatSandi['konfirmasi_sandi']) ? 'input-ada-galat' : '' ?>">
                    <button type="button" class="tombol-intip-sandi" data-target="konfirmasi_sandi" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
                <?php if (isset($galatSandi['konfirmasi_sandi'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galatSandi['konfirmasi_sandi']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="tombol tombol-mint tombol-blok">💾 Simpan Kata Sandi Baru</button>
        </form>
    </div>

    <!-- Kode Pemulihan -->
    <div class="kartu-brutal kartu-kuning">
        <div class="lencana lencana-admin mb-2">KODE PEMULIHAN</div>
        <h2 style="font-size: 1.4rem; margin-bottom: 0.5rem;">
            <?= $punyaKode ? 'Buat Ulang Kode Pemulihan' : 'Anda Belum Punya Kode Pemulihan' ?>
        </h2>
        <p style="margin-bottom: 1rem;">
            <?= $punyaKode
                ? 'Kehilangan kode? Buat yang baru. Kode lama otomatis tidak berlaku.'
                : 'Buat kode pemulihan agar Anda bisa mengatur ulang kata sandi sendiri jika suatu saat lupa.' ?>
        </p>

        <form action="ganti_sandi.php" method="POST">
            <?= inputCsrfToken() ?>
            <input type="hidden" name="aksi" value="kode_baru">

            <div class="form-grup">
                <label for="sandi_lama_kode" class="label-input">Konfirmasi dengan Kata Sandi Saat Ini</label>
                <div class="input-sandi-wrap">
                    <input type="password" id="sandi_lama_kode" name="sandi_lama" required
                           class="kontrol-input <?= $galatKode !== '' ? 'input-ada-galat' : '' ?>">
                    <button type="button" class="tombol-intip-sandi" data-target="sandi_lama_kode" aria-label="Tampilkan kata sandi">👁️</button>
                </div>
                <?php if ($galatKode !== ''): ?>
                    <span class="pesan-galat-bidang"><?= e($galatKode) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="tombol tombol-aksen tombol-blok">
                🛟 <?= $punyaKode ? 'Buat Kode Baru' : 'Buat Kode Pemulihan' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>