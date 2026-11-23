<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

wajibLogin();

$pengguna = penggunaSaatIni();
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id) {
    http_response_code(400);
    $judulHalaman = 'Parameter Tidak Valid';
    require_once __DIR__ . '/kepala.php';
    ?>
    <div class="wadah-sempit">
        <div class="kartu-brutal kartu-bahaya text-center">
            <div class="lencana lencana-bahaya mb-2">GALAT PARAMETER</div>
            <h1>ID Catatan Tidak Valid</h1>
            <p>ID catatan yang hendak diubah tidak valid.</p>
            <a href="daftar.php" class="tombol tombol-utama mt-2">⬅️ Kembali ke Daftar</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/kaki.php';
    exit;
}

// 1. Ambil data catatan yang akan diedit dengan Prepared Statement
$stmt = $db->prepare('SELECT * FROM catatan_kuliah WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$hasil = $stmt->get_result();
$catatan = $hasil->fetch_assoc();
$stmt->close();

if (!$catatan) {
    http_response_code(404);
    $judulHalaman = 'Catatan Tidak Ditemukan';
    require_once __DIR__ . '/kepala.php';
    ?>
    <div class="wadah-sempit">
        <div class="kartu-brutal kartu-kuning text-center">
            <div class="lencana lencana-bahaya mb-2">TIDAK DITEMUKAN — 404</div>
            <h1>Catatan Tidak Ditemukan</h1>
            <p>Catatan yang hendak Anda edit tidak ditemukan dalam sistem.</p>
            <a href="daftar.php" class="tombol tombol-utama mt-2">⬅️ Kembali ke Daftar</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/kaki.php';
    exit;
}

// 2. VERIFIKASI HAK AKSES SERVER SECARA KETAT
if (!bisaKelolaCatatan((int)$catatan['pengguna_id'])) {
    tampilkanAksesDitolak('Anda tidak memiliki hak akses untuk mengedit rangkuman ini. Rangkuman ini dibuat oleh mahasiswa lain.');
}

$galat = [];
$masukan = [
    'judul'            => $catatan['judul'],
    'mata_kuliah'      => $catatan['mata_kuliah'],
    'kode_mata_kuliah' => $catatan['kode_mata_kuliah'] ?? '',
    'topik'            => $catatan['topik'],
    'jenis_catatan'    => $catatan['jenis_catatan'],
    'semester'         => $catatan['semester'],
    'tahun_akademik'   => $catatan['tahun_akademik'],
    'isi_ringkas'      => $catatan['isi_ringkas'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    $masukan['judul']            = ambilPost('judul');
    $masukan['mata_kuliah']      = ambilPost('mata_kuliah');
    $masukan['kode_mata_kuliah'] = ambilPost('kode_mata_kuliah');
    $masukan['topik']            = ambilPost('topik');
    $masukan['jenis_catatan']    = ambilPost('jenis_catatan');
    $masukan['semester']         = ambilPost('semester');
    $masukan['tahun_akademik']   = ambilPost('tahun_akademik');
    $masukan['isi_ringkas']      = ambilPost('isi_ringkas');

    // Validasi Sisi Server
    if ($error = validasiTeksWajib($masukan['judul'], 'Judul rangkuman', 3, 200)) {
        $galat['judul'] = $error;
    }

    if ($error = validasiTeksWajib($masukan['mata_kuliah'], 'Mata kuliah', 3, 100)) {
        $galat['mata_kuliah'] = $error;
    }

    if ($error = validasiKodeMataKuliah($masukan['kode_mata_kuliah'])) {
        $galat['kode_mata_kuliah'] = $error;
    }

    if ($error = validasiTeksWajib($masukan['topik'], 'Topik atau bab', 3, 150)) {
        $galat['topik'] = $error;
    }

    if ($error = validasiPilihan($masukan['jenis_catatan'], 'Jenis catatan', DAFTAR_JENIS_CATATAN)) {
        $galat['jenis_catatan'] = $error;
    }

    if ($error = validasiPilihan($masukan['semester'], 'Semester', DAFTAR_SEMESTER)) {
        $galat['semester'] = $error;
    }

    if ($error = validasiTahunAkademik($masukan['tahun_akademik'])) {
        $galat['tahun_akademik'] = $error;
    }

    if ($error = validasiIsiRangkuman($masukan['isi_ringkas'])) {
        $galat['isi_ringkas'] = $error;
    }

    // Jika seluruh isian valid, lakukan pembaruan data
    if (empty($galat)) {
        $kodeMatkul = $masukan['kode_mata_kuliah'] !== '' ? $masukan['kode_mata_kuliah'] : null;

        $stmtUpdate = $db->prepare('UPDATE catatan_kuliah SET 
            judul = ?, 
            mata_kuliah = ?, 
            kode_mata_kuliah = ?, 
            topik = ?, 
            jenis_catatan = ?, 
            semester = ?, 
            tahun_akademik = ?, 
            isi_ringkas = ? 
            WHERE id = ?');

        $stmtUpdate->bind_param(
            'ssssssssi',
            $masukan['judul'],
            $masukan['mata_kuliah'],
            $kodeMatkul,
            $masukan['topik'],
            $masukan['jenis_catatan'],
            $masukan['semester'],
            $masukan['tahun_akademik'],
            $masukan['isi_ringkas'],
            $id
        );

        $stmtUpdate->execute();
        $stmtUpdate->close();

        setPesan("Rangkuman '{$masukan['judul']}' berhasil diperbarui!", 'sukses');
        header('Location: detail.php?id=' . $id);
        exit;
    }
}

$judulHalaman = 'Edit Rangkuman';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah" style="max-width: 800px;">
    <div class="d-flex justify-between align-center mb-2">
        <a href="detail.php?id=<?= $id ?>" class="tombol tombol-kecil">
            ⬅️ Batal & Kembali ke Detail
        </a>
        <span class="lencana <?= $pengguna['peran'] === 'admin' ? 'lencana-admin' : 'lencana-mint' ?>">
            MODIFIKASI: <?= e(strtoupper(ambilPeranLabel($pengguna['peran']))) ?>
        </span>
    </div>

    <div class="kartu-brutal">
        <div class="lencana lencana-mint mb-2">PENGUBAHAN DATA</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Edit Rangkuman Kuliah</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Perbarui data catatan kuliah di bawah ini. Semua perubahan akan divalidasi ulang secara ketat oleh server.
        </p>

        <?php if (!empty($galat)): ?>
            <div class="pesan-kilat pesan-bahaya mb-2" role="alert">
                <span>⚠️ Ada beberapa kolom yang tidak valid. Mohon periksa pesan kesalahan di bawah setiap kolom.</span>
            </div>
        <?php endif; ?>

        <form id="form-rangkuman" action="edit.php?id=<?= $id ?>" method="POST">
            <?= inputCsrfToken() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- Judul Catatan -->
            <div class="form-grup">
                <label for="judul" class="label-input">
                    Judul Rangkuman <span class="wajib">*</span>
                </label>
                <input 
                    type="text" 
                    id="judul" 
                    name="judul" 
                    class="kontrol-input <?= isset($galat['judul']) ? 'input-ada-galat' : '' ?>" 
                    value="<?= e($masukan['judul']) ?>" 
                    required 
                >
                <?php if (isset($galat['judul'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['judul']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Mata Kuliah & Kode Mata Kuliah -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-grup">
                    <label for="mata_kuliah" class="label-input">
                        Mata Kuliah <span class="wajib">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="mata_kuliah" 
                        name="mata_kuliah" 
                        class="kontrol-input <?= isset($galat['mata_kuliah']) ? 'input-ada-galat' : '' ?>" 
                        value="<?= e($masukan['mata_kuliah']) ?>" 
                        required 
                    >
                    <?php if (isset($galat['mata_kuliah'])): ?>
                        <span class="pesan-galat-bidang"><?= e($galat['mata_kuliah']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-grup">
                    <label for="kode_mata_kuliah" class="label-input">
                        Kode Matkul <small style="font-weight: normal; color: var(--teks-muted);">(Opsional)</small>
                    </label>
                    <input 
                        type="text" 
                        id="kode_mata_kuliah" 
                        name="kode_mata_kuliah" 
                        class="kontrol-input <?= isset($galat['kode_mata_kuliah']) ? 'input-ada-galat' : '' ?>" 
                        value="<?= e($masukan['kode_mata_kuliah']) ?>" 
                    >
                    <?php if (isset($galat['kode_mata_kuliah'])): ?>
                        <span class="pesan-galat-bidang"><?= e($galat['kode_mata_kuliah']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Topik atau Bab -->
            <div class="form-grup">
                <label for="topik" class="label-input">
                    Topik atau Bab <span class="wajib">*</span>
                </label>
                <input 
                    type="text" 
                    id="topik" 
                    name="topik" 
                    class="kontrol-input <?= isset($galat['topik']) ? 'input-ada-galat' : '' ?>" 
                    value="<?= e($masukan['topik']) ?>" 
                    required 
                >
                <?php if (isset($galat['topik'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['topik']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Jenis Catatan, Semester, Tahun Akademik -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-grup">
                    <label for="jenis_catatan" class="label-input">
                        Jenis Rangkuman <span class="wajib">*</span>
                    </label>
                    <select id="jenis_catatan" name="jenis_catatan" class="kontrol-select <?= isset($galat['jenis_catatan']) ? 'input-ada-galat' : '' ?>" required>
                        <?php foreach (DAFTAR_JENIS_CATATAN as $j): ?>
                            <option value="<?= e($j) ?>" <?= $masukan['jenis_catatan'] === $j ? 'selected' : '' ?>>
                                <?= e($j) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($galat['jenis_catatan'])): ?>
                        <span class="pesan-galat-bidang"><?= e($galat['jenis_catatan']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-grup">
                    <label for="semester" class="label-input">
                        Semester <span class="wajib">*</span>
                    </label>
                    <select id="semester" name="semester" class="kontrol-select <?= isset($galat['semester']) ? 'input-ada-galat' : '' ?>" required>
                        <?php foreach (DAFTAR_SEMESTER as $s): ?>
                            <option value="<?= e($s) ?>" <?= $masukan['semester'] === $s ? 'selected' : '' ?>>
                                <?= e($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($galat['semester'])): ?>
                        <span class="pesan-galat-bidang"><?= e($galat['semester']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-grup">
                    <label for="tahun_akademik" class="label-input">
                        Tahun Akademik <span class="wajib">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="tahun_akademik" 
                        name="tahun_akademik" 
                        class="kontrol-input <?= isset($galat['tahun_akademik']) ? 'input-ada-galat' : '' ?>" 
                        value="<?= e($masukan['tahun_akademik']) ?>" 
                        required 
                    >
                    <div class="hint-input">Format YYYY/YYYY (contoh: 2025/2026)</div>
                    <span id="galat-klien-tahun" class="pesan-galat-bidang" style="display: none;"></span>
                    <?php if (isset($galat['tahun_akademik'])): ?>
                        <span class="pesan-galat-bidang"><?= e($galat['tahun_akademik']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Isi Rangkuman -->
            <div class="form-grup">
                <label for="isi_ringkas" class="label-input">
                    Isi Rangkuman / Catatan Materi <span class="wajib">*</span>
                </label>
                <textarea 
                    id="isi_ringkas" 
                    name="isi_ringkas" 
                    class="kontrol-textarea <?= isset($galat['isi_ringkas']) ? 'input-ada-galat' : '' ?>" 
                    required 
                ><?= e($masukan['isi_ringkas']) ?></textarea>

                <div class="penghitung-karakter" id="counter-isi">
                    0 / 2000 karakter (minimal 50)
                </div>

                <?php if (isset($galat['isi_ringkas'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['isi_ringkas']) ?></span>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-between align-center flex-wrap gap-2 mt-3">
                <a href="detail.php?id=<?= $id ?>" class="tombol">
                    ✕ Batal
                </a>
                <button type="submit" class="tombol tombol-mint" style="font-size: 1.05rem;">
                    💾 Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>