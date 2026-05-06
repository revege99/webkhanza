<?php
require_once __DIR__ . '/../function/function_klinik.php';
require_once __DIR__ . '/../myproject/vendor/autoload.php';
require_once __DIR__ . '/../function/bpjs_config.php';

use LZCompressor\LZString;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => false,
        'message' => 'Harus via POST'
    ], JSON_PRETTY_PRINT);
    exit;
}

$no_rawat = $_POST['no_rawat'] ?? '';

if (empty($no_rawat)) {
    echo json_encode([
        'status' => false,
        'message' => 'no_rawat kosong'
    ], JSON_PRETTY_PRINT);
    exit;
}

function stringDecrypt($key, $string)
{
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);

    return openssl_decrypt(
        base64_decode($string),
        $encrypt_method,
        $key_hash,
        OPENSSL_RAW_DATA,
        $iv
    );
}

function decompressBPJS($string)
{
    return LZString::decompressFromEncodedURIComponent($string);
}

if (
    empty($cons_id) ||
    empty($secret_key) ||
    empty($base_url) ||
    empty($auth) ||
    empty($timestamp) ||
    empty($signature) ||
    empty($user_key_pcare)
) {
    echo json_encode([
        'status' => false,
        'message' => 'Konfigurasi BPJS belum lengkap'
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * =========================
 * AMBIL SEMUA DIAGNOSA PASIEN
 * =========================
 */
$sql = "SELECT 
            dp.no_rawat,
            dp.kd_penyakit,
            dp.status,
            dp.prioritas,
            dp.status_penyakit,
            dp.nonSpesialis AS nonSpesialis_lama
        FROM diagnosa_pasien dp
        WHERE dp.no_rawat = ?
        ORDER BY dp.prioritas ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'status' => false,
        'message' => 'Prepare SQL gagal',
        'error' => $conn->error
    ], JSON_PRETTY_PRINT);
    exit;
}

$stmt->bind_param('s', $no_rawat);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows < 1) {
    echo json_encode([
        'status' => false,
        'message' => 'Data diagnosa pasien tidak ditemukan',
        'no_rawat' => $no_rawat
    ], JSON_PRETTY_PRINT);
    exit;
}

$hasil_update = [];
$jumlah_berhasil = 0;
$jumlah_gagal = 0;

/**
 * =========================
 * LOOP SEMUA DIAGNOSA
 * =========================
 */
