<?php 
  require 'function/function.php';
  // include 'views/sidebar.php';

$tanggal_awal= date("Y-m-d");
$tanggal_akhir = date("Y-m-d");

$cari_ekg = query("
 SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  pasien.nm_pasien, 
  rawat_jl_dr.kd_jenis_prw, 
  pasien.alamat, 
  jns_perawatan.nm_perawatan, 
  poliklinik.nm_poli 
  FROM rawat_jl_dr LEFT JOIN reg_periksa on rawat_jl_dr.no_rawat = reg_periksa.no_rawat LEFT JOIN pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis LEFT JOIN jns_perawatan ON rawat_jl_dr.kd_jenis_prw = jns_perawatan.kd_jenis_prw LEFT JOIN poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli 
  WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
  AND rawat_jl_dr.kd_jenis_prw = 'RJ00123';
  ");


$record = count($cari_ekg);

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];

$cari_ekg = query("
  SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.no_rkm_medis, 
  pasien.nm_pasien, 
  rawat_jl_dr.kd_jenis_prw, 
  pasien.alamat, 
  jns_perawatan.nm_perawatan, 
  poliklinik.nm_poli 
  FROM rawat_jl_dr LEFT JOIN reg_periksa on rawat_jl_dr.no_rawat = reg_periksa.no_rawat LEFT JOIN pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis LEFT JOIN jns_perawatan ON rawat_jl_dr.kd_jenis_prw = jns_perawatan.kd_jenis_prw LEFT JOIN poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli 
  WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
  AND rawat_jl_dr.kd_jenis_prw = 'RJ00123';
  ");

  $record = count($cari_ekg);

}



// if (isset($_POST['export'])) {
//     header("Content-Type: application/vnd.ms-excel");
//     header("Content-Disposition: attachment; filename=data_pasien.xls");
//     header("Pragma: no-cache");
//     header("Expires: 0");

//     echo "<table border='1'>";
//     echo "<tr>
//             <th>Tanggal</th>
//             <th>No. RM</th>
//             <th>Nama Pasien</th>
//             <th>Tempat Lahir</th>
//             <th>Tanggal Lahir</th>
//             <th>Alamat</th>
//             <th>NIK</th>
//             <th>No. HP</th>
//           </tr>";

// $tanggal_awal = $_POST['tanggal_awal'];
// $tanggal_akhir = $_POST['tanggal_akhir'];

//    $sql = "SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rkm_medis, 
//                    pasien.nm_pasien, pasien.tmp_lahir, pasien.tgl_lahir, 
//                    reg_periksa.almt_pj, pasien.no_ktp, pasien.no_tlp
//             FROM reg_periksa 
//             INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
//             WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";

//     $exportResult = mysqli_query($conn, $sql);
    
//     while ($row = mysqli_fetch_assoc($exportResult)) {
//         echo "<tr>
//                 <td>{$row['tgl_registrasi']}</td>
//                 <td>{$row['no_rkm_medis']}</td>
//                 <td>{$row['nm_pasien']}</td>
//                 <td>{$row['tmp_lahir']}</td>
//                 <td>{$row['tgl_lahir']}</td>
//                 <td>{$row['almt_pj']}</td>
//                 <td style='mso-number-format:\"@\"'>{$row['no_ktp']}</td> <!-- Format NIK sebagai teks -->
//                 <td style='mso-number-format:\"@\"'>{$row['no_tlp']}</td> <!-- Format No. HP sebagai teks -->
//               </tr>";
//     }
//     echo "</table>";
//     exit();
// }
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

    
  </head>
  <body>


      
      <div class="content">
        <nav class="navbar fixed-top">
        <div class="container">


          <div class="d-flex align-items-center">
            <h2>Pasien dengan Tindakan EKG</h2>
            <!-- <form class="d-flex">
                <input class="form-control" type="search" placeholder="Search for..." aria-label="Search">
                <button class="btn btn-primary ml-2" type="submit">
                     <i class="fas fa-search fa-sm"></i>
                </button>
              </form> -->
          </div>  
          <!-- <div class="d-flex align-items-center row topbar-divider ">
          <h3 class="mr-3">Pasien Anak</h3>
          <form class="d-flex d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" >
              <div class="input-group">
                <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                <div class="input-group-append">
                  <button class="btn btn-primary" type="button">
                    <i class="fas fa-search fa-sm"></i>
                  </button>
                </div>
                
            </div>
          </form>
          </div> -->
          

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

      <?php $i = 1 ?>
      <div class="container-fluid" id="data" >
        <div class="row" id="table-container">
          <table border="1" cellpadding="5" id="registrasi" width="500">
            <tr >
              <th >No</th>
              <th >Tanggal Registrasi</th>
              <th >No. RM</th>
              <th >Nama Pasien</th>
              <th >Alamat</th>
              <th>Tindakan</th>
              <th>Unit</th>
              
            </tr>
            <?php foreach ($cari_ekg as $row) : ?>
            <tr>
              <td style="text-align: center;"><?= $i; ?></td>
              <td style="text-align: center;"><?=$row['tgl_registrasi'] ?></td>
              <td style="text-align: center;"><?=$row['no_rkm_medis'] ?></td>
              <td><?=$row['nm_pasien'] ?></td>           
              <td><?=$row['alamat'] ?></td>
              <td style="text-align: center;"><?=$row['nm_perawatan'] ?></td>
              <td style="text-align: center;"><?=$row['nm_poli'] ?></td>


              
              <?php $i++; ?>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    </div>


     

  </body>
</html>