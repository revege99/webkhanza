<?php 
  require 'function/function.php';
  // include 'views/sidebar.php';


// rujuk bedah
//69 ambulance
$pasien_rujuk_bedah = query("
SELECT 
    rj.no_rawat, 
    p.nm_pasien,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
        THEN 'pasien_rujuk_nonbedah'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_rujuk_bedah';
");


$count_pasien_rujuk_bedah = 0;

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];

$pasien_rujuk_bedah = query("
SELECT 
     rj.no_rawat, 
    p.nm_pasien,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_nonbedah'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_rujuk_bedah';
  ");

$count_pasien_rujuk_bedah = count($pasien_rujuk_bedah);

}

// rujuk non_bedah
//69 ambulance
$rujuk_non_Bedah = query("
SELECT 
    rj.no_rawat, 
    p.nm_pasien,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
        THEN 'pasien_rujuk_nonbedah'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_rujuk_nonbedah';
");


$count_pasien_rujuk_nonbedah = 0;

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];

$rujuk_non_Bedah = query("
SELECT 
     rj.no_rawat, 
    p.nm_pasien,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
        THEN 'pasien_rujuk_nonbedah'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_rujuk_nonbedah';
  ");

$count_pasien_rujuk_nonbedah = count($rujuk_non_Bedah);

}


// lukaluka P
//69 ambulance
$pasien_luka_p = query("
SELECT 
    rj.no_rawat, 
    p.nm_pasien,
    p.jk,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_luka_p'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26' AND p.jk = 'P'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_luka_p';
");


$count_pasien_luka_p = 0;

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];

$pasien_luka_p = query("
SELECT 
    rj.no_rawat, 
    p.nm_pasien,
    p.jk,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_luka_p'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26' AND p.jk = 'P'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_luka_p';
  ");

$count_pasien_luka_p = count($pasien_luka_p);

}


// lukaluka L
//69 ambulance
$pasien_luka_l = query("
SELECT 
    rj.no_rawat, 
    p.nm_pasien,
    p.jk,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_luka_l'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26' AND p.jk = 'L'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_luka_l';
");


$count_pasien_luka_l = 0;

if ( isset($_POST['submit'] )){
$tanggal_awal = $_POST['tanggal_awal'];
$tanggal_akhir = $_POST['tanggal_akhir'];

$pasien_luka_l = query("
SELECT 
    rj.no_rawat, 
    p.nm_pasien,
    p.jk,
    CASE 
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24569' THEN 1 END) > 0 
             AND COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_rujuk_bedah'
        
        WHEN COUNT(CASE WHEN rj.kd_jenis_prw = 'RJ24570' THEN 1 END) > 0 
        THEN 'pasien_luka_l'
        
        ELSE NULL  
    END AS kategori
FROM rawat_jl_dr rj
INNER JOIN reg_periksa rp ON rj.no_rawat = rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.tgl_registrasi BETWEEN '2025-02-26' AND '2025-02-26' AND p.jk = 'L'
GROUP BY rp.no_rkm_medis, p.nm_pasien
HAVING kategori = 'pasien_luka_l';
  ");

$count_pasien_luka_l = count($pasien_luka_l);

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
            <h2>Report IGD </h2>
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
        <label class="visually-hidden" for="autoSizingInputGroup">Username</label>
        <div class="input-group">
          <div class="input-group-text">Poli</div>
          
              <select name="status" id="status" class="status">
                <option id="select" selected><?=$poli; ?></option>
                <option value="Plum">Poliklinik Umum</option>
                <option value="Far">Farmasi Beli Obat</option>
              </select>
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
              <th>Jenis Pelayanan</th>
              <th >Pasien Rujukan Non Bedah</th>
              <th >Pasien Rujukan Bedah</th>
              <th >Tindak Lanjut Pelayanan Dirawat</th>
              <th >Tindak Lanjut Pelayanan Dirujuk</th>
              <th>Tindak Lanjut Pelayanan Pulang</th>
              <th>Mati Di IGD (L) </th>
              <th>Mati Di IGD (P) </th>
              <th>Doa (P)</th>
              <th>Doa (L)</th>
              <th>Luka Luka (L)</th>
              <th>Luka Luka (P)</th>
              
            </tr>
            <tr>
              <td>1</td>
              <td>Kecelakaan Lalulintas Darat</td>
              <td><?= $count_pasien_rujuk_nonbedah ;?></td>
              <td><?=$count_pasien_rujuk_bedah ?></td>
              <td>null</td>
              <td>null</td>
              <td>null</td>
              <td>null</td>
              <td>null</td>
              <td>null</td>
              <td>null</td>
              <td><?= $count_pasien_luka_l ?></td>
              <td><?=$count_pasien_luka_p ?></td>
            </tr>
            <tr>
              <td>2</td>
              <td>Kecelakaan Lalulintas Perairan</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>3</td>
              <td>Kecelakaan Lalulintas Udara</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>4</td>
              <td>Bedah Lainnya</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>5</td>
              <td>Kekerasan Terhadap Perempuan</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>6</td>
              <td>Kekerasan Terhadap Anak</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>7</td>
              <td>Kekerasan Lainnya</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>

             <tr>
              <td>8</td>
              <td>Non Bedah Lainnya</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>

             <tr>
              <td>9</td>
              <td>Kebidanan</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>10</td>
              <td>Psikiatrik</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
             <tr>
              <td>11</td>
              <td>Bayi</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
            <tr>
              <td>12</td>
              <td>Anak</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
            </tr>
        </table>
      </div>
    </div>

    </div>


     

  </body>
</html>