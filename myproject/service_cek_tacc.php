<?php
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

$tanggal_awal  = date("Y-m-d");
$tanggal_akhir = date("Y-m-d");
$keyword       = '';

// 1. Utamakan POST (user submit pencarian baru)
if (isset($_POST['submit'])) {
    $tanggal_awal  = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    $keyword       = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
}
// 2. Kalau tidak ada POST, cek GET (user kembali dari halaman detail)
elseif (isset($_GET['tanggal_awal'], $_GET['tanggal_akhir'])) {
    $tanggal_awal  = $_GET['tanggal_awal'];
    $tanggal_akhir = $_GET['tanggal_akhir'];
    $keyword       = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
}

$keywordLike = "%" . $keyword . "%";

$query = "
SELECT *
FROM (
    SELECT
        rp.no_reg,
        ps.nm_pasien,
        ps.no_rkm_medis,
        rp.no_rawat,
        ps.no_peserta,
        ps.no_ktp,
        mpp.kd_poli_pcare AS kd_poli, 
        pk.nm_poli,
        rp.tgl_registrasi AS tgl_periksa,
        d.kd_dokter,
        d.nm_dokter,
        CONCAT(j.jam_mulai,'-',j.jam_selesai) AS jam_praktek,
        pj.png_jawab,
        ROW_NUMBER() OVER(PARTITION BY rp.no_rawat ORDER BY j.jam_mulai) AS rn
    FROM reg_periksa rp
    INNER JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli
    INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
    INNER JOIN pasien ps ON rp.no_rkm_medis = ps.no_rkm_medis
    INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
    INNER JOIN jadwal j ON rp.kd_dokter = j.kd_dokter
    INNER JOIN maping_poliklinik_pcare mpp ON rp.kd_poli = mpp.kd_poli_rs
    WHERE mpp.kd_poli_pcare IN ('001','U0010', 'U0035', '003')
    AND rp.kd_pj = 'bpj'
    and rp.tgl_registrasi BETWEEN ? and ?
) AS t
WHERE rn = 1;

";
$cari_pasien = queryPrepared($query, [$tanggal_awal, $tanggal_akhir]);
$record     = count($cari_pasien);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RSSL</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" type="text/css" href="service/css/antrean.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

  <style>
    #registrasi tr:nth-child(even) {background-color: #f2f9ff;}
    #registrasi tr:nth-child(odd)  {background-color: #ffffff;}
    #registrasi th {
   

        text-align: center;
    }
    #registrasi td, #registrasi th {
        padding: 8px;
        border: 1px solid #ddd;
    }
  </style>
</head>
<body>
<div class="content">
  <nav class="navbar fixed-top">
    <div class="container d-flex justify-content-between align-items-center">
      <h2>Service Antrean</h2>
      <div class="btn-group">
        <form class="export" method="post">
          <button class="btn btn-warning" id="export" type="submit" name="export">Export ke Excel</button>
        </form>
        <div class="ms-3" id="record">
          <p>Record : <?= $record ?></p>
        </div>
      </div>
    </div>
  </nav>

  <div class="container-fluid" id="container-fitur" style="margin-top:90px;">
    <form class="row gy-2 gx-3 align-items-center" action="" method="post">
      <div class="col-auto">
        <div class="input-group">
          <div class="input-group-text">Tanggal Awal</div>
          <input type="date" value="<?= $tanggal_awal; ?>" class="form-control" name="tanggal_awal">
        </div>
      </div>
      <div class="col-auto">
        <div class="input-group">
          <div class="input-group-text">Tanggal Akhir</div>
          <input type="date" value="<?= $tanggal_akhir; ?>" class="form-control" name="tanggal_akhir">
        </div>
      </div>
      <div class="col-auto">
        <div class="input-group">
        <button type="submit" class="btn btn-primary" name="submit">Cari</button>
    </div>
      </div>
    </form>
  </div>

  <div class="container-fluid" id="data">
    <div class="row" id="table-container" >
      <?php if (!empty($cari_pasien)) : ?>
        <table id="registrasi" width="800">
          <thead>
            <tr>
              <th>Aksi</th>
              <th>No</th>
              <?php
              $column_labels = [
                  "no_reg" => "No Registrasi",
                  "nm_pasien" => "Nama Pasien",
                  "no_rkm_medis" => "No Rekma Medis",
                  "no_peserta" => "No Kartu BPJS",
                  "no_ktp" => "No KTP",
                  "kd_poli" => "kd_poli",
                  "nm_poli" => "Nama Poli",
                  "tgl_periksa" => "Tanggal Periksa",
                  "kd_dokter" => "kd_dokter",
                  "nm_dokter" => "Nama Dokter",
                  "no_rawat" => "No Rawat",
                  "nm_poli" => "Poliklinik",
                  "png_jawab" => "Jenis Bayar",
                  
                  
              ];
              $columns = array_intersect(array_keys($column_labels), array_keys($cari_pasien[0]));
              foreach ($columns as $key) {
                  echo '<th>' . $column_labels[$key] . '</th>';
              }
              ?>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($cari_pasien as $row): ?>
              <tr>
                <!-- Kolom Aksi -->
                <td style="text-align:center;">
                  <form action="?page=function_kirim_antrean" method="post" style="display:inline;">
                    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($row['no_rawat'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-sm btn-primary"
                            onclick="return confirm('Kirim antrean pasien ini ke BPJS?');">
                      Kirim
                    </button>
                  </form>
                  <form action="?page=function_batal_antrean" method="post" style="display:inline;">
                    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($row['no_rawat'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                            onclick="return confirm('Batalkan antrean pasien ini di BPJS?');">
                      Batal
                    </button>
                  </form>


                  <form action="?page=function_panggil_antrean" method="post" style="display:inline;">
                    <input type="hidden" name="no_rawat" value="<?= htmlspecialchars($row['no_rawat'], ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-sm btn-success"
                            onclick="return confirm('Panggil antrean pasien ini ke BPJS?');">
                      Panggil
                    </button>
                  </form>

                </td>

                <!-- Nomor urut -->
                <td style="text-align:center;"><?= $no++; ?></td>

                <!-- Kolom data dinamis -->
                <?php foreach ($columns as $key): ?>
                  <?php
                    $centerCols = ['no_rkm_medis', 'tgl_masuk', 'jam_masuk', 'tgl_keluar', 'jam_keluar', 'total_makan'];
                    $style = in_array($key, $centerCols) ? 'style="text-align:center;"' : '';
                  ?>
                  <td <?= $style ?>>
                    <?= htmlspecialchars($row[$key] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="color: red; font-weight:bold;">
          !!<br>Silahkan pilih tanggal<br>SEMANGAT KAMU YAA !!!
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
