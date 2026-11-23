<?php

declare(strict_types=1);

// Daftar Putih (Whitelist) untuk Nilai ENUM Basis Data
const DAFTAR_JENIS_CATATAN = [
    'Rangkuman',
    'Catatan kuliah',
    'Panduan belajar',
];

const DAFTAR_SEMESTER = [
    'Ganjil',
    'Genap',
    'Antara',
];

const DAFTAR_PERAN = [
    'admin',
    'anggota',
];

/**
 * Fungsi pembantu untuk mencegah Cross-Site Scripting (XSS).
 * Meng-escape string saat dirender ke dokumen HTML.
 */
function e(?string $nilai): string
{
    if ($nilai === null) {
        return '';
    }
    return htmlspecialchars($nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Mengambil nilai masukan dari $_POST dengan pemangkasan spasi ekstra.
 */
function ambilPost(string $kunci, string $default = ''): string
{
    if (!isset($_POST[$kunci])) {
        return $default;
    }
    return trim((string)$_POST[$kunci]);
}

/**
 * Mengambil nilai dari $_GET dengan pemangkasan spasi ekstra.
 */
function ambilGet(string $kunci, string $default = ''): string
{
    if (!isset($_GET[$kunci])) {
        return $default;
    }
    return trim((string)$_GET[$kunci]);
}

/**
 * Menghasilkan Tahun Akademik Otomatis berbasis tanggal saat ini.
 * Format: YYYY/YYYY.
 * Jika bulan saat ini Juli (7) ke atas, masuk tahun ajaran baru (tahunIni / tahunDepan).
 * Jika bulan Januari - Juni, masuk tahun ajaran berjalan (tahunLalu / tahunIni).
 */
function buatTahunAkademikOtomatis(): string
{
    $tahun = (int)date('Y');
    $bulan = (int)date('n');

    if ($bulan >= 7) {
        return sprintf('%d/%d', $tahun, $tahun + 1);
    }
    return sprintf('%d/%d', $tahun - 1, $tahun);
}

/**
 * Menghasilkan daftar opsi Tahun Akademik yang umum untuk kemudahan pengguna di dropdown.
 */
function daftarOpsiTahunAkademik(): array
{
    $tahunSekarang = (int)date('Y');
    $daftar = [];
    for ($i = -2; $i <= 2; $i++) {
        $awal = $tahunSekarang + $i;
        $akhir = $awal + 1;
        $daftar[] = "$awal/$akhir";
    }
    return $daftar;
}

/**
 * Memvalidasi format dan logika tahun akademik (YYYY/YYYY).
 * Syarat: Tahun kedua tepat satu tahun setelah tahun pertama.
 */
function validasiTahunAkademik(string $tap): ?string
{
    $tap = trim($tap);
    if ($tap === '') {
        return 'Tahun akademik wajib diisi.';
    }

    if (!preg_match('/^(\d{4})\/(\d{4})$/', $tap, $cocok)) {
        return 'Format tahun akademik harus YYYY/YYYY (contoh: 2025/2026).';
    }

    $tahunAwal = (int)$cocok[1];
    $tahunAkhir = (int)$cocok[2];

    if ($tahunAkhir !== $tahunAwal + 1) {
        return "Tahun kedua ($tahunAkhir) harus tepat satu tahun setelah tahun pertama ($tahunAwal). Contoh valid: {$tahunAwal}/" . ($tahunAwal + 1) . '.';
    }

    if ($tahunAwal < 2000 || $tahunAwal > 2099) {
        return 'Rentang tahun akademik harus berada di antara tahun 2000 hingga 2099.';
    }

    return null;
}

/**
 * Memvalidasi teks wajib dengan batasan panjang minimum dan maksimum.
 */
function validasiTeksWajib(string $nilai, string $namaKolom, int $min, int $max): ?string
{
    $panjang = mb_strlen(trim($nilai), 'UTF-8');
    if ($panjang === 0) {
        return "Kolom {$namaKolom} wajib diisi.";
    }
    if ($panjang < $min) {
        return "{$namaKolom} terlalu pendek (minimal {$min} karakter).";
    }
    if ($panjang > $max) {
        return "{$namaKolom} terlalu panjang (maksimal {$max} karakter).";
    }
    return null;
}

/**
 * Memvalidasi kode mata kuliah (opsional, maks 20 karakter alphanumeric/strip).
 */
function validasiKodeMataKuliah(?string $kode): ?string
{
    if ($kode === null || trim($kode) === '') {
        return null; // Opsional
    }
    $kode = trim($kode);
    if (mb_strlen($kode, 'UTF-8') > 20) {
        return 'Kode mata kuliah tidak boleh lebih dari 20 karakter.';
    }
    if (!preg_match('/^[a-zA-Z0-9\-\.\s]+$/', $kode)) {
        return 'Kode mata kuliah hanya boleh berisi huruf, angka, tanda hubung (-), atau titik.';
    }
    return null;
}

/**
 * Memvalidasi masukan pilihan terhadap daftar putih (whitelist).
 */
function validasiPilihan(string $nilai, string $namaKolom, array $whitelist): ?string
{
    if ($nilai === '' || !in_array($nilai, $whitelist, true)) {
        return "Pilihan {$namaKolom} tidak valid.";
    }
    return null;
}

/**
 * Memvalidasi isi rangkuman (wajib 50 hingga 2000 karakter).
 */
function validasiIsiRangkuman(string $isi): ?string
{
    $panjang = mb_strlen(trim($isi), 'UTF-8');
    if ($panjang === 0) {
        return 'Isi rangkuman wajib diisi.';
    }
    if ($panjang < 50) {
        return "Isi rangkuman terlalu pendek (saat ini {$panjang} karakter, minimal 50 karakter).";
    }
    if ($panjang > 2000) {
        return "Isi rangkuman melebihi batas (saat ini {$panjang} karakter, maksimal 2000 karakter).";
    }
    return null;
}

/**
 * Memvalidasi username untuk pendaftaran dan login.
 */
function validasiUsername(string $username): ?string
{
    $panjang = strlen($username);
    if ($panjang < 3 || $panjang > 30) {
        return 'Nama pengguna (username) harus terdiri dari 3 hingga 30 karakter.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return 'Nama pengguna hanya boleh berisi huruf, angka, dan garis bawah (_).';
    }
    return null;
}

/**
 * Memvalidasi kata sandi.
 */
function validasiKataSandi(string $sandi): ?string
{
    if (strlen($sandi) < 6) {
        return 'Kata sandi minimal harus terdiri dari 6 karakter.';
    }
    return null;
}