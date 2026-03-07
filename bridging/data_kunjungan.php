<?php 
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

$tanggal_awal = date("Y-m-d");
$tanggal_akhir = $tanggal_awal;
$keyword = '';

if (isset($_POST['submit'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'] ?? $tanggal_awal;
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
}

$cari_nokunj = "
SELECT *
FROM pcare_kunjungan_umum
WHERE tglDaftar BETWEEN ? AND ?
";

$no_kunj = queryPrepared($cari_nokunj, [$tanggal_awal, $tanggal_akhir]);
$record = count($no_kunj);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RSSL</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" type="text/css" href="report/css/anak.css">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap" rel="stylesheet">

  <style>
    #registrasi tr:nth-child(even) { background-color: #f2f9ff; }
    #registrasi tr:nth-child(odd) { background-color: #ffffff; }
    #registrasi th { background-color: #0d6efd; color: white; text-align: center; padding: 8px; }
    #registrasi td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    .table-container { max-height: 400px; overflow-y: auto; }
    table { border-collapse: collapse; width: 100%; }
    .dropdown-menu-custom {
  z-index: 9999 !important;
}

  </style>
</head>
<body>
  <div class="content">
    <nav class="navbar fixed-top">
      <div class="container d-flex justify-content-between align-items-center">
        <h3 style="color: black;">Data Kunjungan</h3>
        <div class="btn-group">
          <form method="post">
            <button class="btn btn-warning" type="submit" name="export">Export ke Excel</button>
          </form>
          <div id="record" class="ms-3">
            <p>Record : <?= $record ?></p>
          </div>
        </div>
      </div>
    </nav>

    <div class="container-fluid" id="container-fitur" >
      <form class="row gy-2 gx-3 align-items-center" method="post" >
        <div class="row" id="fitur">
          <div class="col-auto">
            <div class="input-group">
              <div class="input-group-text">Tanggal Awal</div>
              <input type="date" class="form-control" name="tanggal_awal" value="<?= $tanggal_awal ?>">
            </div>
          </div>
          <div class="col-auto">
            <div class="input-group">
              <div class="input-group-text">Tanggal Akhir</div>
              <input type="date" class="form-control" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
            </div>
          </div>
          <div class="col-auto">
            <div class="input-group">
              <div class="input-group-text">Key Word</div>
              <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
            </div>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary" name="submit">Cari</button>
          </div>
        </div>
      </form>
    </div>

    <div class="container-fluid" id="data" >
      <div class="row" id="table-container">
        <?php if (!empty($no_kunj)) : ?>
          <table class="table table-bordered table-hover" style="font-size: 13px;">
            <thead class="thead-light">
              <tr>
                <th style="width: 80px; text-align: center;">AKSI</th>
                <?php
                $column_labels = [
                  "no_rawat" => "Nomor Rawat",
                  "noKunjungan" => "No Kunjungan",
                  "nm_poli" => "Poli"
                ];
                $columns = array_intersect(array_keys($column_labels), array_keys($no_kunj[0]));
                foreach ($columns as $key) {
                  echo "<th>{$column_labels[$key]}</th>";
                }
                ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($no_kunj as $row) : ?>
                <tr>
                  <td style="position: relative; text-align: center;">
  <!-- Ikon dropdown -->
  <i class="fa-solid fa-bars fa-2x text-primary" style="cursor: pointer;" onclick="toggleDropdown(this)"></i>

  <!-- Dropdown Menu -->
  <div class="dropdown-menu-custom" style="
      display: none;
      position: absolute;
      top: 50%;
      left: 100%;
      transform: translateY(-50%);
      background-color: #fff; /* warna hitam */
      border: 1px solid #444;
      border-radius: 6px;
      box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.3);
      z-index: 9999;
      min-width: 160px;
      text-align: left;
      font-family: 'Quicksand', sans-serif;
    ">
    <a href="?page=post_mcu&noKunj=<?= urldecode($row['noKunjungan']) ?>" 
       class="dropdown-item" 
       style="
         display: block;
         font-size: 14px;
         color: black;
         font-weight: bold;
         padding: 10px 15px;
         text-decoration: none;
       "
       onmouseover="this.style.backgroundColor='grey'" 
       onmouseout="this.style.backgroundColor='transparent'">
      Add MCU
    </a>
    <a href="?page=post_obat" 
       class="dropdown-item" 
       style="
         display: block;
         font-size: 14px;
         color: black;
         font-weight: bold;
         padding: 10px 15px;
         text-decoration: none;
       "
       onmouseover="this.style.backgroundColor='grey'" 
       onmouseout="this.style.backgroundColor='transparent'">
      Add Obat
    </a>
  </div>
</td>


                  <?php
                  foreach ($columns as $key) {
                    $align = in_array($key, ['no_rawat', 'nm_poli']) ? 'text-align: center;' : '';
                    echo "<td style=\"$align font-weight: bold;\">" . htmlspecialchars($row[$key] ?? '') . "</td>";
                  }
                  ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else : ?>
          <p class="text-danger font-weight-bold">SEMANGAT KAMU YAA !!!</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    function toggleDropdown(icon) {
      const menu = icon.parentElement.querySelector('.dropdown-menu-custom');
      const isVisible = menu.style.display === 'block';

      document.querySelectorAll('.dropdown-menu-custom').forEach(d => d.style.display = 'none');
      menu.style.display = isVisible ? 'none' : 'block';
    }

    document.addEventListener('click', function(e) {
      if (!e.target.closest('td')) {
        document.querySelectorAll('.dropdown-menu-custom').forEach(d => d.style.display = 'none');
      }
    });
  </script>
</body>
</html>
