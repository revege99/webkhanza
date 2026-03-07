<?php
require_once __DIR__ . '/../function/function_klinik.php'; // koneksi & helper DB

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
$sql = "SELECT rp.no_rawat, rp.tgl_registrasi, rp.kd_poli,
               ps.no_peserta
        FROM reg_periksa rp
        INNER JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
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
$jam_panggil = date('H:i:s'); // atau gunakan jam tertentu
$dt = new DateTime($tgl_periksa . ' ' . $jam_panggil, new DateTimeZone('Asia/Jakarta'));
$timestamp_ms = $dt->getTimestamp() * 1000;

// Payload panggil BPJS
$payload = [
    "tanggalperiksa" => $data['tgl_registrasi'],
    "kodepoli"       => $data['kd_poli'],
    "nomorkartu"     => $data['no_peserta'],
    "status"         => 2,
    "waktu"          => $timestamp_ms
];

// BPJS API
$cons_id    = '25685';
$secret_key = '9hX4AEEB8C';
$user_key   = 'a0e225428271c8e127fc2c539ff0192f';
$url_panggil = "https://apijkn-dev.bpjs-kesehatan.go.id/antreanfktp_dev/antrean/panggil";

date_default_timezone_set('UTC');
$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
$auth      = "Basic ".base64_encode("tester.stmartina:Bpjs123**:095");

$headers = [
    "Content-Type: application/json",
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key",
    "X-authorization: $auth"
];

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
