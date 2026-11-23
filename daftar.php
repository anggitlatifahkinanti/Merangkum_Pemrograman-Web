<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

// Wajib Login untuk seluruh pengguna (Anggota & Admin)
wajibLogin();

$pengguna = penggunaSaatIni();
$idPengguna = $pengguna['id'];

// Ambil seluruh data catatan kuliah beserta informasi pembuatnya
$sql = 'SELECT 
            c.id,
            c.pengguna_id,
            c.judul,
            c.mata_kuliah,
            c.kode_mata_kuliah,
            c.topik,
            c.jenis_catatan,
            c.semester,
            c.tahun_akademik,
            c.isi_ringkas,
            c.dibuat_pada,
            p.nama AS nama_pembuat,
            p.username AS username_pembuat,
            p.peran AS peran_pembuat
        FROM catatan_kuliah c
        JOIN pengguna p ON c.pengguna_id = p.id
        ORDER BY c.dibuat_pada DESC';

$hasil = $db->query($sql);
$daftarCatatan = [];
$totalMilikSaya = 0;
$daftarMataKuliahUnik = [];

while ($baris = $hasil->fetch_assoc()) {
    $daftarCatatan[] = $baris;
    if ((int)$baris['pengguna_id'] === $idPengguna) {
        $totalMilikSaya++;
    }
    $daftarMataKuliahUnik[$baris['mata_kuliah']] = true;
}
$hasil->free();

$totalCatatan = count($daftarCatatan);
$totalMataKuliah = count($daftarMataKuliahUnik);

$judulHalaman = 'Daftar Rangkuman Kuliah';
require_once __DIR__ . '/kepala.php';
?>

<!-- Banner Keterangan Hak Akses Pengguna -->
<section class="banner-hak-akses">
    <div class="banner-hak-akses-teks">
        <span class="lencana <?= $pengguna['peran'] === 'admin' ? 'lencana-admin' : 'lencana-anggota' ?>">
            PERAN: <?= e(strtoupper(ambilPeranLabel($pengguna['peran']))) ?>
        </span>
        <span style="font-weight: 600; font-size: 0.95rem;">
            <?= e(ambilHakAksesKeterangan($pengguna['peran'])) ?>
        </span>
    </div>
    <div>
        <a href="tambah.php" class="tombol tombol-aksen tombol-kecil">
            ➕ Tambah Rangkuman Baru
        </a>
    </div>
</section>

<!-- Ringkasan Statistik -->
<section class="grid-statistik">
    <div class="kartu-stat kartu-biru">
        <span class="kartu-stat-angka"><?= $totalCatatan ?></span>
        <span class="kartu-stat-label">Total Rangkuman Kuliah</span>
    </div>
    <div class="kartu-stat kartu-kuning">
        <span class="kartu-stat-angka"><?= $totalMataKuliah ?></span>
        <span class="kartu-stat-label">Mata Kuliah Terdaftar</span>
    </div>
    <div class="kartu-stat kartu-mint">
        <span class="kartu-stat-angka"><?= $totalMilikSaya ?></span>
        <span class="kartu-stat-label">Catatan Buatan Anda</span>
    </div>
</section>

<!-- Bilah Pencarian dan Filter Live (Tanpa Reload Halaman) -->
<section class="bilah-filter">
    <div class="d-flex justify-between align-center flex-wrap gap-1 mb-2">
        <h2 style="font-size: 1.25rem; margin: 0;">Eksplorasi Bank Materi</h2>
        <span id="info-jumlah-tampil" class="lencana lencana-kuning">
            Menampilkan <?= $totalCatatan ?> dari <?= $totalCatatan ?> rangkuman
        </span>
    </div>

    <div class="grid-filter">
        <!-- Pencarian Kata Kunci -->
        <div class="form-grup" style="margin-bottom: 0;">
            <label for="input-pencarian" class="label-input">Cari Cepat (Judul, Matkul, Topik, Kode)</label>
            <input 
                type="search" 
                id="input-pencarian" 
                class="kontrol-input" 
                placeholder="Ketik kata kunci pencarian..." 
                autocomplete="off"
            >
        </div>

        <!-- Filter Jenis Catatan -->
        <div class="form-grup" style="margin-bottom: 0;">
            <label for="filter-jenis" class="label-input">Jenis Catatan</label>
            <select id="filter-jenis" class="kontrol-select">
                <option value="">Semua Jenis</option>
                <?php foreach (DAFTAR_JENIS_CATATAN as $j): ?>
                    <option value="<?= e($j) ?>"><?= e($j) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Filter Semester -->
        <div class="form-grup" style="margin-bottom: 0;">
            <label for="filter-semester" class="label-input">Semester</label>
            <select id="filter-semester" class="kontrol-select">
                <option value="">Semua Semester</option>
                <?php foreach (DAFTAR_SEMESTER as $s): ?>
                    <option value="<?= e($s) ?>"><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tombol Reset Filter -->
        <button type="button" id="tombol-reset-filter" class="tombol tombol-kecil" title="Kembalikan pencarian dan filter ke awal">
            🔄 Reset
        </button>
    </div>
</section>

