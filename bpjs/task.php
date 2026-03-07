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

$keyword = '';
// var_dump($tanggal_awal, $tanggal_akhir);
// die();


if (isset($_POST['submit'])) {
    $tanggal_awal = $_POST['tanggal_awal'];

    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
}


$keywordLike = "%" . $keyword . "%";


$cari_sep = "
SELECT rp.no_rawat, bs.no_sep, mb.status, rp.status_lanjut
FROM reg_periksa rp 
inner JOIN bridging_sep bs ON rp.no_rawat = bs.no_rawat
LEFT JOIN mutasi_berkas mb ON rp.no_rawat =  mb.no_rawat
WHERE rp.tgl_registrasi BETWEEN ? AND ?
AND rp.stts <> 'batal'
AND rp.kd_pj = 'BPJ' 
AND status_lanjut ='ralan'
AND kd_poli NOT IN ('IGDK', 'UMUM', 'U0026')

";

$sep = queryPrepared($cari_sep,[$tanggal_awal, $tanggal_awal]);
$record_sep = count($sep);



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


$url = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/pendaftaran/tanggal/$tanggal_awal";

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

function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
}

$key = $cons_id . $secretKey . $timestamp;

$decrypted = stringDecrypt($key, $result['response']);


$original = LZString::decompressFromEncodedURIComponent($decrypted);

if ($original === null) {
    echo "⚠️ Tidak bisa decompress. Mungkin data tidak dikompresi atau key salah.\n";
    exit;
}


$data_array = json_decode($original, true);

// Cek dan tampilkan hanya norekammedis
// if (is_array($data_array)) {
//     foreach ($data_array as $item) {
//         echo "No Rekam Medis: " . $item['norekammedis'] . "<br>";
//     }
// } else {
//     echo " Data tidak dalam format array!";
// }



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



$result_final = [];
$count_bpjs_selesai = 0;

