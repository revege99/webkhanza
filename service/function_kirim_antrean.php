<?php
require_once __DIR__ . '/../function/function_klinik.php'; // koneksi & helper DB

function nextQueueNumber($kodePoli, $tanggal) {
    // Hanya dummy nomor antrean
    return [
        'angka' => 1,
        'nomor' => 'A-1'
    ];
}

// ---------- Ambil data pasien dari POST ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Harus via POST');
}

$no_rawat = $_POST['no_rawat'] ?? '';
if (!$no_rawat) die('no_rawat kosong');

// Ambil detail pasien dari DB
$sql = "SELECT rp.no_rawat, rp.tgl_registrasi, 
               mpp.kd_poli_pcare AS kd_poli, 
               pk.nm_poli,
               ps.no_peserta, ps.no_ktp, ps.no_tlp AS nohp,
               ps.no_rkm_medis AS norm,
               d.kd_dokter, d.nm_dokter,
               j.jam_mulai, j.jam_selesai
        FROM reg_periksa rp
        INNER JOIN pasien ps   ON ps.no_rkm_medis = rp.no_rkm_medis
        INNER JOIN poliklinik pk ON pk.kd_poli = rp.kd_poli
        INNER JOIN dokter d    ON d.kd_dokter = rp.kd_dokter
        INNER JOIN jadwal j    ON j.kd_dokter = d.kd_dokter
        INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli = mpp.kd_poli_rs
        WHERE rp.no_rawat = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $no_rawat);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) die('Data pasien tidak ditemukan');

// ---------- Generate nomor antrean ----------
$queue = nextQueueNumber($data['kd_poli'], $data['tgl_registrasi']);

// ---------- Format jam praktek HH:MM ----------
$jam_mulai  = substr($data['jam_mulai'], 0, 5);
$jam_selesai = substr($data['jam_selesai'], 0, 5);
$jam_praktek = $jam_mulai . '-' . $jam_selesai;

// ---------- Susun payload ----------
$payload = [
    "nomorkartu"     => $data['no_peserta'],
    "nik"            => $data['no_ktp'],
    "nohp"           => $data['nohp'],
    "kodepoli"       => $data['kd_poli'],
    "namapoli"       => $data['nm_poli'],
    "norm"           => $data['norm'],
    "tanggalperiksa" => $data['tgl_registrasi'],
    "kodedokter"     => $data['kd_dokter'],
    "namadokter"     => $data['nm_dokter'],
    "jampraktek"     => $jam_praktek,   // HH:MM-HH:MM
    "nomorantrean"   => $queue['nomor'],
    "angkaantrean"   => $queue['angka'],
    "keterangan"     => ""
];



// ---------- BPJS API Info ----------
$cons_id    = "13505";
$secret_key = "9hQE24BFE8";
$user_key   = "f68d3658bf3e2d1435ffff5397c3e9f0";
$url        = "https://apijkn.bpjs-kesehatan.go.id/antreanfktp/antrean/add";

date_default_timezone_set('UTC');
$timestamp  = time();
$signature  = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secret_key, true));
$auth       = "Basic " . base64_encode("tester.stmartina:Bpjs123**:095:095");

// ---------- Headers ----------
$headers = [
    "Content-Type: application/json",
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key",
    "X-authorization: $auth"
];

// ---------- Var_dump untuk debugging ----------
echo "====== URL & Headers ======\n";
var_dump($url, $headers);

echo "\n====== Payload ======\n";
var_dump($payload);

// ---------- Kirim request ke BPJS ----------
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "Curl error: " . curl_error($ch);
}
curl_close($ch);

echo "\n====== Response ======\n";
var_dump($response);
