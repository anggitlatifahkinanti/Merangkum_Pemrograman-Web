# Merangkum — Bank Catatan & Rangkuman Kuliah Mahasiswa

Aplikasi web bank catatan dan rangkuman kuliah berbasis **PHP 8+ Native**, **MySQL/MariaDB**, **HTML5**, **CSS3 (Neo-Brutalism)**, dan **JavaScript Vanilla** tanpa ketergantungan framework apa pun (murni tanpa Laravel, CodeIgniter, React, Vue, Bootstrap, maupun Tailwind).

Aplikasi dibangun untuk berjalan di lingkungan lokal melalui **XAMPP (Apache & MariaDB)** dengan kontrol hak akses ketat di server, keamanan autentikasi modern, dan antarmuka responsif yang nyaman digunakan pada perangkat desktop maupun ponsel pintar.

---

## 🎨 Arah Desain & Identitas Visual (Neo-Brutalism)

Antarmuka dirancang dengan tema visual **Neo-Brutalism** yang ceria, berani, rapi, dan mudah dibaca oleh mahasiswa:

| Unsur Desain | Penerapan dalam Aplikasi |
|:---|:---|
| **Warna Dominan** | Soft Blue (`#93c5fd` / `#bfdbfe`) untuk latar belakang dan aksen utama |
| **Aksen Pendukung** | Kuning Cerah (`#fde047`), Pink Pastel (`#f472b6`), Lavender (`#c4b5fd`), dan Mint (`#6ee7b7`) |
| **Garis Tepi (Border)** | Garis hitam tebal solid (`3px solid #0f172a` / `#38bdf8` pada mode gelap) |
| **Bayangan (Shadow)** | Bayangan jatuh tegas tanpa blur (*hard offset shadow* `4px-5px 0px`) |
| **Sudut (Radius)** | Kotak tegas dengan sudut sedikit membulat (`border-radius: 4px - 6px`) |
| **Tipografi** | *Space Grotesk* (judul/heading) dan *Plus Jakarta Sans* (isi/body teks) |
| **Mode Tampilan** | Pilihan Tema Terang & Tema Gelap yang preferensinya tersimpan di cookie browser (`tema_merangkum`) |

---

## 🔐 Matriks Hak Akses Pengguna (Server-Side RBAC)

Kontrol hak akses diimplementasikan secara tegas di sisi server PHP menggunakan data sesi, bukan sekadar manipulasi tampilan browser:

| Peran (Role) | Akses Dashboard & Detail | Membuat Catatan Baru | Mengedit Catatan | Menghapus Catatan |
|:---|:---:|:---:|:---|:---|
| **Admin** | ✅ Boleh Semua | ✅ Boleh | ✅ **Semua catatan** (milik sendiri & milik seluruh anggota) | ✅ **Semua catatan** (milik sendiri & milik seluruh anggota) |
| **Anggota** | ✅ Boleh Semua | ✅ Boleh | 🔒 **Hanya catatan miliknya sendiri** | 🔒 **Hanya catatan miliknya sendiri** |
| **Tamu (Publik)** | ❌ Wajib Login | ❌ Wajib Login | ❌ Ditolak (HTTP 403 / Redirect Login) | ❌ Ditolak (HTTP 403 / Redirect Login) |

### Mekanisme Proteksi Hak Akses di Server:
1. **Perekaman Pembuat Berbasis Sesi**: Pada proses penambahan data (`tambah.php`), nilai kolom `pengguna_id` diambil langsung dari `$_SESSION['pengguna_id']`. Form tidak menyediakan input maupun *hidden field* untuk ID pembuat, mencegah manipulasi identitas dari sisi klien.
2. **Validasi Kepemilikan pada Endpoint Mutasi**: Pada `edit.php` dan `hapus.php`, fungsi `bisaKelolaCatatan($pemilikId)` dipanggil sebelum menampilkan formulir maupun mengeksekusi query. Jika pengguna aktif adalah `anggota` dan mencoba memodifikasi ID catatan milik mahasiswa lain, server langsung memutus alur dengan pesan **Akses Ditolak (HTTP 403 Forbidden)**.
3. **Pendaftaran Terbatas**: Endpoint publik `registrasi.php` mengunci kolom `peran` menjadi `'anggota'` secara permanen pada query PHP. Akun `admin` hanya dapat diatur secara langsung melalui basis data.

---

## 🛡️ Standar Keamanan & Proteksi Celah Web