foreach ($data_array as $item) {
    $no_rm = str_replace(' ', '', trim($item['norekammedis']));
    $kodebooking = trim($item['kodebooking']);
    $status = trim($item['status']);
    $sumberdata = trim($item['sumberdata']);

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
            rp.status_lanjut,
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
        AND (p.nm_pasien LIKE ? OR mb.no_rawat LIKE ?)
        AND rp.kd_poli NOT IN ('IGDK','UMUM','U0026')
        AND rp.status_lanjut = 'Ralan'
        AND rp.biaya_reg != 0
        GROUP BY mb.no_rawat
        ORDER BY rp.tgl_registrasi DESC
        LIMIT 1;
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $no_rm, $tanggal_awal, $tanggal_awal, $keywordLike, $keywordLike);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        foreach (['task3', 'task4', 'task5', 'task6', 'task7'] as $key) {
            if (!empty($row[$key])) {
                $dt = new DateTime($row[$key], new DateTimeZone('Asia/Jakarta'));
                $dt->setTimezone(new DateTimeZone('UTC'));
                $row[$key] = $dt->getTimestamp() * 1000;
            } else {
                $row[$key] = '';
            }
        }

        $row['kodebooking'] = $kodebooking;
        $row['status'] = $status;
        $row['sumberdata'] = $sumberdata;

        // Hitung jika BPJS & Selesai
        if (strtoupper($row['kd_pj']) === 'BPJ' && strtolower($status) === 'selesai dilayani') {
            $count_bpjs_selesai++;
        }

        $result_final[] = $row;
    } else {
        $result_final[] = [
            'no_rkm_medis' => $no_rm,
            'kodebooking' => $kodebooking,
            'status' => $status,
            'sumberdata' => $sumberdata,
            'no_data' => true
        ];
    }
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


    .record-container {
    display: flex;
    gap: 1rem; 
    padding: 10px;flex-wrap: wrap; 
/*    background-color: darkred;*/
    margin-top: -5px;
  }

  .record-box {
    background-color: darkred;
    border: 1px solid #ccc;
    padding: 5px;
    border-radius: 8px;
    min-width: 200px;
    flex: 1; /* biar menyesuaikan ruang */
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .record-box p {
    margin: 0;
    font-weight: bold;
    font-size: 1rem;
    color: white;
  }


  
    
    </style>

    
  </head>
  <body> 
      <div style="margin-top:-2rem" class="content">
        <nav class="navbar fixed-top">
        <div class="container" >
          <div class="d-flex align-items-center">
            <h3 style="color : black;">API BPJS SEND TASK</h3>
          </div>  
          <?php
                $persen_sep = 0;
                if ($count_bpjs_selesai > 0) {
                    $persen_sep = ($count_bpjs_selesai / $record_sep) * 100;
                }
                ?>
                <div class="record-container" >
                  <div class="record-box">
                    <p>SEP Terbit: <?= $record_sep ?></p>
                  </div>
                  <div class="record-box">
                    <p>Kunjungan BPJS: <?= $count_bpjs_selesai ?></p>
                  </div>
                  
                  <div class="record-box">
                    <p>Persentase: <?= number_format($persen_sep, 1) ?>%</p>
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
      <!-- <div class="col-auto ">
        <div class="input-group">
          <div class="input-group-text">Tanggal Akhir</div>
          <input  type="date" class="form-control" id="autoSizingInputGroup" value="<?=$tanggal_akhir; ?>" name="tanggal_akhir" >
        </div>
      </div> -->

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
            <div class="row" id="table-container" >
                <table id="registrasi" width="800" height="100%" >
                    <thead style=" position: sticky;
                                top: 0;
                                background-color: white; 
                                z-index: 10;">
                        <tr>
                            <th>No</th>
                            <th>Aksi</th>
                            <th>No RM</th>
                            <th>Kode Booking</th>
                            <th>No Rawat</th>
                            <th>Kd PJ</th>
                            <th>Task 3</th>
                            <th>Task 4</th>
                            <th>Task 5</th>
                            <th>Task 6</th>
                            <th>Task 7</th>
                             <th>Validasi</th>
                             <th>Sumber Data</th>
                             <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 

                        $no = 1;
                        foreach ($result_final as $row):
                            if (isset($row['no_data']) && $row['no_data'] === true):
                        ?>
                            <tr style='background-color: #f8d7da;'>
                                <td><?= $no ?></td>
                                <td></td>
                                <td><?= $row['no_rkm_medis'] ?></td>
                                <td><?= $row['kodebooking'] ?></td>
                                <td colspan='8' style='text-align:center; font-weight:bold;'>tidak ada data di mutasi berkas</td>
                                <td style='text-align:center;'><?= $row['sumberdata'] ?></td>
                                <td style='text-align:center;'><?= $row['status'] ?></td>
                            </tr>
                        <?php
                            else:
                        ?>
                            <tr>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $no ?></td>
                                <td>
                                    <form method='post' action='?page=kirimTask' style='margin:0;'>
                                        <input type='hidden' name='kodebooking' value='<?= $row['kodebooking'] ?>'>
                                        <?php for ($i = 3; $i <= 7; $i++): ?>
                                            <input type='hidden' name='task[<?= $i ?>]' value='<?= $row['task' . $i] ?>'>
                                        <?php endfor; ?>
                                        <button type='submit' class='btn-kirim'>Kirim</button>
                                    </form>
                                </td>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['no_rkm_medis'] ?></td>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['kodebooking'] ?></td>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['no_rawat'] ?></td>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['kd_pj'] ?></td>
                                <?php for ($i = 3; $i <= 7; $i++): ?>
                                    <td style="text-align: center; font-size: 12px; font-weight: bold;">
                                        <?= $row['task' . $i] ?: '' ?>
                                    </td>
                                <?php endfor; ?>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['validasi_task'] ?></td>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['sumberdata'] ?></td>
                                <td style="text-align: center; font-size: 12px; font-weight: bold;"><?= $row['status'] ?></td>
                            </tr>
                        <?php
                            endif;
                            $no++;
                        endforeach;
                        ?>    
                       </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</div>
</body>

</html>