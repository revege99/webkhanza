<?php 
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }



$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

if (isset($_GET['noKunj'])) {
  $noKunj = $_GET['noKunj'];
} else {
  die("Parameter noKunj tidak ditemukan.");
}

 // echo "<div style='margin-left:20rem'>";
 //    echo "<h4>Detail Kunjungan</h4>";
 //    echo "<ul>";
 //    echo "<li>No Kunjungan: " . htmlspecialchars($noKunj) . "</li>";
 //    die();




$cariKunjungan = "
SELECT *
FROM pcare_rujuk_subspesialis
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

    <link rel="stylesheet" type="text/css" href="report/css/anak.css">
   
    <!-- font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">


  </head>
  <body> 
    <?php foreach ($Kunjungan as $dataKunj) : ?>
      <div class="content">
        <nav class="navbar fixed-top">
            <div class="container">
                <div class="d-flex align-items-center">
                    <h3 style="color : black;">Edit Rujukan</h3>
                </div>  
            </div>  
        </nav>

       

        

    <form class="input" style="margin-top:5rem; margin-left: 1rem;" method="post" action="?page=proses_put_rujukan">
  <div class="container">

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>noKunjungan</label>
        <input type="text" name="noKunjungan" class="form-control" value="<?= $dataKunj['noKunjungan'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>noKartu</label>
        <input type="text" name="noKartu" class="form-control" value="<?= $dataKunj['noKartu'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>tglDaftar</label>
        <input type="text" name="tglDaftar" class="form-control" value="<?= date('d-m-Y', strtotime($dataKunj['tglDaftar'])) ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdPoli</label>
        <input type="text" name="kdPoli" class="form-control" value="<?= $dataKunj['kdPoli'] ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>keluhan</label>
        <input type="text" name="keluhan" class="form-control" value="<?= $dataKunj['keluhan'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdSadar</label>
        <input type="text" name="kdSadar" class="form-control" value="<?= $dataKunj['kdSadar'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>sistole</label>
        <input type="text" name="sistole" class="form-control" value="<?= $dataKunj['sistole'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>diastole</label>
        <input type="text" name="diastole" class="form-control" value="<?= $dataKunj['diastole'] ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>beratBadan</label>
        <input type="text" name="beratBadan" class="form-control" value="<?= $dataKunj['beratBadan'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>tinggiBadan</label>
        <input type="text" name="tinggiBadan" class="form-control" value="<?= $dataKunj['tinggiBadan'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>respRate</label>
        <input type="text" name="respRate" class="form-control" value="<?= $dataKunj['respRate'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>heartRate</label>
        <input type="text" name="heartRate" class="form-control" value="<?= $dataKunj['heartRate'] ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>lingkarPerut</label>
        <input type="text" name="lingkarPerut" class="form-control" value="<?= $dataKunj['lingkarPerut'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdStatusPulang</label>
        <input type="text" name="kdStatusPulang" class="form-control" value="<?= $dataKunj['kdStatusPulang'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>tglPulang</label>
        <input type="text" name="tglPulang" class="form-control" value="<?= date('d-m-Y', strtotime($dataKunj['tglPulang'])) ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdDokter</label>
        <input type="text" name="kdDokter" class="form-control" value="<?= $dataKunj['kdDokter'] ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>kdDiag1</label>
        <input type="text" name="kdDiag1" class="form-control" value="<?= $dataKunj['kdDiag1'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdDiag2</label>
        <input type="text" name="kdDiag2" class="form-control" value="<?= $dataKunj['kdDiag2'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdDiag3</label>
        <input type="text" name="kdDiag3" class="form-control" value="<?= $dataKunj['kdDiag3'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdPoliRujukInternal</label>
        <input type="text" name="kdPoliRujukInternal" class="form-control" value="">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>kdppk</label>
        <input type="text" name="kdppk" class="form-control" value="<?= $dataKunj['kdPPK'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>tglEstRujuk</label>
        <input type="text" name="tglEstRujuk" class="form-control" value="<?= date('d-m-Y', strtotime($dataKunj['tglEstRujuk'])) ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdSubSpesialis1</label>
        <input type="text" name="kdSubSpesialis1" class="form-control" value="<?= $dataKunj['kdSubSpesialis']?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdSarana</label>
        <input type="text" name="kdSarana" class="form-control" value="<?= $dataKunj['kdSarana']?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>khusus</label>
        <input type="text" name="khusus" class="form-control" value="">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdTacc</label>
        <input type="text" name="kdTacc" class="form-control" value="<?= $dataKunj['kdTACC'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>alasanTacc</label>
        <input type="text" name="alasanTacc" class="form-control" value="<?= $dataKunj['alasanTACC'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>anamnesa</label>
        <input type="text" name="anamnesa" class="form-control" value="">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>alergiMakan</label>
        <input type="text" name="alergiMakan" class="form-control" value="<?= $dataKunj['KdAlergiMakanan'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>alergiUdara</label>
        <input type="text" name="alergiUdara" class="form-control" value="<?= $dataKunj['KdAlergiUdara'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>alergiObat</label>
        <input type="text" name="alergiObat" class="form-control" value="<?= $dataKunj['KdAlergiObat'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>kdPrognosa</label>
        <input type="text" name="kdPrognosa" class="form-control" value="<?= $dataKunj['KdPrognosa'] ?>">
      </div>
    </div>

    <div class="row">
      <div class="col-md-3 mb-3">
        <label>terapiObat</label>
        <input type="text" name="terapiObat" class="form-control" value="">
      </div>
      <div class="col-md-3 mb-3">
        <label>terapiNonObat</label>
        <input type="text" name="terapiNonObat" class="form-control" value="<?= $dataKunj['terapi_non_obat'] ?>">
      </div>
      <div class="col-md-3 mb-3">
        <label>bmhp</label>
        <input type="text" name="bmhp" class="form-control" value="<?= $dataKunj['bmhp'] ?>">
      </div>
      <div class="col-md-3 mb-3 d-flex align-items-end">
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