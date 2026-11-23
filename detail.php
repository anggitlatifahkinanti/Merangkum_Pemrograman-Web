<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/validasi.php';

wajibLogin();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$id) {
    http_response_code(400);
    $judulHalaman = 'Permintaan Tidak Valid';
    require_once __DIR__ . '/kepala.php';
    ?>
    <div class="wadah-sempit">
        <div class="kartu-brutal kartu-bahaya text-center">
            <div class="lencana lencana-bahaya mb-2">GALAT PARAMETER</div>
            <h1>ID Catatan Tidak Valid</h1>
            <p>ID catatan kuliah yang Anda minta tidak memenuhi spesifikasi numerik yang valid.</p>
            <a href="daftar.php" class="tombol tombol-utama mt-2">⬅️ Kembali ke Daftar Rangkuman</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/kaki.php';
    exit;
}

// Ambil catatan menggunakan Prepared Statement
$stmt = $db->prepare('SELECT 
                        c.*,
                        p.nama AS nama_pembuat,
                        p.username AS username_pembuat,
                        p.peran AS peran_pembuat
                      FROM catatan_kuliah c
                      JOIN pengguna p ON c.pengguna_id = p.id
                      WHERE c.id = ? LIMIT 1');
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
            <p>Rangkuman kuliah yang Anda cari tidak tersedia atau mungkin telah dihapus.</p>
            <a href="daftar.php" class="tombol tombol-utama mt-2">⬅️ Kembali ke Daftar Rangkuman</a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/kaki.php';
    exit;
}

$bisaKelola = bisaKelolaCatatan((int)$catatan['pengguna_id']);
$judulHalaman = $catatan['judul'];
require_once __DIR__ . '/kepala.php';
?>

<div class="wadah" style="max-width: 900px;">
    <!-- Tombol Navigasi Kembali & Aksi Cepat -->
    <div class="d-flex justify-between align-center flex-wrap gap-1 mb-2">
        <a href="daftar.php" class="tombol tombol-kecil">
            ⬅️ Kembali ke Daftar
        </a>

        <?php if ($bisaKelola): ?>
            <div class="d-flex gap-1">
                <a href="edit.php?id=<?= (int)$catatan['id'] ?>" class="tombol tombol-kecil tombol-mint">
                    ✏️ Edit Rangkuman Ini
                </a>
                <button 
                    type="button" 
                    class="tombol tombol-kecil tombol-bahaya pemicu-modal-hapus"
                    data-id="<?= (int)$catatan['id'] ?>"
                    data-judul="<?= e($catatan['judul']) ?>"
                >
                    🗑️ Hapus
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kartu Utama Detail Rangkuman -->
    <article class="kartu-brutal">
        <header class="detail-header">
            <div class="d-flex align-center gap-1 flex-wrap mb-2">
                <span class="lencana lencana-biru">
                    <?= e($catatan['jenis_catatan']) ?>
                </span>
                <span class="lencana lencana-mint">
                    Semester <?= e($catatan['semester']) ?>
                </span>
                <span class="lencana lencana-kuning">
                    T.A. <?= e($catatan['tahun_akademik']) ?>
                </span>
                <?php if (!empty($catatan['kode_mata_kuliah'])): ?>
                    <span class="lencana lencana-lavender">
                        Kode: <?= e($catatan['kode_mata_kuliah']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem; line-height: 1.25;">
                <?= e($catatan['judul']) ?>
            </h1>

            <div style="font-size: 1.1rem; font-weight: 700; color: var(--biru-cerah);">
                📖 Mata Kuliah: <?= e($catatan['mata_kuliah']) ?>
            </div>
            <div style="font-size: 1rem; font-weight: 600; color: var(--teks-muted); margin-top: 0.25rem;">
                📌 Topik / Bab: <?= e($catatan['topik']) ?>
            </div>
        </header>

        <!-- Informasi Penulis & Tanggal -->
        <section class="detail-meta-grid">
            <div>
                <span class="detail-meta-item-label">Penulis Catatan</span>
                <div class="detail-meta-item-isi d-flex align-center gap-1 mt-1">
                    <span>👤 <?= e($catatan['nama_pembuat']) ?></span>
                    <span class="lencana <?= $catatan['peran_pembuat'] === 'admin' ? 'lencana-admin' : 'lencana-anggota' ?>" style="font-size: 0.65rem;">
                        <?= e(ambilPeranLabel($catatan['peran_pembuat'])) ?>
                    </span>
                </div>
            </div>

            <div>
                <span class="detail-meta-item-label">Waktu Dibuat</span>
                <div class="detail-meta-item-isi mt-1">
                    📅 <?= date('d F Y, H:i', strtotime($catatan['dibuat_pada'])) ?> WIB
                </div>
            </div>

            <div>
                <span class="detail-meta-item-label">Terakhir Diperbarui</span>
                <div class="detail-meta-item-isi mt-1">
                    🔄 <?= date('d F Y, H:i', strtotime($catatan['diperbarui_pada'])) ?> WIB
                </div>
            </div>
        </section>

        <!-- Isi Catatan Kuliah Lengkap -->
        <section class="mt-3">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.75rem;">Isi Rangkuman Materi</h2>
            <div class="detail-isi-teks">
<?= e($catatan['isi_ringkas']) ?>
            </div>
        </section>
    </article>
</div>

<?php require_once __DIR__ . '/kaki.php'; ?>