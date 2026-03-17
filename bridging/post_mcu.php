<?php 


$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}
$noKartu = $_GET['noKartu'];
$noKunj = $_GET['noKunj'];


$cariKunjungan = "
SELECT *
FROM pcare_kunjungan_umum
WHERE noKunjungan = ?
";

$Kunjungan = queryPrepared($cariKunjungan, [$noKunj]);
$record = count($Kunjungan);


?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> RSSL </title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <!-- fontawesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" type="text/css" href="report/css/mcu.css">
   
    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <style type="text/css">
      input{
        height: 30px;

      }
      .form-control {
        font-size: 14px;
    }

      label{
        font-size: 14px;
      }
      h5 {
        font-weight: bold;
        background-color: grey;
      }
    </style>


  </head>
  <body> 
    <?php foreach ($Kunjungan as $dataKunj) : ?>
      <div class="content">
        <nav class="navbar fixed-top">
            <div class="container">
                <div class="d-flex align-items-center">
                    <h3 style="color : grey;">MCU <?=$dataKunj['nm_pasien'] ?></h3>
                </div>  
            </div>  
        </nav>
        <form class="input" style="margin-top:5rem; margin-left: 4rem;" method="post" action="?page=proses_post_mcu">
          <div class="container" id="content">
            <input type="hidden" id="noKartu" value="<?= htmlspecialchars($noKartu) ?>">


            <div class="row" style="">
       
              <div class="col-md-3 mb-3">
                <label class="form-label">kdMCU</label>
                <input type="text" name="kdMCU" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">noKunjungan</label>
                <input type="text" name="noKunjungan" class="form-control" value="<?=$dataKunj['noKunjungan']?>">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">kdProvider</label>
                <input type="text" name="kdProvider" id="kdProvider" class="form-control">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">tglPelayanan</label>
                <input type="text" name="tglPelayanan" class="form-control" 
              value="<?= date('d-m-Y', strtotime($dataKunj['tglDaftar'])) ?>">

              </div>
            </div>






            <div class="row">
              <h5>Pemeriksaan Fisik</h5>
              <div class="col-md-3 mb-3">
                <label>tekananDarahSistole</label>
                <input type="text" name="tekananDarahSistole" class="form-control" value="<?=$dataKunj['sistole']?>">
              </div>
              <div class="col-md-3 mb-3">
                <label>tekananDarahDiastole</label>
                <input type="text" name="tekananDarahDiastole" class="form-control" value="<?=$dataKunj['diastole']?>">
              </div>
            </div>

            <div class="row">
              <h5>Pemeriksaan Darah Rutin</h5>
              <div class="col-md-3 mb-3">
                <label>darahRutinHemo</label>
                <input type="text" name="darahRutinHemo" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>darahRutinLeu</label>
                <input type="text" name="darahRutinLeu" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>darahRutinErit</label>
                <input type="text" name="darahRutinErit" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>darahRutinLaju</label>
                <input type="text" name="darahRutinLaju" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>darahRutinHema</label>
                <input type="text" name="darahRutinHema" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>darahRutinTrom</label>
                <input type="text" name="darahRutinTrom" class="form-control" value="0">
              </div>
            </div>

            <div class="row">
              <h5>Profil Lipil</h5>
              <div class="col-md-3 mb-3">
                <label>lemakDarahHDL</label>
                <input type="text" name="lemakDarahHDL" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>lemakDarahLDL</label>
                <input type="text" name="lemakDarahLDL" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>lemakDarahChol</label>
                <input type="text" name="lemakDarahChol" class="form-control" value="0">
              </div>
               <div class="col-md-3 mb-3">
                <label>lemakDarahTrigli</label>
                <input type="text" name="lemakDarahTrigli" class="form-control" value="0">
              </div>
            </div>

            <div class="row">
              <h5>Gula Darah</h5>
              <div class="col-md-3 mb-3">
                <label>gulaDarahSewaktu</label>
                <input type="text" name="gulaDarahSewaktu" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>gulaDarahPuasa</label>
                <input type="text" name="gulaDarahPuasa" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>gulaDarahPostPrandial</label>
                <input type="text" name="gulaDarahPostPrandial" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>gulaDarahHbA1c</label>
                <input type="text" name="gulaDarahHbA1c" class="form-control" value="0">
              </div>
            </div>

            

            <div class="row">
              <h5>Fungsi Hati</h5>
              <div class="col-md-3 mb-3">
                <label>fungsiHatiSGOT</label>
                <input type="text" name="fungsiHatiSGOT" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiHatiSGPT</label>
                <input type="text" name="fungsiHatiSGPT" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiHatiGamma</label>
                <input type="text" name="fungsiHatiGamma" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiHatiProtKual</label>
                <input type="text" name="fungsiHatiProtKual" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiHatiAlbumin</label>
                <input type="text" name="fungsiHatiAlbumin" class="form-control" value="0">
              </div>
            </div>

            <div class="row">
              <h5>Fungsi Ginjal</h5>
              <div class="col-md-3 mb-3">
                <label>fungsiGinjalCrea</label>
                <input type="text" name="fungsiGinjalCrea" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiGinjalUreum</label>
                <input type="text" name="fungsiGinjalUreum" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiGinjalAsam</label>
                <input type="text" name="fungsiGinjalAsam" class="form-control" value="0">
              </div>
            </div>

            <div class="row">
              <h5>Fungsi Jantung</h5>
               <div class="col-md-3 mb-3">
                <label>fungsiJantungABI</label>
                <input type="text" name="fungsiJantungABI" class="form-control" value="0">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiJantungEKG</label>
                <input type="text" name="fungsiJantungEKG" class="form-control">
              </div>
              <div class="col-md-3 mb-3">
                <label>fungsiJantungEcho</label>
                <input type="text" name="fungsiJantungEcho" class="form-control">
              </div>
            </div>

            <div class="row">
              <h5>Pemeriksaan Mata</h5>
               <div class="col-md-3 mb-3">
                <label>funduskopi</label>
                <input type="text" name="funduskopi" class="form-control">
              </div>
            </div>

            <div class="row">
               <h5>Pemeriksaan Radiologi</h5>
              <div class="col-md-3 mb-3">
                <label>radiologiFoto</label>
                <input type="text" name="radiologiFoto" class="form-control">
              </div>
              
            </div>

            <div class="row">
              <h5>Pemeriksaan Tambahan</h5>
              <div class="col-md-3 mb-3">
                <label>pemeriksaanLain</label>
                <input type="text" name="pemeriksaanLain" class="form-control">
              </div>
              <div class="col-md-3 mb-3">
                <label>keterangan</label>
                <input type="text" name="keterangan" class="form-control">
              </div>
            </div>


            <div class="row">
              <div class="col-md-12 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100" name="submit">Submit</button>
              </div>
            </div>

          </div>
        </form>

      <?php endforeach; ?>
        </div>
    </div>



  </body>
</html>

<script src="../webkhanza/js/getProvider.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>