<?php 
  require 'function/function.php';
$tanggal_awal= date("Y-m-d");
$tanggal_akhir = date("Y-m-d");
$registrasi = query("
  SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.jam_reg, 
  pasien.nm_pasien, 
  reg_periksa.p_jawab, 
  reg_periksa.no_rkm_medis, 
  CASE WHEN pasien.keluarga = 'DIRI SENDIRI' THEN'✓' ELSE'' END as 'Datang Sendiri',
  pasien.alamat, 
  pasien.pekerjaan, 
  pasien.pekerjaan, 
  pasien.agama, pasien.pnd, 
  pasien.tmp_lahir, 
  pasien.tgl_lahir, 
  CONCAT(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, CURDATE()), ' Tahun') AS 'USIA',
  pasien.jk, 
  CASE WHEN pasien.jk = 'L' THEN'✓' ELSE'' END as 'L',
  CASE WHEN pasien.jk = 'P' THEN'✓' ELSE'' END as 'P',
  CASE WHEN reg_periksa.status_poli = 'Baru' THEN'✓' ELSE'' END as 'Baru',
  CASE WHEN reg_periksa.status_poli = 'Lama' THEN'✓' ELSE'' END as 'Lama',
  reg_periksa.kd_poli, 
  poliklinik.nm_poli, 
  reg_periksa.kd_pj,
  CASE WHEN penjab.png_jawab = 'Umum' THEN'✓' ELSE'' END AS 'Umum', 
  CASE WHEN penjab.png_jawab = 'BPJS' THEN'✓' ELSE'' END AS 'JKN', 
  CASE WHEN penjab.png_jawab = 'Mandiri Inhealth' THEN'✓' ELSE'' END AS 'MI', 
  CASE WHEN penjab.png_jawab = 'Jasa Raharja' THEN'✓' ELSE'' END AS 'Jasa Raharja', 
  CASE WHEN penjab.png_jawab = 'Medika Plaza' THEN'✓' ELSE'' END AS 'Medika Plaza', 
  CASE WHEN penjab.png_jawab = 'Angkasa Pura' THEN'✓' ELSE'' END AS 'Angkasa Pura', 
  
  reg_periksa.kd_dokter, 
  dokter.nm_dokter
FROM reg_periksa
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab on reg_periksa.kd_pj = penjab.kd_pj
LEFT JOIN dokter on reg_periksa.kd_dokter = dokter.kd_dokter
WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
AND reg_periksa.status_lanjut !='ranap'
  ");

