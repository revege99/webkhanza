<?php 
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

$function_path = __DIR__ . '/../function/function.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

// Default tanggal awal & akhir
$tanggal_awal = date("Y-m-d");
$tanggal_akhir = date("Y-m-d");

// var_dump($tanggal_awal, $tanggal_akhir);
// die();


if (isset($_POST['submit'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
}

$cari_anak_umum = "
SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  reg_periksa.no_rawat,
  pasien.nm_pasien, 
  penjab.png_jawab,
  poliklinik.nm_poli,
  pasien.umur, 
  reg_periksa.almt_pj, 
  pasien.no_ktp
FROM reg_periksa 
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
WHERE 
  reg_periksa.kd_poli IN ('IGDK', 'UMUM') 
  AND reg_periksa.status_lanjut = 'Ralan'
  AND reg_periksa.biaya_reg != 0
  AND penjab.png_jawab = 'UMUM'
  AND pasien.umur <= 17
  AND DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?";


$pasien_anak_umum = queryPrepared($cari_anak_umum, [$tanggal_awal, $tanggal_akhir]);
$record_anak_umum = count($pasien_anak_umum);

$cari_anak_bpjs = "
SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  reg_periksa.no_rawat,
  pasien.nm_pasien, 
  penjab.png_jawab,
  poliklinik.nm_poli,
  pasien.umur, 
  reg_periksa.almt_pj, 
  pasien.no_ktp
FROM reg_periksa 
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
WHERE 
  reg_periksa.kd_poli IN ('IGDK', 'UMUM') 
  AND reg_periksa.status_lanjut = 'Ralan'
  AND reg_periksa.biaya_reg != 0
  AND penjab.png_jawab = 'BPJS'
  AND pasien.umur <= 17
  AND DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?";

$pasien_anak_bpjs = queryPrepared($cari_anak_bpjs, [$tanggal_awal, $tanggal_akhir]);
$record_anak_bpjs = count($pasien_anak_bpjs);

$cari_dewasa_umum = " 
  SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  reg_periksa.no_rawat,
  pasien.nm_pasien, 
  penjab.png_jawab,
  poliklinik.nm_poli,
  pasien.umur, 
  reg_periksa.almt_pj, 
  pasien.no_ktp
FROM reg_periksa 
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
WHERE 
  reg_periksa.kd_poli IN ('IGDK', 'UMUM') 
  AND reg_periksa.status_lanjut = 'Ralan'
  AND reg_periksa.biaya_reg != 0
  AND penjab.png_jawab = 'UMUM'
  AND pasien.umur > 17
  AND DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?
";
$pasien_dewasa_umum = queryPrepared($cari_dewasa_umum, [$tanggal_awal, $tanggal_akhir]);
$record_dewasa_umum = count($pasien_dewasa_umum);


$cari_dewasa_BPJS = " 
  SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  reg_periksa.no_rawat,
  pasien.nm_pasien, 
  penjab.png_jawab,
  poliklinik.nm_poli,
  pasien.umur, 
  reg_periksa.almt_pj, 
  pasien.no_ktp
FROM reg_periksa 
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
WHERE 
  reg_periksa.kd_poli IN ('IGDK', 'UMUM') 
  AND reg_periksa.status_lanjut = 'Ralan'
  AND reg_periksa.biaya_reg != 0
  AND penjab.png_jawab = 'BPJS'
  AND pasien.umur > 17
  AND DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?
";
$pasien_dewasa_BPJS = queryPrepared($cari_dewasa_BPJS, [$tanggal_awal, $tanggal_akhir]);
$record_dewasa_BPJS = count($pasien_dewasa_BPJS);


$cari_ekg = " 
  SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  pasien.nm_pasien, 
  rawat_jl_dr.kd_jenis_prw, 
  pasien.alamat, 
  jns_perawatan.nm_perawatan, 
  poliklinik.nm_poli 
  FROM rawat_jl_dr 
  LEFT JOIN reg_periksa on rawat_jl_dr.no_rawat = reg_periksa.no_rawat 
  LEFT JOIN pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis 
  LEFT JOIN jns_perawatan ON rawat_jl_dr.kd_jenis_prw = jns_perawatan.kd_jenis_prw 
  LEFT JOIN poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli 
  WHERE rawat_jl_dr.kd_jenis_prw = 'RJ00123'
  AND DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?
";
$pasien_ekg = queryPrepared($cari_ekg, [$tanggal_awal, $tanggal_akhir]);
$record_ekg = count($pasien_ekg);

$cari_rujuk = " 
  SELECT 
  reg_periksa.no_rawat,
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  pasien.nm_pasien, 
  pasien.umur,
  poliklinik.nm_poli,
  jns_perawatan.nm_perawatan
FROM reg_periksa 
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab ON reg_periksa.kd_pj = penjab.kd_pj
LEFT JOIN rawat_jl_dr ON reg_periksa.no_rawat = rawat_jl_dr.no_rawat
LEFT JOIN jns_perawatan ON rawat_jl_dr.kd_jenis_prw = jns_perawatan.kd_jenis_prw
WHERE 
  reg_periksa.kd_poli IN ('IGDK', 'UMUM') 
  AND reg_periksa.status_lanjut = 'Ralan'
  AND reg_periksa.biaya_reg != 0
  AND jns_perawatan.nm_perawatan LIKE '%SBB%'
  AND DATE(reg_periksa.tgl_registrasi) BETWEEN ? AND ?
  ORDER BY reg_periksa.no_rawat, jns_perawatan.nm_perawatan
";
$pasien_rujuk = queryPrepared($cari_rujuk, [$tanggal_awal, $tanggal_akhir]);
$record_rujuk = count($pasien_rujuk);

?>



<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Klinik St. Lusia Siborongborong</title>

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
      td {
        border: 1px solid black;
      }
      th {
        border: 1px solid black;
        background-color: skyblue;
        text-align: center;
        font-weight: bold;
      }
      #record{
        text-align: center;
      }
    </style>

    
  </head>
  <body>


      
      <div class="content">
        <nav class="navbar fixed-top">
        <div class="container">


          <div class="d-flex align-items-center">
            <h2>Report IGD RSSL</h2>
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
            <label class="visually-hidden" for="autoSizingInputGroup">Username</label>
            <div class="input-group">
              <div class="input-group-text">Tanggal Akhir</div>
              <input  type="date" class="form-control" id="autoSizingInputGroup" value="<?=$tanggal_akhir; ?>" name="tanggal_akhir" >
            </div>
          </div>
         
          <div class="col-auto">
            <button type="submit" class="btn btn-primary" name="submit">Cari</button>
          </div>
        </form>
    </div>
    </div>
      <div style="margin-top : 5rem" class="container" >
        <table border="1" cellpadding="10" cellspacing="5">
          <tr style="background-color: grey;">
            <th>Kategori</th>
            <th>Jumlah</th>
          </tr>
          <tr style="border:1px solid black;">
            <td>Pasien anak Umum</td>
            <td id="record"><?=$record_anak_umum ?></td>
          </tr>
          <tr>
            <td>Pasien Anak BPJS</td>
            <td id="record"><?=$record_anak_bpjs ?></td>
          </tr>
          <tr>
            <td>Pasien Dewasa umum</td>
            <td id="record"><?=$record_dewasa_umum ?></td>
          </tr>
          <tr>
            <td>Jumlah Dewasa BPJS</td>
            <td id="record"><?=$record_dewasa_BPJS ?></td>
          </tr>
          <tr>
            <td>Jumlah Pasien EKG</td>
            <td id="record"><?=$record_ekg ?></td>
          </tr>
          <tr>
            <td>Jumlah Pasien Rujuk</td>
            <td id="record"><?=$record_rujuk ?></td>
          </tr>
        </table>
        <p style="color: red; font-weight : bold;"><br> Data IGD Per Tanggal
          <br> <?= date('d-m-Y', strtotime($tanggal_awal)) ?> Sampai Dengan <?= date('d-m-Y', strtotime($tanggal_akhir)) ?>
          <br> SEMANGAT KAMU YAA !!!</p>

        
      </div>
    </div>

  </body>
</html>