<!-- Pesan Jika Tidak Ada Hasil Filter -->
<div id="pesan-kosong-filter" class="kotak-kosong" style="display: none; margin-bottom: 1.5rem;">
    <div class="kotak-kosong-ikon">🔍</div>
    <h2>Tidak Ada Rangkuman yang Cocok</h2>
    <p style="color: var(--teks-muted);">
        Tidak ditemukan catatan kuliah yang sesuai dengan kata kunci atau kombinasi filter Anda.
    </p>
    <button type="button" onclick="document.getElementById('tombol-reset-filter').click();" class="tombol tombol-aksen tombol-kecil">
        Bersihkan Filter Pencarian
    </button>
</div>

<!-- Grid Kartu Rangkuman Kuliah -->
<?php if (empty($daftarCatatan)): ?>
    <div class="kotak-kosong">
        <div class="kotak-kosong-ikon">📚</div>
        <h2>Bank Catatan Masih Kosong</h2>
        <p style="color: var(--teks-muted); max-width: 500px; margin: 0 auto 1.5rem;">
            Belum ada rangkuman kuliah yang tersimpan. Mulai bagikan catatan pertama Anda untuk mempermudah belajar bersama!
        </p>
        <a href="tambah.php" class="tombol tombol-mint">
            ➕ Buat Catatan Pertama
        </a>
    </div>
<?php else: ?>
    <div id="wadah-kartu-rangkuman" class="grid-rangkuman">
        <?php foreach ($daftarCatatan as $c): ?>
            <?php 
                $bisaKelola = bisaKelolaCatatan((int)$c['pengguna_id']);
                $adalahMilikSaya = ((int)$c['pengguna_id'] === $idPengguna);

                // Warna lencana jenis catatan
                $warnaJenis = 'lencana-biru';
                if ($c['jenis_catatan'] === 'Catatan kuliah') $warnaJenis = 'lencana-kuning';
                if ($c['jenis_catatan'] === 'Panduan belajar') $warnaJenis = 'lencana-lavender';
            ?>
            <article 
                class="kartu-brutal kartu-interaktif kartu-rangkuman kartu-item-rangkuman"
                data-judul="<?= e($c['judul']) ?>"
                data-matkul="<?= e($c['mata_kuliah']) ?>"
                data-kode="<?= e($c['kode_mata_kuliah'] ?? '') ?>"
                data-topik="<?= e($c['topik']) ?>"
                data-jenis="<?= e($c['jenis_catatan']) ?>"
                data-semester="<?= e($c['semester']) ?>"
            >
                <div>
                    <!-- Header Kartu: Lencana Jenis & Semester/TAP -->
                    <div class="kartu-rangkuman-header">
                        <span class="lencana <?= $warnaJenis ?>">
                            <?= e($c['jenis_catatan']) ?>
                        </span>
                        <span class="lencana lencana-mint" title="Semester & Tahun Akademik">
                            <?= e($c['semester']) ?> &bull; <?= e($c['tahun_akademik']) ?>
                        </span>
                    </div>

                    <!-- Judul Catatan -->
                    <h3 class="kartu-rangkuman-judul">
                        <a href="detail.php?id=<?= (int)$c['id'] ?>">
                            <?= e($c['judul']) ?>
                        </a>
                    </h3>

                    <!-- Mata Kuliah & Kode -->
                    <div class="kartu-rangkuman-matkul">
                        <span>📖 <?= e($c['mata_kuliah']) ?></span>
                        <?php if (!empty($c['kode_mata_kuliah'])): ?>
                            <span class="lencana" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;">
                                <?= e($c['kode_mata_kuliah']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Topik / Bab -->
                    <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.6rem; color: var(--teks-muted);">
                        📌 Bab: <?= e($c['topik']) ?>
                    </div>

                    <!-- Cuplikan Isi -->
                    <p class="kartu-rangkuman-cuplikan">
                        <?= e($c['isi_ringkas']) ?>
                    </p>
                </div>

                <!-- Bagian Bawah: Informasi Pembuat & Tombol Aksi -->
                <div>
                    <div class="kartu-rangkuman-meta">
                        <div class="d-flex justify-between align-center">
                            <span>
                                ✍️ <strong><?= e($c['nama_pembuat']) ?></strong>
                                <?php if ($adalahMilikSaya): ?>
                                    <span class="lencana lencana-mint" style="font-size: 0.65rem; padding: 0.1rem 0.35rem;">Anda</span>
                                <?php endif; ?>
                            </span>
                            <span style="font-size: 0.75rem;">
                                <?= date('d M Y, H:i', strtotime($c['dibuat_pada'])) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Aksi (Detail, Edit, Hapus) -->
                    <div class="kartu-rangkuman-aksi">
                        <a href="detail.php?id=<?= (int)$c['id'] ?>" class="tombol tombol-kecil tombol-utama">
                            👁️ Detail
                        </a>

                        <?php if ($bisaKelola): ?>
                            <a href="edit.php?id=<?= (int)$c['id'] ?>" class="tombol tombol-kecil tombol-mint" title="Edit Rangkuman">
                                ✏️ Edit
                            </a>
                            <button 
                                type="button" 
                                class="tombol tombol-kecil tombol-bahaya pemicu-modal-hapus"
                                data-id="<?= (int)$c['id'] ?>"
                                data-judul="<?= e($c['judul']) ?>"
                                title="Hapus Rangkuman"
                            >
                                🗑️ Hapus
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/kaki.php'; ?>