| Aspek Keamanan | Implementasi Teknis | Mitigasi Risiko |
|:---|:---|:---|
| **SQL Injection** | Menggunakan MySQLi Prepared Statements (`prepare`, `bind_param`, `execute`) pada seluruh query berparameter masukan | Mencegah injeksi kode SQL ilegal |
| **Cross-Site Scripting (XSS)** | Seluruh variabel dinamis diescape menggunakan fungsi pembantu `e()` berbasis `htmlspecialchars(..., ENT_QUOTES \| ENT_SUBSTITUTE, 'UTF-8')` | Mencegah eksekusi script jahat di browser |
| **Cross-Site Request Forgery (CSRF)** | Token unik kriptografis 64-karakter (`bin2hex(random_bytes(32))`) divalidasi dengan `hash_equals()` pada setiap form POST (`tambah.php`, `edit.php`, `hapus.php`, `logout.php`) | Mencegah pengiriman aksi palsu dari situs luar |
| **Session Fixation & Hijacking** | Menjalankan `session_regenerate_id(true)` saat login sukses, cookie dengan flag `HttpOnly`, `SameSite = Lax`, dan `session.use_strict_mode = 1` | Mencegah pencurian dan fiksasi sesi pengguna |
| **Kebocoran Kredensial & SQL** | Kesalahan basis data ditangkap dengan `try-catch`, detail dicatat ke berkas `error_log`, dan pengguna hanya disajikan pesan kesalahan generik yang ramah | Mencegah pembocoran struktur tabel & kredensial |
| **Penghapusan Ilegal via GET** | Endpoint `hapus.php` menolak mutasi melalui metode GET dan mewajibkan POST dengan konfirmasi modal | Menghindari penghapusan data akibat web-crawler / klik tautan |

---

## 📋 Aturan & Validasi Data Rangkuman

| Bidang Data | Aturan Validasi Sisi Server | Penanganan Sisi Klien |
|:---|:---|:---|
| **Judul** | Wajib diisi, panjang 3 hingga 200 karakter | Atribut `required`, autofocus |
| **Mata Kuliah** | Wajib diisi, panjang 3 hingga 100 karakter | Atribut `required` |
| **Kode Mata Kuliah** | Opsional (boleh kosong), maksimal 20 karakter alfanumerik / strip | Placeholder panduan format |
| **Topik / Bab** | Wajib diisi, panjang 3 hingga 150 karakter | Atribut `required` |
| **Jenis Catatan** | Wajib, validasi whitelist: `Rangkuman`, `Catatan kuliah`, `Panduan belajar` | Dropdown `<select>` dengan pilihan tetap |
| **Semester** | Wajib, validasi whitelist: `Ganjil`, `Genap`, `Antara` | Dropdown `<select>` dengan pilihan tetap |
| **Tahun Akademik** | Format harus `YYYY/YYYY` dan tahun kedua wajib tepat satu tahun setelah tahun pertama (misal: `2025/2026`) | Dihitung otomatis berbasis tanggal hari ini, dilengkapi validator interaktif |
| **Isi Rangkuman** | Wajib diisi, panjang antara **50 hingga 2000 karakter** (dihitung multibyte `mb_strlen`) | Penghitung karakter (*live counter*) visual (berubah hijau jika valid, merah jika < 50 atau > 2000) |
| **Identitas Pembuat** | Diambil dari `$_SESSION['pengguna_id']` | Ditampilkan sebagai label statis informatif |
| **Waktu Pembuatan** | Dikelola otomatis oleh MySQL via `CURRENT_TIMESTAMP` | Ditampilkan dengan format tanggal lokal Indonesia |

---

## 🚀 Panduan Instalasi di XAMPP (Lokal)

### 1. Kebutuhan Perangkat Lunak
- **Web Server**: Apache (tersedia bawaan di XAMPP).
- **PHP**: Versi 8.0, 8.1, 8.2, atau yang lebih baru dengan ekstensi `mysqli` aktif.
- **Basis Data**: MariaDB / MySQL versi 10.4+ / 8.0+.

### 2. Penempatan Direktori Proyek
Salin folder proyek `merangkum` ke dalam direktori publik web server XAMPP:
```text
C:\xampp\htdocs\merangkum
```

### 3. Konfigurasi dan Impor Basis Data
1. Jalankan aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL** hingga indikator berwarna hijau.
3. Buka peramban web dan masuk ke phpMyAdmin: `http://localhost/phpmyadmin/`.
4. Klik tab **Import** pada menu atas phpMyAdmin.
5. Klik **Choose File** / **Pilih Berkas**, pilih berkas `app.sql` yang berada di dalam folder proyek:
   ```text
   C:\xampp\htdocs\merangkum\app.sql
   ```
