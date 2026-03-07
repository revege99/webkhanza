<?php

header('Content-Type: application/json');

require_once '../myproject/vendor/autoload.php';
require_once '../function/bpjs_config.php';

use LZCompressor\LZString;

$jenis  = 'noka';
$nomor  = $_GET['nomor']  ?? '';

if (!$jenis || !$nomor) {
    echo json_encode(["status"=>false,"message"=>"Parameter tidak lengkap"]);
    exit;
}

if (!in_array($jenis, ['nik','noka'])) {
    echo json_encode(["status"=>false,"message"=>"Jenis kartu tidak valid"]);
    exit;
}

date_default_timezone_set('UTC');
$timestamp = time();

$data = $cons_id . "&" . $timestamp;
$signature = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
$authorization = "Basic " . base64_encode("$auth_user:$auth_pass:$kd_aplikasi");

$url = $base_url . "/peserta/$jenis/$nomor";

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "X-authorization: $authorization",
        "user_key: $user_key"
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(["status"=>false,"message"=>curl_error($ch)]);
    exit;
}

curl_close($ch);

if ($httpCode != 200) {
    echo json_encode(["status"=>false,"message"=>"HTTP Error $httpCode"]);
    exit;
}

$responseData = json_decode($response, true);

if ($responseData['metaData']['code'] != 200) {
    echo json_encode([
        "status"=>false,
        "message"=>$responseData['metaData']['message']
    ]);
    exit;
}

function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
}

function decompress($string) {
    return LZString::decompressFromEncodedURIComponent($string);
}

$key = $cons_id . $secret_key . $timestamp;
$decrypted = stringDecrypt($key, $responseData['response']);

if ($decrypted === false) {
    echo json_encode(["status"=>false,"message"=>"Gagal decrypt"]);
    exit;
}

$original = decompress($decrypted);

if ($original === null) {
    echo json_encode(["status"=>false,"message"=>"Gagal decompress"]);
    exit;
}

echo json_encode([
    "status" => true,
    "data"   => json_decode($original, true)
]);