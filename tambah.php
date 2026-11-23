<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

wajibLogin();

$pengguna = penggunaSaatIni();
$galat = [];

// Nilai default awal formulir
$masukan = [
    'judul'            => '',
    'mata_kuliah'      => '',
    'kode_mata_kuliah' => '',
    'topik'            => '',
    'jenis_catatan'    => 'Rangkuman',
    'semester'         => 'Ganjil',
    'tahun_akademik'   => buatTahunAkademikOtomatis(),
    'isi_ringkas'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    // Ambil data dari formulir
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

    // Jika seluruh isian lolos validasi, simpan ke database
    if (empty($galat)) {
        $penggunaIdServer = $pengguna['id']; // DIJAMIN dari session server!
        $kodeMatkul = $masukan['kode_mata_kuliah'] !== '' ? $masukan['kode_mata_kuliah'] : null;

        $stmt = $db->prepare('INSERT INTO catatan_kuliah 
            (pengguna_id, judul, mata_kuliah, kode_mata_kuliah, topik, jenis_catatan, semester, tahun_akademik, isi_ringkas) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $stmt->bind_param(
            'issssssss',
            $penggunaIdServer,
            $masukan['judul'],
            $masukan['mata_kuliah'],
            $kodeMatkul,
            $masukan['topik'],
            $masukan['jenis_catatan'],
            $masukan['semester'],
            $masukan['tahun_akademik'],
            $masukan['isi_ringkas']
        );

        $stmt->execute();
        $idBaru = (int)$stmt->insert_id;
        $stmt->close();

        setPesan("Rangkuman '{$masukan['judul']}' berhasil disimpan!", 'sukses');
        header('Location: detail.php?id=' . $idBaru);
        exit;
    }
}

$judulHalaman = 'Tambah Rangkuman Baru';
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah" style="max-width: 800px;">
    <div class="d-flex justify-between align-center mb-2">
        <a href="daftar.php" class="tombol tombol-kecil">
            ⬅️ Batal & Kembali
        </a>
        <span class="lencana lencana-mint">
            PEMBUAT: <?= e($pengguna['nama']) ?> (<?= e(ambilPeranLabel($pengguna['peran'])) ?>)
        </span>
    </div>

    <div class="kartu-brutal">
        <div class="lencana lencana-kuning mb-2">FORMULIR CATATAN KULIAH</div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Buat Rangkuman Baru</h1>
        <p style="color: var(--teks-muted); margin-bottom: 1.5rem;">
            Isi formulir berikut untuk mendokumentasikan pemahaman materi kuliah Anda.
        </p>

        <?php if (!empty($galat)): ?>
            <div class="pesan-kilat pesan-bahaya mb-2" role="alert">
                <span>⚠️ Terdapat beberapa kolom yang belum valid. Mohon periksa pesan kesalahan di bawah setiap kolom.</span>
            </div>
        <?php endif; ?>

        <form id="form-rangkuman" action="tambah.php" method="POST">
            <?= inputCsrfToken() ?>

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
                    placeholder="Contoh: Pemodelan Data Relasional dan Normalisasi Bentuk Ketiga"
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
                        placeholder="Contoh: Basis Data Lanjut"
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
                        placeholder="Contoh: IF21201"
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
                    placeholder="Contoh: Bab 4 - Normalisasi dan Ketergantungan Fungsional"
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
                        placeholder="YYYY/YYYY"
                    >
                    <div class="hint-input">Format YYYY/YYYY (misal: <?= e(buatTahunAkademikOtomatis()) ?>)</div>
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
                    placeholder="Tuliskan intisari kuliah, konsep utama, rumus, definisi penting, atau rangkuman materi di sini (antara 50 hingga 2000 karakter)..."
                ><?= e($masukan['isi_ringkas']) ?></textarea>

                <div class="penghitung-karakter" id="counter-isi">
                    0 / 2000 karakter (minimal 50)
                </div>

                <?php if (isset($galat['isi_ringkas'])): ?>
                    <span class="pesan-galat-bidang"><?= e($galat['isi_ringkas']) ?></span>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-between align-center flex-wrap gap-2 mt-3">
                <a href="daftar.php" class="tombol">
                    ✕ Batalkan
                </a>
                <button type="submit" class="tombol tombol-mint" style="font-size: 1.05rem;">
                    💾 Simpan Rangkuman
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>