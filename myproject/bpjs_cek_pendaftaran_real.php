<?php
require_once 'vendor/autoload.php';

use LZCompressor\LZString;

date_default_timezone_set('UTC');

// Konfigurasi
$cons_id     = '22020';
$secretKey   = '3aLBB8C8D8';
$user_key    = '1cae203f209aa3d28db949c8a3806069'; 
$tanggal     = '2025-07-18'; 

// Generate timestamp dan signature
$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secretKey, true));

// Buat URL endpoint
$url = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/pendaftaran/tanggal/$tanggal";

// Set header
$headers = [
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key"
];

// CURL request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Curl error: " . curl_error($ch);
    exit;
}

curl_close($ch);

// Decode JSON respons
$result = json_decode($response, true);

if (!isset($result['response'])) {
    echo "Gagal mengambil data atau data kosong:\n";
    print_r($result);
    exit;
}

// Lakukan dekripsi
function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
}

// Gabungkan key untuk dekripsi
$key = $cons_id . $secretKey . $timestamp;

// Dekripsi dan decompress
$decrypted = stringDecrypt($key, $result['response']);
$original = LZString::decompressFromEncodedURIComponent($decrypted);

if ($original === null) {
    echo "⚠️ Tidak bisa decompress. Mungkin data tidak dikompresi atau key salah.\n";
    exit;
}

// Tampilkan hasil JSON
header('Content-Type: application/json');
echo $original;

?>
