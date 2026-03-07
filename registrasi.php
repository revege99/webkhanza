<?php 

require 'function/function.php';



$tanggal_awal= date("Y-m-d");
$tanggal_akhir = date("Y-m-d");
$registrasi = query("SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.tmp_lahir, pasien.tgl_lahir, reg_periksa.almt_pj, pasien.no_ktp, pasien.no_tlp
FROM reg_periksa 
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'");
// $daftar = mysqli_query($conn, $registrasi);

$record = count($registrasi);

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];



$registrasi = query("SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.tmp_lahir, pasien.tgl_lahir, reg_periksa.almt_pj, pasien.no_ktp, pasien.no_tlp
FROM reg_periksa 
INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'");
// $daftar = mysqli_query($conn, $registrasi);

$record = count($registrasi);
// var_dump($record);
// die();
}



if (isset($_POST['export'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=data_pasien.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>
            <th>Tanggal</th>
            <th>No. RM</th>
            <th>Nama Pasien</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Alamat</th>
            <th>NIK</th>
            <th>No. HP</th>
          </tr>";

$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];

   $sql = "SELECT reg_periksa.tgl_registrasi, reg_periksa.no_rkm_medis, 
                   pasien.nm_pasien, pasien.tmp_lahir, pasien.tgl_lahir, 
                   reg_periksa.almt_pj, pasien.no_ktp, pasien.no_tlp
            FROM reg_periksa 
            INNER JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";

    $exportResult = mysqli_query($conn, $sql);
    
    while ($row = mysqli_fetch_assoc($exportResult)) {
        echo "<tr>
                <td>{$row['tgl_registrasi']}</td>
                <td>{$row['no_rkm_medis']}</td>
                <td>{$row['nm_pasien']}</td>
                <td>{$row['tmp_lahir']}</td>
                <td>{$row['tgl_lahir']}</td>
                <td>{$row['almt_pj']}</td>
                <td style='mso-number-format:\"@\"'>{$row['no_ktp']}</td> <!-- Format NIK sebagai teks -->
                <td style='mso-number-format:\"@\"'>{$row['no_tlp']}</td> <!-- Format No. HP sebagai teks -->
              </tr>";
    }
    echo "</table>";
    exit();
}
?>


<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> Klinik St. Lusia Siborongborong</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" type="text/css" href="style/registrasi.css">
    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
  	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  	<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    
  </head>
  <body>


    <div class="sidebar">
      <div class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion">
        <div>
          <h2>RSSL</h2>
        </div>


           <div class="dropdown">
            <a class="btn dropdown-toggle-icon" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-house-fill"></i> Home <i class="bi bi-chevron-right chevron-icon"></i>
            </a>

            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action</a></li>
                <li><a class="dropdown-item" href="#">Another action</a></li>
                <li><a class="dropdown-item" href="#">Something else here</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <a class="btn dropdown-toggle-icon" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-house-fill"></i> Home <i class="bi bi-chevron-right chevron-icon"></i>
            </a>

            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action</a></li>
                <li><a class="dropdown-item" href="#">Another action</a></li>
                <li><a class="dropdown-item" href="#">Something else here</a></li>
            </ul>
        </div>    
        </div>
      </div>
      
      <div class="content">
        <nav class="navbar navbar-expand-md fixed-top">
      	<div class="container">
          
          <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
            <div class="input-group">
              <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
              <div class="input-group-append">
                <button class="btn btn-primary" type="button">
                  <i class="fas fa-search fa-sm"></i>
                </button>
              </div>
            </div>
          </form>
          

      </div>
    </nav>

    <div class="container-fluid" id="container-fitur">
    <form class="row gy-2 gx-3 align-items-center" action="" method="post">
      <div class="row" id="fitur">
      <div class="col-auto">
        <label class="visually-hidden" for="autoSizingInputGroup">Username</label>
        <div class="input-group">
          <div class="input-group-text">Tanggal Awal</div>
          <input type="date" value="<?=$tanggal_awal; ?>" class="form-control" id="autoSizingInputGroup" placeholder="Username" name="tanggal_awal">
        </div>
      </div>
      <div class="col-auto ">
        <label class="visually-hidden" for="autoSizingInputGroup">Username</label>
        <div class="input-group">
          <div class="input-group-text">Tanggal Akhir</div>
          <input type="date" class="form-control" id="autoSizingInputGroup" value="<?=$tanggal_akhir; ?>" name="tanggal_akhir">
        </div>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary" name="submit">Cari</button>
      </div>
      <div class="col-auto">
      <form class="export" method="post">
        <button class="btn btn-warning" id="export" type="submit" name="export">Export ke Excel</button>
      </form>
      </div>
      <div class="col-auto" id="record">
        <p>Record : <?=$record ?></p>
      </div>
    </form>
    </div>
    </div>

      <?php $i = 1 ?>
      <div class="container-fluid" id="data" >
        <div class="row" id="table-container">
          <table border="1" cellpadding="10" id="registrasi" width="500">
            <tr >
              <th>No</th>
              <th style="width: 150px;">Tanggal</th>
              <th style="width : 100px">No. RM</th>
              <th style="width : 200px">Nama Pasien</th>
              <th style="">Tempat Lahir</th>
              <th>Tanggal Lahir</th>
              <th>Alamat</th>
              <th>NIK</th>
              <th>No. HP</th>
            </tr>
            <?php foreach ($registrasi as $regis) : ?>
            <tr>
              <td style="text-align: center;"><?= $i; ?></td>
              <td style="text-align: center;"><?=$regis['tgl_registrasi'] ?></td>
              <td><?=$regis['no_rkm_medis'] ?></td>
              <td><?=$regis['nm_pasien'] ?></td>
              <td><?=$regis['tmp_lahir'] ?></td>
              <td><?=$regis['tgl_lahir'] ?></td>
              <td><?=$regis['almt_pj'] ?></td>
              <td><?=$regis['no_ktp'] ?></td>
              <td><?=$regis['no_tlp'] ?></td>
              <?php $i++; ?>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>


   <script>
    // Ambil semua elemen dropdown
    let dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        let toggleButton = dropdown.querySelector('.dropdown-toggle-icon');
        let chevronIcon = dropdown.querySelector('.chevron-icon');

        // Tambahkan event listener untuk toggle dropdown
        toggleButton.addEventListener('click', function () {
            setTimeout(() => {
                if (toggleButton.getAttribute("aria-expanded") === "true") {
                    chevronIcon.classList.remove("bi-chevron-right");
                    chevronIcon.classList.add("bi-chevron-down");
                } else {
                    chevronIcon.classList.remove("bi-chevron-down");
                    chevronIcon.classList.add("bi-chevron-right");
                }
            }, 50);
        });
    });
</script>
  </body>
</html>