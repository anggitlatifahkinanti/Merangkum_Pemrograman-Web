-- 1. Buat database jika belum tersedia
CREATE DATABASE IF NOT EXISTS merangkum
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE merangkum;
ALTER TABLE pengguna ADD COLUMN hash_kode_pemulihan VARCHAR(255) NULL AFTER hash_sandi;

-- 2. Bersihkan tabel lama jika ada (urutan: catatan_kuliah dulu, lalu pengguna)
DROP TABLE IF EXISTS catatan_kuliah;
DROP TABLE IF EXISTS pengguna;

-- ---------------------------------------------------------------------
-- Tabel 1: pengguna
-- Registrasi publik otomatis mendapatkan peran 'anggota'.
-- Peran 'admin' memiliki hak kelola menyeluruh.
-- ---------------------------------------------------------------------
CREATE TABLE pengguna (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(50)  NOT NULL UNIQUE,
  nama        VARCHAR(100) NOT NULL,
  peran       ENUM('admin', 'anggota') NOT NULL DEFAULT 'anggota',
  hash_sandi  VARCHAR(255) NOT NULL,
  hash_kode_pemulihan VARCHAR(255) NULL,
  dibuat_pada DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tabel 2: catatan_kuliah
-- Menyimpan rangkuman, catatan kuliah, atau panduan belajar.
-- ---------------------------------------------------------------------
CREATE TABLE catatan_kuliah (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pengguna_id      INT UNSIGNED NOT NULL,
  judul            VARCHAR(200) NOT NULL,
  mata_kuliah      VARCHAR(100) NOT NULL,
  kode_mata_kuliah VARCHAR(20)  NULL,
  topik            VARCHAR(150) NOT NULL,
  jenis_catatan    ENUM('Rangkuman', 'Catatan kuliah', 'Panduan belajar') NOT NULL,
  semester         ENUM('Ganjil', 'Genap', 'Antara') NOT NULL,
  tahun_akademik   VARCHAR(9)   NOT NULL,
  isi_ringkas      TEXT         NOT NULL,
  dibuat_pada      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_catatan_pengguna
    FOREIGN KEY (pengguna_id) REFERENCES pengguna(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indeks untuk mendukung pencarian dan filter cepat
CREATE INDEX idx_catatan_filter ON catatan_kuliah (jenis_catatan, semester);
CREATE INDEX idx_catatan_matkul ON catatan_kuliah (mata_kuliah);

-- ---------------------------------------------------------------------
-- 1. Admin   : username 'admin',    sandi 'Admin123!',   peran 'admin'
-- 2. Anggota : username 'anggota1', sandi 'Anggota123!', peran 'anggota'
-- 3. Anggota : username 'anggota2', sandi 'Anggota123!', peran 'anggota'
-- ---------------------------------------------------------------------
INSERT INTO pengguna (id, username, nama, peran, hash_sandi, dibuat_pada) VALUES
(1, 'admin', 'Administrator Akademik', 'admin', 
    '$2y$10$D2CLWZh64JRrl.7CrJwzLe9hyhN0Y.CrM7Pi1JwVMb4IPD/ICIIZC', '2026-10-01 08:00:00'),
(2, 'anggota1', 'Budi Santoso', 'anggota', 
    '$2y$10$p4QIlowfJBSDBWIIPkH1v.bSSuHSbQsHwbZ.X7.Sei49J9b08wwcG', '2026-10-02 09:15:00'),
(3, 'anggota2', 'Siti Rahmawati', 'anggota', 
    '$2y$10$p4QIlowfJBSDBWIIPkH1v.bSSuHSbQsHwbZ.X7.Sei49J9b08wwcG', '2026-10-03 10:30:00');

-- ---------------------------------------------------------------------
-- Data Awal: Catatan Kuliah
-- ---------------------------------------------------------------------
INSERT INTO catatan_kuliah 
  (pengguna_id, judul, mata_kuliah, kode_mata_kuliah, topik, jenis_catatan, semester, tahun_akademik, isi_ringkas, dibuat_pada) 
VALUES
(
  1,
  'Fondasi Basis Data Relasional & Normalisasi 1NF-3NF',
  'Basis Data',
  'IF21201',
  'Normalisasi Tabel dan Anomali Redundansi',
  'Rangkuman',
  'Ganjil',
  '2025/2026',
  'Normalisasi adalah proses menstrukturkan data relasional untuk meminimalkan redundansi dan menghindari anomali modifikasi (insert, update, delete). Bentuk 1NF mewajibkan nilai atribut atomik tanpa repeating groups. Bentuk 2NF mengharuskan setiap atribut non-kunci bergantung secara fungsional penuh pada primary key. Bentuk 3NF menghapus ketergantungan transitif antar kolom non-kunci.',
  '2026-10-05 08:30:00'
),
(
  2,
  'Arsitektur Model-View-Controller dan Prinsip Stateless Web',
  'Pemrograman Web I',
  'IF21305',
  'Pemisahan Lapisan Logika dan Tampilan',
  'Catatan kuliah',
  'Ganjil',
  '2025/2026',
  'HTTP bersifat stateless, artinya setiap permintaan berdiri sendiri tanpa mengingat konteks sebelumnya. Untuk menyimpan identitas pengguna, kita memanfaatkan Session ID di sisi server yang dikaitkan melalui Cookie HttpOnly di browser klien. Pola MVC membagi tanggung jawab aplikasi: Model mengatur data dan aturan bisnis, View merender antarmuka pengguna, dan Controller memproses masukan serta menentukan alur.',
  '2026-10-10 11:20:00'
),
(
  3,
  'Struktur Data Tree & Kompleksitas Algoritma Binary Search Tree',
  'Algoritma dan Struktur Data',
  'IF21102',
  'Binary Search Tree (BST) dan Traversal',
  'Panduan belajar',
  'Genap',
  '2025/2026',
  'Binary Search Tree (BST) memiliki properti: nilai node di sebelah kiri selalu lebih kecil dari node induk, dan nilai node di sebelah kanan selalu lebih besar. Kompleksitas pencarian rata-rata adalah O(log n), namun dapat terdegradasi menjadi O(n) jika pohon condong (skewed). Tiga metode traversal mendasar meliputi In-Order (menghasilkan urutan terurut menaik), Pre-Order (cocok untuk kloning pohon), dan Post-Order (cocok untuk penghapusan node daun).',
  '2026-10-14 14:45:00'
),
(
  2,
  'Protokol Jaringan: Model Lapisan OSI vs TCP/IP Suite',
  'Jaringan Komputer',
  'IF21402',
  'Lapisan Transport, Port Addressing, dan Handshake 3-Way',
  'Rangkuman',
  'Genap',
  '2025/2026',
  'Lapisan Transport bertanggung jawab atas komunikasi end-to-end antar proses aplikasi. TCP menyediakan layanan berorientasi koneksi yang andal dengan mekanisme 3-Way Handshake (SYN -> SYN-ACK -> ACK), kontrol aliran via sliding window, dan retransmisi paket hilang. Sebaliknya, UDP bersifat connectionless dan tanpa jaminan urutan, sangat optimal untuk streaming video dan DNS query yang memprioritaskan latensi rendah.',
  '2026-10-18 16:10:00'
),
(
  1,
  'Metodologi Rekayasa Perangkat Lunak: Agile Scrum vs Waterfall',
  'Rekayasa Perangkat Lunak',
  NULL,
  'Siklus Hidup Pengembangan Perangkat Lunak (SDLC)',
  'Panduan belajar',
  'Antara',
  '2024/2025',
  'Pendekatan Waterfall bekerja secara linier dan berurutan dari analisis kebutuhan hingga pemeliharaan, sangat cocok untuk proyek dengan spesifikasi tetap dan risiko regulasi tinggi. Sementara itu, metodologi Agile Scrum menggunakan iterasi pendek (Sprint 1-4 minggu) dengan artefak berupa Product Backlog, Sprint Backlog, dan Increment teruji. Scrum mengutamakan adaptabilitas terhadap perubahan kebutuhan pengguna melalui evaluasi berkala.',
  '2026-10-22 09:00:00'
),
(
  3,
  'Manajemen Memori Virtual dan Paging pada Sistem Operasi',
  'Sistem Operasi',
  'IF21203',
  'Virtual Memory, Page Fault, dan Algoritma Penggantian Halaman LRU',
  'Catatan kuliah',
  'Ganjil',
  '2026/2027',
  'Memori virtual memisahkan alamat logis proses dari alamat fisik RAM melalui Memory Management Unit (MMU). Konsep Paging membagi ruang memori logis menjadi blok ukuran tetap yang disebut pages, dan memori fisik menjadi frames. Jika halaman yang diminta tidak berada di RAM fisik, sistem operasi memicu Page Fault interrupt untuk mengambil halaman dari disk swap. Algoritma Least Recently Used (LRU) mengganti halaman yang paling lama tidak diakses untuk meminimalkan miss rate.',
  '2026-11-01 13:15:00'
);