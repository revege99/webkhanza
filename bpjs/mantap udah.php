<?php 
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }


require_once 'myproject/vendor/autoload.php';

$function_path = __DIR__ . '/../function/function.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

// Default tanggal awal & akhir
$tanggal_awal = date("Y-m-d");
$tanggal_akhir = date("Y-m-d");
$keyword = '';
// var_dump($tanggal_awal, $tanggal_akhir);
// die();


if (isset($_POST['submit'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
}


$keywordLike = "%" . $keyword . "%";


$query = "
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

  CASE

    WHEN mb.dikirim IS NULL OR mb.diterima IS NULL THEN 'Belum Selesai'

    -- 🔴 Jika task5 kosong padahal task3 & 4 sudah ada → Belum Selesai
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
LEFT JOIN resep_obat ro ON mb.no_rawat = ro.no_rawat  -- ✅ join langsung ke mutasi_berkas
LEFT JOIN reg_periksa rp ON mb.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli

WHERE DATE(rp.tgl_registrasi) BETWEEN ? AND ?
  AND (p.nm_pasien LIKE ? or mb.no_rawat LIKE ? ) 
  AND rp.kd_poli NOT IN ('IGDK','UMUM','U0026')
  AND rp.status_lanjut = 'Ralan'
  -- AND rp.kd_pj = 'BPJ'
  AND rp.biaya_reg != 0

GROUP BY mb.no_rawat;


";

$task = queryPrepared($query, [$tanggal_awal, $tanggal_akhir, $keywordLike, $keywordLike]);
$record = count($task);


use LZCompressor\LZString;

date_default_timezone_set('UTC');

// Konfigurasi
$cons_id     = '22020';
$secretKey   = '3aLBB8C8D8';
$user_key    = '1cae203f209aa3d28db949c8a3806069'; 
$tanggal     = '2025-07-19'; 

// Generate timestamp dan signature
$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secretKey, true));

// Buat URL endpoint
$url = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/pendaftaran/tanggal/$tanggal_awal";

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







$kodebooking = $_POST['kodebooking'] ?? '';
$taskid = $_POST['taskid'] ?? '';
$waktu = $_POST['waktu'] ?? '';

if ($kodebooking && $taskid && $waktu) {
    $result = kirimTaskBPJS($kodebooking, $taskid, $waktu);
    echo json_encode($result);
} else {
    echo json_encode(["status" => false, "message" => "Data tidak lengkap!"]);
}


$no = 1;
$data_ditampilkan = [];

foreach ($data_array as $item) {
    $no_rm = trim($item['norekammedis']);
    $kodebooking = trim($item['kodebooking']);
    $status = trim($item['status']);

    $sql = "SELECT
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
            AND DATE(rp.tgl_registrasi) BETWEEN ? AND ?
            AND rp.kd_poli NOT IN ('IGDK','UMUM','U0026')
            AND rp.status_lanjut = 'Ralan'
            AND rp.biaya_reg != 0
            GROUP BY mb.no_rawat
            ORDER BY rp.tgl_registrasi DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $no_rm, $tanggal_awal, $tanggal_akhir);
    $stmt->execute();
    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    if ($row) {
        $timestamps = [];
        foreach (['task3', 'task4', 'task5', 'task6', 'task7'] as $key) {
            if (!empty($row[$key])) {
                $dt = new DateTime($row[$key], new DateTimeZone('Asia/Jakarta'));
                $dt->setTimezone(new DateTimeZone('UTC'));
                $timestamps[$key] = $dt->getTimestamp() * 1000;
            } else {
                $timestamps[$key] = '';
            }
        }

        $data_ditampilkan[] = [
            'no' => $no,
            'no_rm' => $no_rm,
            'kodebooking' => $kodebooking,
            'status' => $status,
            'timestamps' => $timestamps,
            'row' => $row
        ];
    } else {
        $data_ditampilkan[] = [
            'no' => $no,
            'no_rm' => $no_rm,
            'kodebooking' => $kodebooking,
            'status' => $status,
            'error' => '❌ Tidak ditemukan di database'
        ];
    }

    $no++;
}




?>



<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> RSSL </title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->

    <!-- fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" type="text/css" href="report/css/anak.css">
   
    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style type="text/css">
        
        #registrasi tr:nth-child(even) {
              background-color: #f2f9ff; /* biru muda */
          }

          #registrasi tr:nth-child(odd) {
              background-color: #ffffff; /* putih */
          }

          #registrasi th {
              background-color: #0d6efd;
              color: white;
              text-align: center;
              padding: 8px;

          }

          #registrasi td {
              padding: 8px;
              text-align: left;
              border: 1px solid #ddd;

          }


          .table-container {
        max-height: 400px; /* atau sesuai kebutuhan */
        overflow-y: auto;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }
