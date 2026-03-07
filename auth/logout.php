<?php
// session sudah aktif dari index.php

// Hapus semua variabel session
$_SESSION = [];

// Hapus session di server
session_destroy();

// Hapus cookie session (opsional tapi rapi)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect ke login
header("Location: index.php?page=login");
exit;
