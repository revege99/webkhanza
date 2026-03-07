
<?php
require_once 'vendor/autoload.php';

use LZCompressor\LZString;

date_default_timezone_set('UTC');

// Konfigurasi
$cons_id     = '22020';
$secretKey   = '3aLBB8C8D8';
$user_key    = '1cae203f209aa3d28db949c8a3806069'; 
$tanggal     = '2025-07-16'; 

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

// Dekripsi
$decrypted = stringDecrypt($key, $result['response']);

// Decompress
$original = LZString::decompressFromEncodedURIComponent($decrypted);

if ($original === null) {
    echo "⚠️ Tidak bisa decompress. Mungkin data tidak dikompresi atau key salah.\n";
    exit;
}

// Ubah hasil decompress ke array PHP
$data_array = json_decode($original, true);

// Cek dan tampilkan hanya norekammedis
// if (is_array($data_array)) {
//     foreach ($data_array as $item) {
//         echo "No Rekam Medis: " . $item['norekammedis'] . "<br>";
//     }
// } else {
//     echo "❌ Data tidak dalam format array!";
// }


// Tampilkan hasil JSON
// header('Content-Type: application/json');
// echo $original;


$data_array = json_decode($original, true);


$koneksi = new mysqli("192.168.3.250", "rssl", "s1ntluc14", "sik"); // sesuaikan user/password/DB

if ($koneksi->connect_error) {
    die("❌ Koneksi ke database gagal: " . $koneksi->connect_error);
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data No. Rekam Medis</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px auto;
        }
        th, td {
            padding: 10px 15px;
            border: 1px solid #ccc;
            text-align: center;
        }
        th {
            background: #f0f0f0;
        }
    </style>
</head>
<body>

    <h2 style="text-align:center;">Daftar No. Rekam Medis (<?php echo htmlspecialchars($tanggal); ?>)</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Rekam Medis</th>
                <th>No Booking</th>
                <th>NIK</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (is_array($data_array) && count($data_array) > 0) {
                $no = 1;
                foreach ($data_array as $item) {
                    echo "<tr>";
                    echo "<td>$no</td>";
                    echo "<td>" . htmlspecialchars($item['norekammedis']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['kodebooking']) . "</td>"; 
                    echo "<td>" . htmlspecialchars($item['nik']) . "</td>"; 

                    echo "<td>" . htmlspecialchars($item['status']) . "</td>";
                    echo "</tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='4'>Tidak ada data.</td></tr>";
            }
            ?>
        </tbody>
    </table>


    <h2>Pisahan</h2>

    <h2 style="text-align:center;">Task 3 s/d 7 dari No. Rekam Medis (<?= htmlspecialchars($tanggal); ?>)</h2>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>No RM</th>
            <th>Kode Booking</th>
            <th>No Rawat</th>
            <th>Task 3</th>
            <th>Task 4</th>
            <th>Task 5</th>
            <th>Task 6</th>
            <th>Task 7</th>
             <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;

        foreach ($data_array as $item) {
            $no_rm = trim($item['norekammedis']);
            $kodebooking = trim($item['kodebooking']);
            $sql = "
            SELECT
              p.nm_pasien,
              mb.no_rawat,
              mb.dikirim AS task3,
              mb.diterima AS task4,
              CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat) AS task5,
              CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan) AS task6,
              CONCAT(ro.tgl_perawatan, ' ', ro.jam) AS task7,
              pk.nm_poli,
              rp.kd_pj,
              rp.no_rkm_medis,
              CASE
                WHEN mb.dikirim IS NULL OR mb.diterima IS NULL THEN 'Belum Selesai'
                WHEN pr.tgl_perawatan IS NULL OR pr.jam_rawat IS NULL THEN 'Belum Selesai'
                WHEN (ro.tgl_peresepan IS NOT NULL AND ro.jam_peresepan IS NOT NULL 
                       AND (ro.tgl_perawatan IS NULL OR ro.jam IS NULL))
                  OR (ro.tgl_perawatan IS NOT NULL AND ro.jam IS NOT NULL 
                       AND (ro.tgl_peresepan IS NULL OR ro.jam_peresepan IS NULL)) THEN 'Belum Selesai'
                WHEN mb.diterima < mb.dikirim THEN 'Task4 < Task3'
                WHEN CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat) < mb.diterima THEN 'Task5 < Task4'
                WHEN ro.tgl_peresepan IS NOT NULL AND ro.jam_peresepan IS NOT NULL 
                     AND CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan) < CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat) THEN 'Task6 < Task5'
                WHEN ro.tgl_perawatan IS NOT NULL AND ro.jam IS NOT NULL 
                     AND CONCAT(ro.tgl_perawatan, ' ', ro.jam) < CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan) THEN 'Task7 < Task6'
                ELSE 'OK'
              END AS validasi_task
            FROM mutasi_berkas mb 
            LEFT JOIN pemeriksaan_ralan pr ON mb.no_rawat = pr.no_rawat
            LEFT JOIN resep_obat ro ON mb.no_rawat = ro.no_rawat  
            LEFT JOIN reg_periksa rp ON mb.no_rawat = rp.no_rawat
            LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli
            LEFT JOIN referensi_mobilejkn_bpjs rmb ON mb.no_rawat = rmb.no_rawat
            WHERE rp.no_rkm_medis = ?
             
              AND rp.kd_poli NOT IN ('IGDK','UMUM','U0026')
              AND rp.status_lanjut = 'Ralan'
              AND rp.biaya_reg != 0
            GROUP BY mb.no_rawat
            ORDER BY rp.tgl_registrasi DESC
            LIMIT 1;

                    ";
            
            $stmt = $koneksi->prepare($sql);
            $stmt->bind_param("s", $no_rm);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$no_rm}</td>
                        <td>{$kodebooking}</td>
                        <td>{$row['no_rawat']}</td>
                        <td>{$row['task3']}</td>
                        <td>{$row['task4']}</td>
                        <td>{$row['task5']}</td>
                        <td>{$row['task6']}</td>
                        <td>{$row['task7']}</td>
                        <td>{$row['validasi_task']}</td>

                      </tr>";
            } else {
                echo "<tr>
                        <td>{$no}</td>
                        <td>{$no_rm}</td>
                        <td colspan='6'>❌ Tidak ditemukan di database</td>
                      </tr>";
            }

            $no++;
        }
        ?>
    </tbody>
</table>

</body>
</html>