.btn-kirim {
        background-color: #007BFF;
        color: white;
        border: none;
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
    }

    .btn-kirim:hover {
        background-color: #0056b3;
    }

    .btn-kirim:disabled {
        background-color: #999;
        cursor: not-allowed;
    }
  
    
    </style>

    
  </head>
  <body> 
      <div class="content">
        <nav class="navbar fixed-top">
        <div class="container">
          <div class="d-flex align-items-center">
            <h3 style="color : black;">Monitoring Task 3 - 7 Pasien BPJS</h3>
          </div>  
          <div class="btn-group">
            <div class="col-auto">
              <form class="export" method="post">
                <button class="btn btn-warning" id="export" type="submit" name="export">Export ke Excel</button>
              </form>
            </div>
               <div class="col-auto" id="record">
                <p>Record : <?=$record ?></p>
              </div>
            </div>          
      </div>
    </nav>
    <div class="container-fluid" id="container-fitur">
    <form class="row gy-2 gx-3 align-items-center" action="" method="post">
      <div class="row" id="fitur">
      <div class="col-auto">
        <label class="visually-hidden" for="autoSizingInputGroup">Username</label>
        <div class="input-group">
          <div class="input-group-text">Tanggal Awal</div>
          <input type="date" value="<?=$tanggal_awal; ?>"  class="form-control" id="autoSizingInputGroup"name="tanggal_awal">
        </div>
      </div>
      <div class="col-auto ">
        <div class="input-group">
          <div class="input-group-text">Tanggal Akhir</div>
          <input  type="date" class="form-control" id="autoSizingInputGroup" value="<?=$tanggal_akhir; ?>" name="tanggal_akhir" >
        </div>
      </div>

      <div class="col-auto ">
        <div class="input-group">
          <div class="input-group-text"> Key Word</div>
          <input  type="text" class="form-control" id="autoSizingInputGroup"  name="keyword"  value="<?=htmlspecialchars($keyword)?>">
        </div>
      </div>
 
     
      <div class="col-auto">
        <button type="submit" class="btn btn-primary" name="submit">Cari</button>
      </div>

     
</form>


    </div>
        </div>
          <div class="container-fluid" id="data" >
            <div class="row" id="table-container">
                <table id="registrasi" width="800">
                  
                        <tr>
                            <th>No</th>
                            <th>No Rm</th>
                            <th>Aksi</th>
                            <th>Kode Booking</th>
                            <th>No Rawat</th>
                            <th>Task 3</th>
                            <th>Task 4</th>
                            <th>Task 5</th>
                            <th>Task 6</th>
                            <th>Task 7</th>
                            <th>Status Task</th>
                            <th>Status</th>
                        </tr>
                   
                    <tbody>
                <?php foreach ($data_ditampilkan as $data): ?>
                    <?php if (isset($data['error'])): ?>
                        <tr>
                            <td><?= $data['no'] ?></td>
                            <td colspan="11"><?= $data['error'] ?></td>
                        </tr>
                    <?php else: 
                        $row = $data['row'];
                        $timestamps = $data['timestamps'];
                    ?>
                        <tr>
                            <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $data['no'] ?></td>
                            <td>
                                <form method="post" action="?page=kirimTask">
                                    <input type="hidden" name="kodebooking" value="<?= $data['kodebooking'] ?>">
                                    <input type="hidden" name="taskid" value="3">
                                    <input type="hidden" name="waktu" value="<?= strtotime($row['task3']) * 1000; ?>">
                                    <button type="submit"
                                        style="border-radius: 10px; background-color: blue; border: none; padding: 5px; width: 50px; color: white; font-weight: bold;">
                                        KIRIM
                                    </button>
                            </form>

                            </td>
                            <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $data['no_rm'] ?></td>
                            <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $data['kodebooking'] ?></td>
                            <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['no_rawat'] ?></td>
                            <?php foreach (['task3','task4','task5','task6','task7'] as $key): ?>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;">
                                    <?= $timestamps[$key] ?>
                                </td>
                            <?php endforeach; ?>
                            <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['validasi_task'] ?></td>
                            <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $data['status'] ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>
</form>
</div>
</div>
</body>

<script>
function kirimTask(kodebooking, taskid, waktu) {
    fetch('kirim_task.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            kodebooking: kodebooking,
            taskid: taskid,
            waktu: waktu
        })
    })
    .then(res => res.json())
    .then(data => {
        alert("Status: " + data.metaData.message);
        // bisa reload atau disable tombol di sini
    })
    .catch(err => alert("Error: " + err));
}
</script>

    </html>