6. Gulir ke bawah dan klik tombol **Import** / **Kirim**.
7. Basis data `merangkum` beserta seluruh struktur tabel dan data demo awal akan otomatis terbentuk.

> [!NOTE]
> Pengaturan koneksi bawaan pada `koneksi.php` adalah Host: `localhost`, Pengguna: `root`, Sandi: `(kosong)`, Basis Data: `merangkum`, Port: `3306`. Jika MySQL Anda menggunakan kata sandi khusus, sesuaikan variabel di dalam file `koneksi.php`.

### 4. Mengakses Aplikasi Web
Buka peramban web (Chrome, Edge, Firefox) dan akses alamat:
```text
http://localhost/merangkum/
```

---

## 🔑 Kredensial Akun Pengujian (Demo)

Setelah berkas `app.sql` diimpor, tersedia 3 akun pengujian siap pakai:

| Peran (Role) | Username | Kata Sandi | Deskripsi Pengujian |
|:---|:---|:---|:---|
| **Administrator** | `admin` | `Admin123!` | Memiliki wewenang penuh membaca, menambah, mengedit, dan menghapus catatan siapa saja. |
| **Anggota (Mhs 1)** | `anggota1` | `Anggota123!` | Akun mahasiswa pertama, dapat menambah catatan dan mengedit catatan miliknya sendiri. |
| **Anggota (Mhs 2)** | `anggota2` | `Anggota123!` | Akun mahasiswa kedua, digunakan untuk menguji isolasi hak akses (membuktikan bahwa Anggota 2 tidak dapat mengedit/menghapus catatan Anggota 1). |

### Cara Mengubah atau Menambah Akun:
- **Pendaftaran Mandiri**: Klik menu **Daftar Akun** (`registrasi.php`) pada antarmuka aplikasi untuk membuat akun Anggota baru.
- **Mengubah Sandi Manual**: Buat hash baru dengan skrip PHP `password_hash('SandiBaru', PASSWORD_DEFAULT)` dan perbarui nilai kolom `hash_sandi` pada tabel `pengguna` di phpMyAdmin.

---

## 📁 Struktur Berkas & Pembagian Modul

Semua file dikelompokkan dan dipisahkan berdasarkan fungsi tanpa mencampuradukkan CSS atau JavaScript ke dalam dokumen PHP:

| Nama Berkas / Direktori | Jenis | Peran dan Tanggung Jawab |
|:---|:---:|:---|
| `app.sql` | SQL | Skema basis data relasional (`pengguna`, `catatan_kuliah`), foreign key cascade, indeks pencarian, dan data seed demo |
| `koneksi.php` | Backend PHP | Koneksi database MySQLi, mode strict error exception, konfigurasi UTF-8 MB4, dan penanganan galat aman |
| `sesi.php` | Backend PHP | Pengaturan parameter cookie sesi (HttpOnly/SameSite), fungsi pengecekan peran (RBAC), token CSRF, flash message, dan halaman 403 |
| `validasi.php` | Backend PHP | Fungsi validasi masukan form, sanitasi teks, logika tahun akademik YYYY/YYYY, daftar putih (whitelist), dan escaping XSS `e()` |
| `kepala.php` | Template PHP | Bagian pembuka HTML, metadata, tautan font Google, navigasi utama, badge peran pengguna aktif, tombol tema, dan flash alert |
| `kaki.php` | Template PHP | Bagian penutup HTML, hak cipta, wadah modal konfirmasi hapus data, dan pemanggilan skrip `js/app.js` |
| `index.php` | Halaman PHP | Halaman pendaratan (*landing page*) pengenalan aplikasi dengan pengalihan otomatis ke `daftar.php` jika sudah login |
| `login.php` | Halaman PHP | Formulir autentikasi pengguna dengan regenerasi ID sesi dan verifikasi hash sandi bcrypt |
| `registrasi.php` | Halaman PHP | Formulir pendaftaran mahasiswa baru dengan peran otomatis terkunci sebagai `'anggota'` |
| `logout.php` | Endpoint PHP | Proses pembersihan sesi dan penghancuran cookie sesi yang diamankan via POST + token CSRF |
| `daftar.php` | Halaman PHP | Dashboard utama: ringkasan statistik data, pencarian langsung, filter multi-kriteria tanpa reload, dan kartu rangkuman |
| `detail.php` | Halaman PHP | Tampilan lengkap satu rangkuman: metadata komprehensif, informasi pembuat, waktu pembuatan, dan teks isi lengkap |
| `tambah.php` | Halaman PHP | Formulir penambahan rangkuman dengan sticky form, validasi server, dan perekaman `pengguna_id` otomatis dari sesi |
| `edit.php` | Halaman PHP | Formulir pengubahan data rangkuman dengan verifikasi hak kepemilikan di server (HTTP 403 jika tidak berwenang) |
| `hapus.php` | Endpoint PHP | Titik akhir eksekusi penghapusan data via metode POST + CSRF dengan verifikasi otorisasi server |
| `css/gaya.css` | Lembar Gaya | Desain murni CSS3 bertema Neo-Brutalism, variabel tema terang dan tema gelap, tipografi responsif, dan efek tombol interaktif |
| `js/app.js` | Skrip Klien | JavaScript Vanilla: filter & live search DOM tanpa reload, alih tema terang/gelap via cookie, penghitung karakter, dan kontrol modal hapus |
| `README.md` | Dokumentasi | Panduan komprehensif arsitektur sistem, aturan keamanan, instruksi instalasi, dan spesifikasi pengujian |

