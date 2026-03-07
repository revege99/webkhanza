<?php
require_once 'vendor/autoload.php';

// Data dari BPJS
$cons_id    = "25685";
$secret_key = "9hX4AEEB8C";
$user_key   = "a0e225428271c8e127fc2c539ff0192f";


date_default_timezone_set('UTC');
$timestamp = time();

$auth = "Basic ".base64_encode("tester.stmartina:Bpjs123**:095");
$data = $cons_id . "&" . $timestamp;
$signature = base64_encode(hash_hmac('sha256', $data, $secret_key, true));


$is_browser = isset($_SERVER['HTTP_USER_AGENT']);
$line_break = $is_browser ? "<br>" : PHP_EOL;

echo "========== BPJS PCare Header ==========" . $line_break;
echo "Content-Type: text/plain" . $line_break;
echo "X-cons-id: " . $cons_id . $line_break;
echo "X-timestamp: " . $timestamp . $line_break;
echo "X-signature: " . $signature . $line_break;
echo "X-authorization: " .$auth .$line_break;
echo "user_key: " . $user_key . $line_break;
echo "=======================================" . $line_break;



use LZCompressor\LZString;


function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16); // IV 16 byte
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
}


function decompress($string) {
    return LZString::decompressFromEncodedURIComponent($string);
}


$consid    = "25685";         
$conspwd   = "9hX4AEEB8C";                  
$timestamp = "1754635204";       

// Gabungkan jadi key
$key = $cons_id . $secret_key . $timestamp;


$encrypted = '
FeUtX8u9RLcM0L1G5U8AG+8F8XkCuzf1gihJcuIIDUdJEGHq27fAXeNgTHJ8bS6WXXHs0EncAhWS7B87vZ5SZffiTjj16KQ3JIJvmUQEbsVYqJ7dmqaacfV0ByEyO081cLOC+hxt+FhjLhZ9UmCGgSmIp5v8PHcGry+xR5G3yXx1pQjj7ZFxDPVgDp7R74psKYx2fDnfKIbzVH+CRITBcKupDPfJHe/dpbiYImgxJTicAu9ElVu8S3MoOvJCxoxU4boWUaAr4341XcOTMxiZJrveGNkXllE2QPnq3cH1nNL2CdZBLU5n9F6Om9llkbnZHdLxnMdK7OdJ2msHCU4S0BzUodQTAojyvq+5LxUDLzX3zHW18UrELostTP8x364H+0sJ0hhdL+5FeV316SDCXKEqc04ecVE0ha6akb2jG/ukVuPD7piQscB3F/wYQhs0jOIsw01UsgLHAxhbiHZJxQuVwsWUVBba8LP60f/Pj+mr+SCSnd5mvqFNjykD+1AfsqLa1z0O0b3y/ULxX5craU0akAs6Yko+4wNaeyTkKRKnwdTvnBmsdx5oItnP6BNMHdS7A8Mpu/EqhNrGbU3MaFelG+NBWxGk8utna55RxtUblu2KhUwaLbyW/nMJxDkNrCKiki3fvFxd3IWqoDgyorV8V1N7q90vKi/Rl1skHOkZYDBpDCDhOCy/N0jdw1QZbDMlPujc9hDDvo2/Xxm2SIsDiCUhbq15axrLQuM9tvEiKnxrWRnTNwtbbyR1Mcg4XW1Vbu6+oaI2RuBmsWWsmzDp3gGyvyzfgcG4cUzw4dHHLMa6WaHOQKo509MSnekoobYckUgVvwWxPro8MCOH/9Jrnd6pDziEZ3alSv/fu4l3889Q0//w83mQGDgz0rGf
';


$decrypted = stringDecrypt($key, $encrypted);

if ($decrypted === false) {
    echo " Gagal dekripsi. Periksa key, timestamp, atau data.\n";
    exit;
}



$original = decompress($decrypted);
if ($original === null) {
    echo "⚠️ Tidak dapat decompress. Mungkin data tidak dikompresi.\n";
} else {
    // echo "📄 Hasil decompress:\n";
    // echo "---------------------------\n";
    echo $original . "\n";
}