$record = count($registrasi);

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];
$registrasi = query("
  SELECT 
  reg_periksa.tgl_registrasi, 
  reg_periksa.jam_reg, 
  pasien.nm_pasien, 
  reg_periksa.p_jawab, 
  reg_periksa.no_rkm_medis,
  CASE WHEN pasien.keluarga = 'DIRI SENDIRI' THEN'✓' ELSE'' END as 'Datang Sendiri',
  pasien.alamat, 
  pasien.pekerjaan, 
  pasien.pekerjaan, 
  pasien.agama, 
  pasien.pnd, 
  pasien.tmp_lahir, 
  pasien.tgl_lahir, 
  CONCAT(TIMESTAMPDIFF(YEAR, pasien.tgl_lahir, CURDATE()), ' Tahun') AS 'USIA',
  pasien.jk, 
  CASE WHEN pasien.jk = 'L' THEN'✓' ELSE'' END as 'L',
  CASE WHEN pasien.jk = 'P' THEN'✓' ELSE'' END as 'P',
  CASE WHEN reg_periksa.status_poli = 'Baru' THEN'✓' ELSE'' END as 'Baru',
  CASE WHEN reg_periksa.status_poli = 'Lama' THEN'✓' ELSE'' END as 'Lama',
  reg_periksa.kd_poli, 
  poliklinik.nm_poli, 
  reg_periksa.kd_pj, 
  CASE WHEN penjab.png_jawab = 'Umum' THEN'✓' ELSE'' END AS 'Umum', 
  CASE WHEN penjab.png_jawab = 'BPJS' THEN'✓' ELSE'' END AS 'JKN', 
  CASE WHEN penjab.png_jawab = 'Mandiri Inhealth' THEN'✓' ELSE'' END AS 'MI', 
  CASE WHEN penjab.png_jawab = 'Jasa Raharja' THEN'✓' ELSE'' END AS 'Jasa Raharja', 
  CASE WHEN penjab.png_jawab = 'Medika Plaza' THEN'✓' ELSE'' END AS 'Medika Plaza', 
  CASE WHEN penjab.png_jawab = 'Angkasa Pura' THEN'✓' ELSE'' END AS 'Angkasa Pura',  
  reg_periksa.kd_dokter, 
  dokter.nm_dokter
FROM reg_periksa
LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
LEFT JOIN poliklinik ON reg_periksa.kd_poli = poliklinik.kd_poli
LEFT JOIN penjab on reg_periksa.kd_pj = penjab.kd_pj
LEFT JOIN dokter on reg_periksa.kd_dokter = dokter.kd_dokter
WHERE reg_periksa.tgl_registrasi BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
AND reg_periksa.status_lanjut !='ranap'
  ");
// $daftar = mysqli_query($conn, $registrasi);
// $tanggal_lahir = $registrasi[0]['tgl_lahir'];

// $tanggal_lahir_format = date("d-m-Y", strtotime($tanggal_lahir));
// var_dump($tanggal_lahir_format);
// die();
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
            <?php if (isset($_POST['status'])){
              $status = $_POST['status'];

              if ($status == "Plum")  {
                echo '<h2>Pasien Dewasa Umum</h2>';
              }elseif ($status == "Far") {      
                echo '<h2>Pasien Anak BPJS</h2>';
              }else{
                echo '<h2 class="bg-danger">Pilih unit pasien</h2>';
              }

            } ?>
           
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
              <th rowspan="2" >No</th>
              <th rowspan="2" >Tanggal Berobat</th>
              <th rowspan="2" >Pukul</th>
              <th rowspan="2" >Nama Pasien</th>
              <th rowspan="2" >Nama Penanggung Jawab</th>
              <th rowspan="2" >No.RM</th>
              <th rowspan="2" >Datang Sendiri</th>
              <th rowspan="2" >Dikirim Oleh dr/dr Spesialis</th>
              <th rowspan="2" >Rujukan Puskesmas</th>
              <th rowspan="2" >Rujukan RS</th>
              <th rowspan="2" >Konsul R. Jalan</th>
              <th rowspan="2" >Lainnya</th>
              <th rowspan="2" >Alamat</th>
              <th rowspan="2" >Pekerjaan</th>
              <th rowspan="2" >Agama</th>
              <th rowspan="2" >Pendidikan</th>
              <th rowspan="2">Tempat Tanggal Lahir</th>
              <th rowspan="2">USIA</th>
              <th colspan="2">Jenis Kelamin</th>
              <th colspan="2">Pasien</th>
              <th rowspan="2">Poli Tujuan</th>
              <th rowspan="2">Diagnosa</th>
              <th rowspan="2">ICD X</th>
              <th rowspan="2">Dirujuk</th>
              <th rowspan="2">Pulang</th>
              <th rowspan="2">Meninggal</th>
              <th rowspan="2">PAPS</th>
              <th colspan="6">Jenis Bayar</th>
              <th rowspan="2">DPJP</th>
              <th rowspan="2">Tindakan Keperawatan</th>
              <th rowspan="2">Keterangan</th>
              
            </tr>
            <tr> 
              <th>L</th>
              <th>P</th>
              <th>Baru</th>
              <th>Lama</th>
              <th>Umum</th>
              <th>JKN</th>
              <th>MI</th>
              <th>Jasa Raharja</th>
              <th>Medika Plaza</th>
              <th>Angkasa Pura</th>
          </tr>
            <?php foreach ($registrasi as $row) : ?>
            <tr>
              <td style="text-align: center;"><?= $i; ?></td>
              <td style="text-align: center;"><?=$row['tgl_registrasi'] ?></td>
              <td><?=$row['jam_reg'] ?></td>
              <td><?=$row['nm_pasien'] ?></td>
              <td><?=$row['p_jawab'] ?></td>
              <td><?=$row['no_rkm_medis'] ?></td>
              <td style="text-align: center;"><?=$row['Datang Sendiri'] ?></td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td><?= ucwords(strtolower($row['alamat']))  ?></td>
              <td><?=$row['pekerjaan'] ?></td>
              <td><?=$row['agama'] ?></td>
              <td><?=$row['pnd'] ?></td>
              <td><?=$row['tmp_lahir'] ?>, <?= date("d-m-Y", strtotime($row['tgl_lahir'])) ?></td>
              <td style="text-align: center;"><?=$row['USIA'] ?></td>
              <td style="text-align: center;"><?=$row['L'] ?></td>
              <td style="text-align: center;"><?=$row['P'] ?></td>
              <td style="text-align: center;"><?=$row['Baru'] ?></td>
              <td style="text-align: center;"><?=$row['Lama'] ?></td>
              <td style="text-align: center;"><?= ucwords(strtolower($row['nm_poli'])) ?></td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td style="text-align: center;"><?=$row['Umum'] ?></td>
              <td style="text-align: center;"><?=$row['JKN'] ?></td>
              <td style="text-align: center;"><?=$row['MI'] ?></td>
              <td style="text-align: center;"><?=$row['Jasa Raharja'] ?></td>
              <td style="text-align: center;"><?=$row['Medika Plaza'] ?></td>
              <td style="text-align: center;"><?=$row['Angkasa Pura'] ?></td>
              <td style="text-align: center;"><?=$row['nm_dokter'] ?></td>
              <td style="text-align: center;">-</td>
              <td>-</td>
              
              <?php $i++; ?>
          <?php endforeach; ?>
        </table>
      </div>
    </div>

    </div>


     

  </body>
</html>