while ($data = $result->fetch_assoc()) {

    $kd_penyakit = trim($data['kd_penyakit']);
    $status_diagnosa = trim($data['status']);
    $prioritas = (int) $data['prioritas'];
    $status_penyakit = $data['status_penyakit'] ?? null;
    $nonSpesialis_lama = $data['nonSpesialis_lama'] ?? null;

    if (empty($kd_penyakit)) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Kode diagnosa kosong'
        ];
        continue;
    }

    /**
     * =========================
     * URL PCARE DIAGNOSA
     * =========================
     */
    $url = rtrim($base_url, '/') . "/diagnosa/" . urlencode($kd_penyakit) . "/0/500";

    /**
     * =========================
     * HEADERS PCARE
     * =========================
     */
    $headers = [
        "Content-Type: application/json",
        "X-cons-id: " . $cons_id,
        "X-timestamp: " . $timestamp,
        "X-signature: " . $signature,
        "X-authorization: " . $auth,
        "user_key: " . $user_key_pcare
    ];

    /**
     * =========================
     * REQUEST GET KE PCARE
     * =========================
     */
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Curl error: ' . curl_error($ch)
        ];
        curl_close($ch);
        continue;
    }

    curl_close($ch);

    if (empty($response)) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Response BPJS kosong',
            'http_code' => $http_code
        ];
        continue;
    }

    /**
     * =========================
     * PARSE RESPONSE BPJS
     * =========================
     */
    $response_bpjs = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Response BPJS bukan JSON valid',
            'http_code' => $http_code,
            'raw_response' => $response
        ];
        continue;
    }

    $metadata = $response_bpjs['metaData'] ?? null;

    if (!isset($response_bpjs['response'])) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => $metadata['message'] ?? 'Response terenkripsi tidak ditemukan',
            'http_code' => $http_code,
            'metadata' => $metadata,
            'raw_response' => $response_bpjs
        ];
        continue;
    }

    /**
     * =========================
     * DECRYPT RESPONSE BPJS
     * =========================
     */
    $key = $cons_id . $secret_key . $timestamp;

    $encrypted_response = trim($response_bpjs['response']);

    $decrypted = stringDecrypt($key, $encrypted_response);

    if ($decrypted === false) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Gagal dekripsi response BPJS',
            'http_code' => $http_code,
            'metadata' => $metadata,
            'debug' => [
                'timestamp' => $timestamp,
                'encrypted_sample' => substr($encrypted_response, 0, 80)
            ]
        ];
        continue;
    }

    /**
     * =========================
     * DECOMPRESS RESPONSE
     * =========================
     */
    $decompressed = decompressBPJS($decrypted);

    if ($decompressed === null) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Gagal decompress response BPJS',
            'http_code' => $http_code,
            'metadata' => $metadata,
            'decrypted' => $decrypted
        ];
        continue;
    }

    /**
     * =========================
     * CONVERT HASIL KE ARRAY
     * =========================
     */
    $json_result = json_decode($decompressed, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Hasil decrypt bukan JSON valid',
            'http_code' => $http_code,
            'metadata' => $metadata,
            'result_text' => $decompressed
        ];
        continue;
    }

    /**
     * =========================
     * AMBIL nonSpesialis BERDASARKAN kdDiag
     * =========================
     */
    $nonSpesialis = null;
    $nmDiag = null;

    if (isset($json_result['list']) && is_array($json_result['list'])) {
        foreach ($json_result['list'] as $item) {
            if (($item['kdDiag'] ?? '') === $kd_penyakit) {
                $nonSpesialis = $item['nonSpesialis'] ?? null;
                $nmDiag = $item['nmDiag'] ?? null;
                break;
            }
        }
    }

    if ($nonSpesialis === null) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Nilai nonSpesialis tidak ditemukan dari response PCare',
            'metadata' => $metadata,
            'data_pcare' => $json_result
        ];
        continue;
    }

    $nonSpesialisValue = $nonSpesialis ? 'true' : 'false';

    /**
     * =========================
     * DETEKSI AKSI
     * =========================
     */
    $aksi = 'no_change';

    if ($nonSpesialis_lama === null || $nonSpesialis_lama === '') {
        $aksi = 'insert_value';
    } elseif ($nonSpesialis_lama !== $nonSpesialisValue) {
        $aksi = 'update_changed';
    }

    /**
     * =========================
     * INSERT / UPDATE nonSpesialis
     * PRIMARY KEY:
     * no_rawat + kd_penyakit + status
     * =========================
     */
    $sqlUpsert = "INSERT INTO diagnosa_pasien
                  (
                      no_rawat,
                      kd_penyakit,
                      status,
                      prioritas,
                      status_penyakit,
                      nonSpesialis
                  )
                  VALUES (?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                      nonSpesialis = VALUES(nonSpesialis)";

    $stmtUpsert = $conn->prepare($sqlUpsert);

    if (!$stmtUpsert) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Prepare insert/update nonSpesialis gagal',
            'error' => $conn->error
        ];
        continue;
    }

    $stmtUpsert->bind_param(
        'sssiss',
        $no_rawat,
        $kd_penyakit,
        $status_diagnosa,
        $prioritas,
        $status_penyakit,
        $nonSpesialisValue
    );

    $stmtUpsert->execute();

    if ($stmtUpsert->errno) {
        $jumlah_gagal++;
        $hasil_update[] = [
            'kd_penyakit' => $kd_penyakit,
            'status_diagnosa' => $status_diagnosa,
            'status' => false,
            'message' => 'Gagal insert/update nonSpesialis',
            'error' => $stmtUpsert->error
        ];
        continue;
    }

    $jumlah_berhasil++;

    $hasil_update[] = [
        'kd_penyakit' => $kd_penyakit,
        'nmDiag' => $nmDiag,
        'status_diagnosa' => $status_diagnosa,
        'status' => true,
        'aksi' => $aksi,
        'message' => 'Berhasil insert/update nonSpesialis',
        'nonSpesialis_lama' => $nonSpesialis_lama,
        'nonSpesialis_baru' => $nonSpesialisValue,
        'metadata' => $metadata
    ];
}

/**
 * =========================
 * OUTPUT FINAL
 * =========================
 */
echo json_encode([
    'status' => $jumlah_berhasil > 0,
    'message' => 'Proses insert/update nonSpesialis selesai',
    'no_rawat' => $no_rawat,
    'total_diagnosa' => $result->num_rows,
    'jumlah_berhasil' => $jumlah_berhasil,
    'jumlah_gagal' => $jumlah_gagal,
    'hasil_update' => $hasil_update
], JSON_PRETTY_PRINT);