---

## 🧪 Rekapitulasi Pengujian Sistem (Automated End-to-End Test)

Pengujian fungsionalitas dan otorisasi telah dijalankan secara komprehensif melalui protokol HTTP lokal dengan hasil kelolosan **100% (17 dari 17 skenario lulus)**:

| No | Skenario Pengujian | Target Endpoint | Hasil Pengujian | Status |
|:---:|:---|:---|:---|:---:|
| 1 | Aksesibilitas halaman login publik | `GET /login.php` | HTTP 200 OK | ✅ Lolos |
| 2 | Pembentukan token keamanan CSRF | `GET /login.php` | Token 64-karakter valid terbentuk di sesi | ✅ Lolos |
| 3 | Autentikasi akun Administrator | `POST /login.php` | Verifikasi hash berhasil, alihkan ke `daftar.php` (HTTP 302) | ✅ Lolos |
| 4 | Autentikasi akun Anggota | `POST /login.php` | Verifikasi hash berhasil, alihkan ke `daftar.php` (HTTP 302) | ✅ Lolos |
| 5 | Proteksi akses dashboard tanpa login | `GET /daftar.php` | Pengguna dialihkan ke halaman login (HTTP 302) | ✅ Lolos |
| 6 | Pembuatan rangkuman baru oleh Anggota | `POST /tambah.php` | Berhasil disimpan dengan `pengguna_id` dari server (HTTP 302) | ✅ Lolos |
| 7 | Integritas ID catatan baru yang tersimpan | `GET /detail.php` | ID catatan tersimpan secara valid pada basis data | ✅ Lolos |
| 8 | Anggota memperbarui catatan miliknya sendiri | `POST /edit.php` | Pembaruan data berhasil dieksekusi (HTTP 302) | ✅ Lolos |
| 9 | Autentikasi akun Anggota 2 | `POST /login.php` | Berhasil login sebagai pengguna berbeda (HTTP 302) | ✅ Lolos |
| 10 | Anggota 2 mencoba mengedit catatan Anggota 1 | `POST /edit.php` | **Ditolak oleh server dengan status HTTP 403 Forbidden** | ✅ Lolos |
| 11 | Anggota 2 mencoba menghapus catatan Anggota 1 | `POST /hapus.php` | **Ditolak oleh server dengan status HTTP 403 Forbidden** | ✅ Lolos |
| 12 | Admin mengedit catatan milik Anggota 1 | `POST /edit.php` | **Dizinkan oleh server dan berhasil diubah (HTTP 302)** | ✅ Lolos |
| 13 | Admin menghapus catatan milik Anggota 1 | `POST /hapus.php` | **Diizinkan oleh server dan berhasil dihapus (HTTP 302)** | ✅ Lolos |
| 14 | Pengiriman request POST tanpa token CSRF | `POST /hapus.php` | Permintaan langsung ditolak (HTTP 403 Forbidden) | ✅ Lolos |
| 15 | Validasi server terhadap format tahun akademik salah | `POST /tambah.php` | Nilai `2025/2027` ditolak, form kembali menampilkan galat | ✅ Lolos |
| 16 | Validasi server batas minimum panjang isi rangkuman | `POST /tambah.php` | Teks di bawah 50 karakter ditolak oleh server | ✅ Lolos |
| 17 | Pendaftaran mandiri akun baru publik | `POST /registrasi.php` | Akun baru berhasil dibuat dan otomatis terkunci sebagai Anggota | ✅ Lolos |
