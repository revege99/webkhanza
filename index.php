<?php
session_start();

/**
 * ============================
 * KONEKSI & FUNCTION GLOBAL
 * ============================
 */
require_once __DIR__ . '/function/function_klinik.php';

/**
 * ============================
 * ROUTER SEDERHANA
 * ============================
 */

$page = isset($_GET['page']) ? basename($_GET['page']) : 'home';

/**
 * ============================
 * FOLDER YANG DIIZINKAN
 * ============================
 */
$folders = [
    'views',
    'bridging',
    'service',
    'auth'
];

/**
 * ============================
 * WHITELIST HALAMAN
 * ============================
 */
$allowed_pages = [

    'views' => [
        'home',
        'about'
    ],

    'bridging' => [
        'post_obat',
        'proses_post_obat',
        'post_mcu',
        'proses_post_mcu',
        'data_kunjungan',
        'data_mcu',
        'data_obat',
        'proses_del_obat',
        'put_rujukan',
        'proses_put_rujukan',
        'get_obat_bynokunjungan',
        'data_obat_pcare_lokal',
        'hapus_obat_lokal',
        'proses_del_mcu',
        'proses_update_mcu'
    ],

    'service' => [
        'antrean',
        'kirim_antrean',
        'function_kirim_antrean',
        'function_batal_antrean',
        'function_panggil_antrean',
        'function_pasien_tidakhadir'
    ],

    'auth' => [
        'login',
        'proses_login',
        'logout'
    ],
];

/**
 * ============================
 * RESOLUSI FILE
 * ============================
 */
$file_found     = false;
$file_path      = '';
$current_folder = '';

foreach ($folders as $folder) {

    if (
        isset($allowed_pages[$folder]) &&
        in_array($page, $allowed_pages[$folder])
    ) {

        $path = "$folder/$page.php";

        if (is_readable($path)) {
            $file_found     = true;
            $file_path      = $path;
            $current_folder = $folder;
            break;
        }
    }
}

/**
 * ============================
 * AUTH GUARD
 * ============================
 */

/* halaman yang bebas login */
$public_pages = [
    'login',
    'proses_login',
    'proses_del_obat',   // bridging yang diizinkan tanpa login
    'function_pasien_tidakhadir'
];

/* folder yang bebas login */
$public_folders = [
    'service'
];

if (
    !isset($_SESSION['login']) &&
    !in_array($page, $public_pages) &&
    !in_array($current_folder, $public_folders)
) {

    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

    header("Location: index.php?page=login");
    exit;
}

/**
 * ============================
 * HALAMAN TANPA SIDEBAR
 * ============================
 */
$no_sidebar_pages = [
    'login',
    'proses_login',
    'logout',
    'proses_post_mcu',
    'proses_post_obat',
    'proses_del_mcu',
    'proses_update_mcu',
    'function_panggil_antrean'
];

/**
 * ============================
 * RENDER HALAMAN
 * ============================
 */

if ($file_found) {

    if (
        isset($_SESSION['login']) &&
        !in_array($page, $no_sidebar_pages)
    ) {
        include 'views/sidebar.php';
    }

    include $file_path;

} else {

    http_response_code(404);
    echo "<h2 style='text-align:center;color:red'>404 - Page Not Found</h2>";

}