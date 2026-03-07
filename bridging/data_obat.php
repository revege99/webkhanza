<?php

if (isset($_GET['hapus'])) {
    if ($_GET['hapus'] == 'success') {
        echo "<script>alert('✅ Obat berhasil dihapus dari BPJS dan lokal.');</script>";
    } elseif ($_GET['hapus'] == 'partial') {
        echo "<script>alert('⚠️ Obat terhapus dari BPJS tapi gagal hapus lokal.');</script>";
    } elseif ($_GET['hapus'] == 'fail') {
        $pesan = isset($_GET['pesan']) ? urldecode($_GET['pesan']) : 'Tidak diketahui';
        echo "<script>alert('❌ Gagal menghapus obat dari BPJS: $pesan');</script>";
    }
}

require_once 'myproject/vendor/autoload.php';

$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

if (isset($_GET['noKunjungan'])) {
    $noKunjungan = $_GET['noKunjungan'];
} else {
    die("Parameter noKunjungan tidak ditemukan.");
}

$cari_obat_diberikan = "
SELECT 
    a.no_rawat, 
    a.noKunjungan,
    a.kdObatSK,
    a.kode_brng,
    db.nama_brng,
    dp.jml
FROM `pcare_obat_diberikan` a
INNER JOIN databarang db 
    ON a.kode_brng = db.kode_brng
INNER JOIN `detail_pemberian_obat` dp 
    ON a.kode_brng = dp.kode_brng 
   AND a.no_rawat = dp.no_rawat 
WHERE a.noKunjungan = ?
";


$obat_diberikan = queryPrepared($cari_obat_diberikan, [$noKunjungan]);

// var_dump($obat_diberikan);
// die();







use LZCompressor\LZString;
date_default_timezone_set('UTC');

// Konfigurasi
$cons_id       = '25685';
$secretKey     = '9hX4AEEB8C';
$user_key      = 'a0e225428271c8e127fc2c539ff0192f';
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




// Panggil API
$url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/obat/kunjungan/$noKunjungan";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error: " . curl_error($ch));
}
curl_close($ch);

// Proses hasil
$result = json_decode($response, true);
$key = $cons_id . $secretKey . $timestamp;

if (!isset($result['response']) || !is_string($result['response'])) {
    die("<div style='margin-left:20rem; font-family:Arial; font-size:16px; color:red;'>
        Data Obat tidak pcare tidak ada <b>$noKunjungan</b>
     </div>");

}

$decrypted = stringDecrypt($key, $result['response']);
$original  = LZString::decompressFromEncodedURIComponent($decrypted);

if ($original === null) {
    die("Gagal mendekompresi data untuk noKunjungan $noKunjungan");
}

$data_array    = json_decode($original, true);
$data_obat_list = [];

if (isset($data_array['list']) && is_array($data_array['list'])) {
    foreach ($data_array['list'] as $obat) {
        // Cari no_rawat dari hasil query lokal
        $no_rawat_local = '';
        foreach ($obat_diberikan as $lokal) {
            if ($lokal['kdObatSK'] == ($obat['kdObatSK'] ?? '')) {
                $no_rawat_local = $lokal['no_rawat'];
                $kd_brng_lokal = $lokal['kode_brng'];
                $jml_local = $lokal['jml'];
                break;
            }
        }

        $data_obat_list[] = [
            'no_rawat'    => $no_rawat_local ?: '-',
            'kode_brng'    => $kd_brng_lokal ?: '-',
            'jml'    => $jml_local ?: '-',
            'kdObatSK'    => $obat['kdObatSK'] ?? '-',
            'kdObat'      => $obat['kdObat'] ?? '-',
            'noKunjungan' => $noKunjungan
        ];
    }
}


// Debug output
// echo '<pre>';
// print_r($data_obat_list);
// echo '</pre>';

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
                <h3 style="color : black;">Data Obat</h3>
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
                                    <th>KD Obat SK</th>
                                    <th>No Rawat</th>
                                    <th>Kode Barang</th>
                                    <th>No Kunjungan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data_obat_list)): ?>
                                    <tr>
                                        <td colspan="3">Tidak ada data.</td></tr>
                                        <?php else: ?>
                                            <?php $no = 1; foreach ($data_obat_list as $obat): ?>
                                               <tr>
                                                  <td><?= $no++; ?></td>
                                                  <td><?= htmlspecialchars($obat['kdObatSK']) ?></td>
                                                  <td><?= htmlspecialchars($obat['no_rawat']) ?></td>
                                                  <td><?= htmlspecialchars($obat['kode_brng']) ?></td>
                                                  <td><?= htmlspecialchars($obat['noKunjungan']) ?></td>
                                                  <td style="display: flex; justify-content: center; align-items: center; gap: 10px;">
                                                <form method="POST" action="?page=proses_del_obat" onsubmit="return confirm('Yakin ingin menghapus obat ini dari BPJS?')">
                                                    <input type="hidden" name="kdObatSK" value="<?= htmlspecialchars($obat['kdObatSK']) ?>">
                                                    <input type="hidden" name="noKunjungan" value="<?= htmlspecialchars($obat['noKunjungan']) ?>">
                                                    <input type="hidden" name="kode_brng" value="<?= htmlspecialchars($obat['kode_brng']) ?>">
                                                    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($obat['no_rawat']) ?>">
                                                    <input type="hidden" name="jml" value="<?= htmlspecialchars($obat['jml']) ?>">
                                                    <button type="submit" style="border: none; background: none;">
                                                        <i class="fa-solid fa-trash fa-2x" style="color: #dc3545; cursor: pointer;"></i>
                                                    </button>
                                                </form>
                                            </td>
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
