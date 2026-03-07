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
$keyword = '';
// var_dump($tanggal_awal, $tanggal_akhir);
// die();


if (isset($_POST['submit'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : ''; 
$keyword = "%$keyword%"; 
}

$query = "
SELECT r.no_rawat, rp.no_rkm_medis, p.nm_pasien
FROM resume_pasien_ranap AS r
LEFT JOIN reg_periksa AS rp ON r.no_rawat = rp.no_rawat
LEFT JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE DATE(rp.tgl_registrasi) BETWEEN ? AND ?
AND (rp.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ?)";

$pasien_ranap = queryPrepared($query, [$tanggal_awal, $tanggal_akhir,$keyword,$keyword]);
$record = count($pasien_ranap);

// Simpan hasil ke session
// $_SESSION['data_pasien'] = $pasien_ranap; 
// echo "<pre>";
// print_r($_SESSION['data_pasien']); // Cek apakah data tersimpan di session
// echo "</pre>";


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

    
  </head>
  <body> 
      <div class="content">
        <nav class="navbar fixed-top">
        <div class="container">
          <div class="d-flex align-items-center">
            <h3 style="color : black;">Resume Pasien Ranap</h3>
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
        <div class="input-group">
          <div class="input-group-text">Tanggal Akhir</div>
          <input  type="date" class="form-control" id="autoSizingInputGroup" value="<?=$tanggal_akhir; ?>" name="tanggal_akhir" >
        </div>
      </div>

      <div class="col-auto ">
        <div class="input-group">
          <div class="input-group-text"> Key Word</div>
          <input  type="text" class="form-control" id="autoSizingInputGroup"  name="keyword" >
        </div>
      </div>
 
     
      <div class="col-auto">
        <button type="submit" class="btn btn-primary" name="submit">Cari</button>
      </div>

     
</form>
    </div>
        </div>
          <div class="container-fluid" id="data" >
            <div class="row" id="table-container">
                 <?php 
                    $no = 1;
                    if (!empty($pasien_ranap)) : ?>
                        <table border="1" cellpadding="5" id="registrasi" width="800">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Aksi</th>
                                    <?php 
                                    // Mapping Nama Kolom dari Database ke Tampilan
                                    $column_labels = [
                                        "no_rawat" => "No Rawat",
                                        "no_rkm_medis" => "No RKM Medis",
                                        "nm_pasien" => "Nama Pasien",
                                        
                                        
                                    ];

                                    // Pastikan hanya kolom yang ada di data yang diambil
                                    $columns = !empty($pasien_ranap) ? array_keys($pasien_ranap[0]) : [];
                                    $columns = array_intersect(array_keys($column_labels), $columns);

                                    foreach ($columns as $key) {
                                        echo "<th>" . $column_labels[$key] . "</th>";
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($pasien_ranap as $row) { ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                       <td>
                                        <button style="border-radius: 10px; background-color: blue; border: none; padding: 5px; width: 50px;">
                                          <a href="#" onclick="cetakPdf('<?= $row['no_rawat'] ?>')" style="text-decoration: none; color: white; font-weight: bold; ">CETAK</a>
                                        </button>
                                      </td>
                                        <?php 
                                        foreach ($columns as $key) {
                                            echo "<td>" . htmlspecialchars($row[$key]) . "</td>";
                                        }
                                        ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: red; font-weight : bold;"> !! <br> Silahkan pilih tanggal<br> SEMANGAT KAMU YAA !!!</p>
                    <?php endif; ?>

        
        
          <div id="detail-container" style="margin-top: 20px; padding: 10px; border: 1px solid #ddd; display: none;">
            <h3>Detail Pemeriksaan</h3>
            <form>

                <label>Subjek:</label>
                <textarea id="subjek" class="form-control" rows="2" readonly></textarea>

                <label>Objek:</label>
                <textarea id="objek" class="form-control" rows="2" readonly></textarea>

                <label>Instruksi:</label>
                <textarea id="instruksi" class="form-control" rows="2" readonly></textarea>

                <label>Evaluasi:</label>
                <textarea id="evaluasi" class="form-control" rows="2" readonly></textarea>

                <label>Jam Rawat:</label>
                <textarea id="jam_rawat" class="form-control" rows="2" readonly></textarea>

                <label>Tanggal Perawtan:</label>
                <textarea id="tgl_perawatan" class="form-control" rows="2" readonly></textarea>
            </form>
        </div>
    </div>
   </div>
       
</div>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        let rows = document.querySelectorAll(".data-row");

        rows.forEach(row => {
            row.addEventListener("click", function() {
                // Ambil data dari atribut `data-*`
                let objek = this.getAttribute("data-objek");
                let subjek = this.getAttribute("data-subjek");
                let instruksi = this.getAttribute("data-instruksi");
                let evaluasi = this.getAttribute("data-evaluasi");
                let jam_rawat = this.getAttribute("data-jam_rawat");
                let tgl_perawatan = this.getAttribute("data-tgl_perawatan");

                // Masukkan ke dalam textarea
                document.getElementById("objek").value = objek;
                document.getElementById("subjek").value = subjek;
                document.getElementById("instruksi").value = instruksi;
                document.getElementById("evaluasi").value = evaluasi;
                document.getElementById("jam_rawat").value = jam_rawat;
                document.getElementById("tgl_perawatan").value = tgl_perawatan;
            });
        });
    });
</script>

<script>
    function cetakPdf(no_rawat, tanggal) {
    let url = "myproject/pdf_resume_ranap.php?no_rawat=" + no_rawat;
    window.open(url, '_blank');
}

</script>


     

  </body>
</html>