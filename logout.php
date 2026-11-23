<?php

declare(strict_types=1);

require_once __DIR__ . '/sesi.php';
require_once __DIR__ . '/validasi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validasiCsrfToken()) {
        tolakCsrf();
    }

    // Kosongkan array sesi
    $_SESSION = [];

    // Hapus cookie sesi dari peramban bila ada
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    session_start();
    setPesan('Anda telah berhasil keluar dari sistem.', 'info');
    header('Location: login.php');
    exit;
}

header('Location: daftar.php');
exit;