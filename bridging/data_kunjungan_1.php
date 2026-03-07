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





use LZCompressor\LZString;
date_default_timezone_set('UTC');

// Konfigurasi
$cons_id    = "25685";
    $secret_key = "9hX4AEEB8C";
    $user_key   = "a0e225428271c8e127fc2c539ff0192f";

    $timestamp   = time();
    $data        = $cons_id . "&" . $timestamp;
    $signature   = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
    $auth        = "Basic " . base64_encode("tester.stmartina:Bpjs123**:095");

// Buat URL endpoint
$url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/kunjungan/peserta/0002081694609";

// Set header
$headers = [
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key",
    "X-authorization: $auth"
        
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

// dekripsi
function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);
    return openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
}

//  key untuk dekripsi
$key = $cons_id . $secret_key . $timestamp;

// Dekripsi
$decrypted = stringDecrypt($key, $result['response']);

// Decompress
$original = LZString::decompressFromEncodedURIComponent($decrypted);

if ($original === null) {
    echo "⚠️ Tidak bisa decompress. Mungkin data tidak dikompresi atau key salah.\n";
    exit;
}

// hasil decompress ke array PHP
$data_array = json_decode($original, true);

// Ubah ke array baru yang hanya berisi data dengan tglDaftar == tanggal_awal
// Tanggal yang ingin difilter
$tanggal_filter = "2025-07-26"; // format YYYY-MM-DD

echo "<h3 style='margin-left:20rem;'>Semua data sebelum filter:</h3>";
echo "<pre style=\"margin-left: 20rem;\">";
print_r($data_array);
echo "</pre>";

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
            <h3 style="color : black;">API BPJS SEND TASK</h3>
          </div>  
                <div class="record-container">
                  
                  <!-- <div style="background-color: #FF8C00;" class="record-box">
                    <p>Record: <?= $record ?></p>
                  </div> -->
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
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php 

                        $no = 1;
                        
                        $no++;
                        endforeach;
                        ?>    
                       
                    </table>
                


                </div>
            </div>
        </form>
    </div>
</div>
</body>

</html>

