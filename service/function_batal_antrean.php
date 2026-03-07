<?php
require_once __DIR__ . '/../function/function_klinik.php'; // koneksi & helper DB

// ---------- Cek POST ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Harus via POST');
}

$no_rawat = $_POST['no_rawat'] ?? '';
if (!$no_rawat) die('no_rawat kosong');

// ---------- Ambil data pasien ----------
$sql = "SELECT rp.no_rawat, rp.tgl_registrasi, rp.kd_poli,
               ps.no_peserta, ps.no_rkm_medis AS norm
        FROM reg_periksa rp
        INNER JOIN pasien ps ON ps.no_rkm_medis = rp.no_rkm_medis
        WHERE rp.no_rawat = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $no_rawat);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die('Data pasien tidak ditemukan');

// ---------- Payload batal ----------
$payload = [
    "tanggalperiksa" => $data['tgl_registrasi'],
    "kodepoli"       => $data['kd_poli'],
    "nomorkartu"     => $data['no_peserta'],
    "alasan"         => "Terjadi perubahan jadwal dokter"
];

// ---------- BPJS API Info ----------
$cons_id    = '25685';
$secret_key = '9hX4AEEB8C';
$user_key   = 'a0e225428271c8e127fc2c539ff0192f';
$url_batal  = "https://apijkn-dev.bpjs-kesehatan.go.id/antreanfktp_dev/antrean/batal";

date_default_timezone_set('UTC');
$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
$auth      = "Basic ".base64_encode("tester.stmartina:Bpjs123**:095");

// ---------- Header ----------
$headers = [
    "Content-Type: application/json",
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key",
    "X-authorization: $auth"
];

// ---------- Kirim ke BPJS ----------
$ch = curl_init($url_batal);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
if(curl_errno($ch)){
    echo "Curl error: ".curl_error($ch);
} else {
    echo "Response BPJS Batal: " . $response;
}
curl_close($ch);
