<?php
require_once __DIR__ . '/../function/function_klinik.php'; // koneksi & helper DB
require_once __DIR__ . '/../function/bpjs_config.php'; // koneksi & helper DB

header('Content-Type: application/json'); // pastikan output JSON
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "metadata" => ["code" => 405, "message" => "Metode request harus POST"]
    ]);
    exit;
}

$no_rawat = $_POST['no_rawat'] ?? '';
if (!$no_rawat) {
    echo json_encode([
        "metadata" => ["code" => 400, "message" => "no_rawat kosong"]
    ]);
    exit;
}

// Ambil data pasien
$sql = "SELECT rp.no_rawat, rp.tgl_registrasi, mpp.kd_poli_pcare as kd_poli,
               ps.no_peserta
        FROM reg_periksa rp
        INNER JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
        INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli = mpp.kd_poli_rs
        WHERE rp.no_rawat = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $no_rawat);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo json_encode([
        "metadata" => ["code" => 404, "message" => "Data pasien tidak ditemukan"]
    ]);
    exit;
}

// Timestamp milidetik
$tgl_periksa = $data['tgl_registrasi'];
$jam_panggil = date('H:i:s'); 
$dt = new DateTime($tgl_periksa . ' ' . $jam_panggil, new DateTimeZone('Asia/Jakarta'));
$timestamp_ms = $dt->getTimestamp() * 1000;

// Payload panggil BPJS
$payload = [
    "tanggalperiksa" => $data['tgl_registrasi'],
    "kodepoli"       => $data['kd_poli'],
    "nomorkartu"     => $data['no_peserta'],
    "status"         => 1,
    "waktu"          => $timestamp_ms
    
];

echo '<pre>';
print_r($payload);
echo '</pre>';


// BPJS API
// $cons_id    = '13216';
// $secret_key = '3nG5007800';
// $user_key   = '907eacdff6474399dafd7c60d4b13c0a';
// $url_panggil = "https://apijkn-dev.bpjs-kesehatan.go.id/antreanfktp_dev/antrean/panggil";

// date_default_timezone_set('UTC');
// $timestamp = time();
// $signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
// $auth      = "Basic ".base64_encode("0030B011:Stmartina30#:095");
// var_dump($user_key_antrol);
// exit();
$headers = [
  
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key_antrol"
];

echo '<pre>';
print_r($headers);
echo '</pre>';

$ch = curl_init($url_panggil);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);

if(curl_errno($ch)){
    echo json_encode([
        "metadata" => ["code" => 500, "message" => "Curl error: ".curl_error($ch)]
    ]);
} else {
    // kembalikan response BPJS langsung (JSON)
    echo $response;

}

curl_close($ch);
