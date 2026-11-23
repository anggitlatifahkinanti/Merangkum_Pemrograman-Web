<?php

declare(strict_types=1);

// Konfigurasi koneksi database (dapat ditimpa via environment variables)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbName = getenv('DB_NAME') ?: 'merangkum';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);

// Aktifkan pelaporan error MySQLi sebagai Exception internal
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_init();
    if (!$db) {
        throw new RuntimeException('Gagal menginisialisasi MySQLi.');
    }

    // Set batas waktu timeout koneksi
    $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

    // Buka koneksi ke MySQL/MariaDB
    $db->real_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

    // Pastikan charset utf8mb4 aktif untuk mendukung karakter multibyte dan emoji
    $db->set_charset('utf8mb4');

} catch (Throwable $e) {
    // Catat rincian kesalahan teknis ke berkas error log server
    error_log(sprintf(
        '[Merangkum DB Error] %s di %s:%d (Host: %s, DB: %s)',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $dbHost,
        $dbName
    ));

    // Jangan tampilkan detail database/kredensial ke pengguna publik demi keamanan
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kesalahan Koneksi Basis Data — Merangkum</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background-color: #fce7f3;
                color: #0f172a;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 1rem;
            }
            .kotak {
                background: #ffffff;
                border: 4px solid #0f172a;
                box-shadow: 8px 8px 0px #0f172a;
                padding: 2rem;
                max-width: 520px;
                width: 100%;
            }
            h1 {
                font-size: 1.5rem;
                margin-top: 0;
                color: #e11d48;
            }
            p { line-height: 1.6; }
            .badge {
                display: inline-block;
                background: #fde047;
                border: 2px solid #0f172a;
                padding: 0.25rem 0.5rem;
                font-weight: bold;
                font-size: 0.85rem;
                margin-bottom: 1rem;
            }
            code {
                background: #e2e8f0;
                padding: 0.2rem 0.4rem;
                font-size: 0.9em;
                border: 1px solid #0f172a;
            }
        </style>
    </head>
    <body>
        <div class="kotak">
            <div class="badge">SISTEM BASIS DATA</div>
            <h1>Gagal Terhubung ke Basis Data</h1>
            <p>Aplikasi <strong>Merangkum</strong> tidak dapat terhubung ke server MySQL/MariaDB lokal.</p>
            <p>Silakan pastikan beberapa hal berikut:</p>
            <ol>
                <li>Layanan <strong>MySQL</strong> pada XAMPP Control Panel sudah dalam status <em>Running</em>.</li>
                <li>Basis data bernama <code>merangkum</code> telah dibuat atau skrip <code>app.sql</code> sudah diimpor.</li>
                <li>Kredensial pengguna MySQL pada berkas <code>koneksi.php</code> sesuai.</li>
            </ol>
            <p style="font-size: 0.85rem; color: #64748b; margin-top: 1.5rem;">
                *Detail galat teknis telah dicatat secara aman pada log server.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}