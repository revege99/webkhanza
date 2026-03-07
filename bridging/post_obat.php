<?php 
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// $function_path = __DIR__ . '/../function/function.php';
// if (file_exists($function_path)) {
//     require_once $function_path;
// } else {
//     die("Error: File function.php tidak ditemukan di $function_path");
// }




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
      <div class="content">
        <nav class="navbar fixed-top">
            <div class="container">
                <div class="d-flex align-items-center">
                    <h3 style="color : black;">Post Obat</h3>
                </div>  
            </div>  
        </nav>

       
        

<form class="input" style="margin-top:5rem; margin-left: 1rem;" method="post" action="?page=proses_post_obat">

  <div class="mb-3">
    <label class="form-label">kdObatSK</label>
    <input style="width:50%" type="text" name="kdObatSK" class="form-control" value="0">
  </div>

  <div class="mb-3">
    <label class="form-label">noKunjungan</label>
    <input style="width:50%" type="text" name="noKunjungan" class="form-control" value="0030B0110725Y000018">
  </div>

  <div class="mb-3">
    <label class="form-label">racikan</label>
    <select name="racikan" class="form-control" style="width:50%">
      <option value="false">false</option>
      <option value="true" >true</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">kdRacikan</label>
    <input style="width:50%" type="text" name="kdRacikan" class="form-control" >
  </div>

  <div class="mb-3">
    <label class="form-label">obatDPHO</label>
    <select name="obatDPHO" class="form-control" style="width:50%">
      <option value="true">true</option>
      <option value="false">false</option>
    </select>
  </div>

  <div class="mb-3">
    <label class="form-label">kdObat</label>
    <input style="width:50%" type="text" name="kdObat" class="form-control" value="130102305">
  </div>

  <div class="mb-3">
    <label class="form-label">signa1</label>
    <input style="width:50%" type="text" name="signa1" class="form-control" value="3">
  </div>

  <div class="mb-3">
    <label class="form-label">signa2</label>
    <input style="width:50%" type="text" name="signa2" class="form-control" value="1">
  </div>

  <div class="mb-3">
    <label class="form-label">jmlObat</label>
    <input style="width:50%" type="text" name="jmlObat" class="form-control" value="10">
  </div>

  <div class="mb-3">
    <label class="form-label">jmlPermintaan</label>
    <input style="width:50%" type="text" name="jmlPermintaan" class="form-control" value="10">
  </div>

  <div class="mb-3">
    <label class="form-label">nmObatNonDPHO</label>
    <input style="width:50%" type="text" name="nmObatNonDPHO" class="form-control">
  </div>

  <button type="submit" class="btn btn-primary" name="submit">Submit</button>
</form>

          
        </div>
    </div>



  </body>
</html>