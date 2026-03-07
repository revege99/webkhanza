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
}


$keywordLike = "%" . $keyword . "%";


$query = "
SELECT
  p.nm_pasien,
  mb.no_rawat,
  mb.dikirim AS task3,
  mb.diterima AS task4,
  CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat) AS task5,
  CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan) AS task6,
  CONCAT(ro.tgl_perawatan, ' ', ro.jam) AS task7,
  pk.nm_poli,
  rp.kd_pj,

  CASE
    -- 🔴 Jika task3 atau task4 kosong → Belum Selesai
    WHEN mb.dikirim IS NULL OR mb.diterima IS NULL THEN 'Belum Selesai'

    -- 🔴 Jika task5 kosong padahal task3 & 4 sudah ada → Belum Selesai
    WHEN pr.tgl_perawatan IS NULL OR pr.jam_rawat IS NULL THEN 'Belum Selesai'

    -- 🔴 Jika salah satu dari task6 atau task7 ada, tapi pasangannya kosong → Belum Selesai
    WHEN (ro.tgl_peresepan IS NOT NULL AND ro.jam_peresepan IS NOT NULL 
           AND (ro.tgl_perawatan IS NULL OR ro.jam IS NULL))
      OR (ro.tgl_perawatan IS NOT NULL AND ro.jam IS NOT NULL 
           AND (ro.tgl_peresepan IS NULL OR ro.jam_peresepan IS NULL)) THEN 'Belum Selesai'

    -- 🟠 Validasi urutan waktu jika semua task lengkap
    WHEN mb.diterima < mb.dikirim THEN 'Task4 < Task3'
    WHEN CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat) < mb.diterima THEN 'Task5 < Task4'
    WHEN ro.tgl_peresepan IS NOT NULL AND ro.jam_peresepan IS NOT NULL 
         AND CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan) < CONCAT(pr.tgl_perawatan, ' ', pr.jam_rawat) THEN 'Task6 < Task5'
    WHEN ro.tgl_perawatan IS NOT NULL AND ro.jam IS NOT NULL 
         AND CONCAT(ro.tgl_perawatan, ' ', ro.jam) < CONCAT(ro.tgl_peresepan, ' ', ro.jam_peresepan) THEN 'Task7 < Task6'

    ELSE 'OK'
  END AS validasi_task

FROM mutasi_berkas mb 
LEFT JOIN pemeriksaan_ralan pr ON mb.no_rawat = pr.no_rawat
LEFT JOIN resep_obat ro ON mb.no_rawat = ro.no_rawat  -- ✅ join langsung ke mutasi_berkas
LEFT JOIN reg_periksa rp ON mb.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli

WHERE DATE(rp.tgl_registrasi) BETWEEN ? AND ?
  AND (p.nm_pasien LIKE ? or mb.no_rawat LIKE ? ) 
  AND rp.kd_poli NOT IN ('IGDK','UMUM','U0026')
  AND rp.status_lanjut = 'Ralan'
  AND rp.kd_pj = 'BPJ'
  AND rp.biaya_reg != 0

GROUP BY mb.no_rawat;


";

$task = queryPrepared($query, [$tanggal_awal, $tanggal_akhir, $keywordLike, $keywordLike]);
$record = count($task);


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
            <h3 style="color : black;">Monitoring Task 3 - 7 Pasien BPJS</h3>
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
            <div class="row" id="table-container">

                <?php if (isset($_GET['status']) && $_GET['status'] === 'ok') : ?>
                    <div style="background-color: #d4edda; padding: 10px; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 10px;">
                        ✅ Task berhasil dikirim ke BPJS.
                    </div>
                <?php endif; ?>

                 <?php 
                    $no = 1;
                    if (!empty($task)) : ?>
                        <table border="1" cellpadding="5" id="registrasi" width="800">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <?php 
                                    // Mapping Nama Kolom dari Database ke Tampilan
                                    $column_labels = [
                                        "nm_pasien" => "Nama Pasien",
                                        "no_rawat" => "No Rawat",
                                        "task3" => "Task 3",
                                        "task4" => "Task 4",
                                        "task5" => "Task 5",
                                        "task6" => "Task 6",
                                        "task7" => "Task 7",
                                        "nm_poli" => "Poliklinik",
                                        "validasi_task" => "Status",
                                        
                                        
                                    ];

                                    // Pastikan hanya kolom yang ada di data yang diambil
                                    $columns = !empty($task) ? array_keys($task[0]) : [];
                                    $columns = array_intersect(array_keys($column_labels), $columns);

                                    foreach ($columns as $key) {
                                        echo "<th>" . $column_labels[$key] . "</th>";
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($task as $row) { ?>
                                    <tr>
                                        <td style="text-align:center;"><?= $no++ ?></td>
                                      
                                 <?php 
                                        foreach ($columns as $key) {
                                            $align = in_array($key, ['validasi_task', 'nm_poli']) ? 'center' : 'left';
                                            echo "<td style=\"text-align: $align;\">" . htmlspecialchars($row[$key] ?? '') . "</td>";
                                        }
                                        ?>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color: red; font-weight : bold;"> !! <br> Silahkan pilih tanggal<br> SEMANGAT KAMU YAA !!!</p>
                    <?php endif; ?>
     

  </body>
</html>