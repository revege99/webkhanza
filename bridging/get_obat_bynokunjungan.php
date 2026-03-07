
<?php
require_once 'myproject/vendor/autoload.php';

$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

use LZCompressor\LZString;
date_default_timezone_set('UTC');

// Konfigurasi
$cons_id     = '25685';
$secretKey   = '9hX4AEEB8C';
$user_key    = 'a0e225428271c8e127fc2c539ff0192f';
$authorization = 'Basic dGVzdGVyLnN0bWFydGluYTpCcGpzMTIzKio6MDk1';

// Generate timestamp dan signature
$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secretKey, true));

// Header API
$headers = [
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key",
    "X-authorization: $authorization"
];

// Fungsi dekripsi string
function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
}

// Ambil daftar noKunjungan dari database
$tanggal_awal = '2025-08-06';
$tanggal_akhir = date("Y-m-d");

$sql = "SELECT * FROM pcare_kunjungan_umum WHERE tglDaftar BETWEEN ? AND ?";
$no_kunj_list = queryPrepared($sql, [$tanggal_awal, $tanggal_akhir]);

// Siapkan array untuk menampung data obat
$data_obat_list = [];

foreach ($no_kunj_list as $item) {
    $noKunjungan = $item['noKunjungan'];
    $url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/obat/kunjungan/$noKunjungan";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch);
        curl_close($ch);
        continue;
    }

    curl_close($ch);

    $result = json_decode($response, true);
    $key = $cons_id . $secretKey . $timestamp;

    if (!isset($result['response'])) {
        continue;
    }

    $decrypted = stringDecrypt($key, $result['response']);
    $original = LZString::decompressFromEncodedURIComponent($decrypted);

    if ($original === null) {
        continue;
    }

    $data_array = json_decode($original, true);

    // Cek apakah 'list' ada dan merupakan array
    if (isset($data_array['list']) && is_array($data_array['list'])) {
        foreach ($data_array['list'] as $obat) {
            $data_obat_list[] = [
                'noKunjungan'     => $noKunjungan,
                'kdObatSK'        => $obat['kdObatSK'] ?? '-',
                'kdObat'          => $obat['obat']['kdObat'] ?? '-',
                'nmObat'          => $obat['obat']['nmObat'] ?? '-',
                'signa1'          => $obat['signa1'] ?? '',
                'signa2'          => $obat['signa2'] ?? '',
                'jmlObat'         => $obat['jmlObat'] ?? '',
                'jmlHari'         => $obat['jmlHari'] ?? '',
                'kekuatan'        => $obat['kekuatan'] ?? '',
                'kdRacikan'       => $obat['kdRacikan'] ?? '',
                'jmlPermintaan'   => $obat['jmlPermintaan'] ?? '',
                'jmlObatRacikan'  => $obat['jmlObatRacikan'] ?? '',
            ];
        }
    }
}


// echo '<pre style="margin-left:20rem; margin-top:10rem">';
// var_dump($data_obat_list);
// echo '</pre>';
// die();


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

  
    
    </style>

    
  </head>
      <body> 
          <div style="margin-top:-2rem" class="content">
            <nav class="navbar fixed-top">
            <div class="container">
              <div class="d-flex align-items-center">
                <h3 style="color : black;">Data Obat Pcare Api</h3>
              </div>  
     
                   
                    <div class="record-container">
                      
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
                      <div class="container-fluid" id="data">
                        <div class="row" id="table-container" style="">
                            <table id="registrasi" width="800" height="100%" style="margin-top:2rem">
                            <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                <tr>
                                    <th>No</th>
                                    <th>kdObatSK</th>
                                    <th>No Kunjungan</th>
                                    <th>Aturan Pakai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data_obat_list)): ?>
                                    <tr><td colspan="7">Tidak ada data obat.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($data_obat_list as $obat): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($obat['nmObat']) ?></td>
                                            <td><?= htmlspecialchars($obat['kdObatSK']) ?></td>
                                            <td><?= htmlspecialchars($obat['signa1']) ?> x <?= htmlspecialchars($obat['signa2']) ?></td>
                                            <td><?= htmlspecialchars($obat['jmlObat']) ?></td>
                                            <td><?= htmlspecialchars($obat['jmlHari']) ?></td>
                                            <td><?= htmlspecialchars($obat['noKunjungan']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>

                            
                        </table>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </body>

</html>
