<?php
/*sesi.php : Pengelolaan Sesi Pengguna, Autentikasi, Otorisasi, dan Token CSRF Aplikasi "Merangkum"*/

declare(strict_types=1);

// Konfigurasi parameter cookie sesi sebelum session_start
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,              // Berakhir saat peramban ditutup
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,       // True jika berjalan pada koneksi HTTPS
        'httponly' => true,           // Mencegah akses cookie sesi melalui JavaScript (anti XSS)
        'samesite' => 'Lax',          // Mitigasi serangan CSRF antar-situs
    ]);

    session_start();
}

/**
 * Memeriksa apakah ada pengguna yang sedang login aktif.
 */
function apakahLogin(): bool
{
    return !empty($_SESSION['pengguna_id']) && is_numeric($_SESSION['pengguna_id']);
}

/**
 * Mengambil data profil ringkas pengguna yang sedang login.
 */
function penggunaSaatIni(): ?array
{
    if (!apakahLogin()) {
        return null;
    }
    return [
        'id'       => (int)$_SESSION['pengguna_id'],
        'username' => (string)($_SESSION['pengguna_username'] ?? ''),
        'nama'     => (string)($_SESSION['pengguna_nama'] ?? ''),
        'peran'    => (string)($_SESSION['pengguna_peran'] ?? 'anggota'),
    ];
}

/**
 * Memeriksa apakah pengguna yang sedang login memiliki peran Admin.
 */
function apakahAdmin(): bool
{
    return apakahLogin() && ($_SESSION['pengguna_peran'] ?? '') === 'admin';
}

/**
 * Menjaga halaman agar hanya dapat diakses oleh pengguna yang sudah login.
 * Mengalihkan ke login.php jika belum login.
 */
function wajibLogin(): void
{
    if (!apakahLogin()) {
        setPesan('Silakan masuk terlebih dahulu untuk mengakses fitur ini.', 'peringatan');
        header('Location: login.php');
        exit;
    }
}

/**
 * Memeriksa hak akses untuk mengedit atau menghapus suatu rangkuman.
 * Aturan:
 * - Admin dapat mengedit dan menghapus rangkuman siapa saja.
 * - Anggota hanya dapat mengedit dan menghapus rangkuman miliknya sendiri.
 */
function bisaKelolaCatatan(int $pemilikCatatanId): bool
{
    if (!apakahLogin()) {
        return false;
    }
    if (apakahAdmin()) {
        return true;
    }
    return (int)$_SESSION['pengguna_id'] === $pemilikCatatanId;
}

/**
 * Mengembalikan label peran yang mudah dibaca.
 */
function ambilPeranLabel(string $peran): string
{
    return ($peran === 'admin') ? 'Administrator' : 'Anggota';
}

/**
 * Mengembalikan keterangan hak akses berdasarkan peran.
 */
function ambilHakAksesKeterangan(string $peran): string
{
    if ($peran === 'admin') {
        return 'Hak Akses Admin: Anda memiliki wewenang penuh untuk membaca, menambah, mengedit, dan menghapus seluruh rangkuman.';
    }
    return 'Hak Akses Anggota: Anda dapat membaca seluruh rangkuman, membuat rangkuman baru, serta mengedit & menghapus rangkuman buatan Anda sendiri.';
}

/**
 * Mengatur pesan kilat (flash message) dalam sesi.
 */
function setPesan(string $teks, string $tipe = 'sukses'): void
{
    $_SESSION['pesan_flash'] = [
        'teks' => $teks,
        'tipe' => $tipe, // sukses, bahaya, peringatan, info
    ];
}

/**
 * Mengambil dan menghapus pesan kilat dari sesi.
 */
function ambilPesan(): ?array
{
    if (isset($_SESSION['pesan_flash'])) {
        $pesan = $_SESSION['pesan_flash'];
        unset($_SESSION['pesan_flash']);
        return $pesan;
    }
    return null;
}

/**
 * Mengambil atau membuat token CSRF sesi.
 */
function ambilCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Menghasilkan elemen tag input hidden HTML untuk token CSRF.
 */
function inputCsrfToken(): string
{
    $token = htmlspecialchars(ambilCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Memvalidasi token CSRF yang dikirim melalui formulir POST atau header.
 */
function validasiCsrfToken(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($sessionToken) || empty($token)) {
        return false;
    }
    return hash_equals($sessionToken, (string)$token);
}

/**
 * Menolak permintaan dengan kesalahan CSRF (HTTP 403).
 */
function tolakCsrf(): void
{
    tampilkanAksesDitolak('Token keamanan (CSRF) tidak valid atau sesi Anda telah kedaluwarsa. Silakan muat ulang halaman dan coba lagi.');
}

/**
 * Menampilkan halaman resmi Akses Ditolak (HTTP 403 Forbidden).
 */
function tampilkanAksesDitolak(string $pesan = 'Anda tidak memiliki hak akses untuk melakukan tindakan ini.'): void
{
    http_response_code(403);
    $judulHalaman = 'Akses Ditolak (403)';
    require_once __DIR__ . '/kepala.php';
    ?>
    <section class="wadah-konten">
        <div class="kartu-brutal kartu-peringatan text-center">
            <div class="lencana lencana-bahaya mb-2">AKSES DITOLAK — 403 FORBIDDEN</div>
            <h1 class="judul-halaman" style="font-size: 2rem;">Tindakan Tidak Diizinkan</h1>
            <p class="teks-lead" style="max-width: 600px; margin: 1rem auto;">
                <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <div class="d-flex justify-center gap-2 mt-3">
                <a href="daftar.php" class="tombol tombol-utama">Kembali ke Daftar Rangkuman</a>
                <?php if (!apakahLogin()): ?>
                    <a href="login.php" class="tombol tombol-aksen">Masuk Akun</a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/kaki.php';
    exit;
}

/**
 * Mengambil preferensi tema aktif dari cookie (terang atau gelap).
 */
function ambilTemaAktif(): string
{
    $tema = $_COOKIE['tema_merangkum'] ?? 'terang';
    return in_array($tema, ['terang', 'gelap'], true) ? $tema : 'terang';
}

/**
 * Membuat kode pemulihan acak, format XXXX-XXXX-XXXX (tanpa huruf/angka yang mirip: I, O, 0, 1).
 */
function buatKodePemulihan(): string
{
    $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 32 karakter
    $kode = '';
    for ($i = 0; $i < 12; $i++) {
        $kode .= $abjad[random_int(0, 31)];
    }
    return implode('-', str_split($kode, 4));
}

/**
 * Menyeragamkan kode masukan pengguna (huruf besar, tanpa tanda hubung/spasi).
 */
function normalisasiKodePemulihan(string $kode): string
{
    return preg_replace('/[^A-Z0-9]/', '', strtoupper($kode)) ?? '';
}