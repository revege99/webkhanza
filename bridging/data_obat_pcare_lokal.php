<?php 
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}


if (isset($_GET['noRawat'])) {
  $noRawat = $_GET['noRawat'];
} else {
  die("Parameter noRawat tidak ditemukan.");
}




$tanggal_awal = date("Y-m-d");
$tanggal_akhir = date("Y-m-d");
$keyword = '';

if (isset($_POST['submit'])) {
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
    $tanggal_awal = $_POST['tanggal_awal'] ?? $tanggal_awal;
    $tanggal_akhir = $_POST['tanggal_akhir'] ?? $tanggal_akhir;
}

$keywordLike = "%" . $keyword . "%";

$cari_obat = "
SELECT *
FROM `pcare_obat_diberikan` a
WHERE a.no_rawat = ?

";



$task = queryPrepared($cari_obat, [$noRawat]);
$record = count($task);

// $cariKunjungan = "
// SELECT *
// FROM pcare_kunjungan_umum
// WHERE noKunjungan = ?
// ";

// $Kunjungan = queryPrepared($cariKunjungan, [$noKunj]);
// $record = count($Kunjungan);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Obat Diberikan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="report/css/anak.css">
    <style>
        #registrasi tr:nth-child(even) { background-color: #f2f9ff; }
        #registrasi tr:nth-child(odd) { background-color: #ffffff; }
        #registrasi th { background-color: #0d6efd; color: white; text-align: center; padding: 8px; }
        #registrasi td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        .table-container { max-height: 400px; overflow-y: auto; }
        table { border-collapse: collapse; width: 100%; }
    </style>
</head>
<body>
<div class="content">
    <nav class="navbar fixed-top">
        <div class="container">
            <div class="d-flex align-items-center">
                <h3 style="color: black;">Data Obat Diberikan</h3>
            </div>
            <div class="btn-group">
                <form class="export" method="post">
                    <button class="btn btn-warning" type="submit" name="export">Export ke Excel</button>
                </form>
                <div class="col-auto" id="record">
                    <p>Record : <?= $record ?></p>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid" id="container-fitur">
        <form class="row gy-2 gx-3 align-items-center" action="" method="post">
            <div class="row" id="fitur">
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
                        <div class="input-group-text">Keyword</div>
                        <input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary" name="submit">Cari</button>
                </div>
            </div>
        </form>
    </div>

    <div class="container-fluid" id="data">
        <div class="row" id="table-container">
            <?php if (!empty($task)) : ?>
                <table id="registrasi">
                    <thead>
                        <tr>
                            <?php
                            $column_labels = [
                                "no_rawat" => "No Rawat",
                                "noKunjungan" => "No Kunjungan",
                                "kdObatSK" => "Kode Obat SK",
                                "tgl_perawatan" => "Tanggal Perawatan",
                                "kode_brng" => "Kode Barang",
                            ];
                            $columns = array_intersect(array_keys($column_labels), array_keys($task[0]));
                            foreach ($columns as $key) {
                                echo "<th>" . $column_labels[$key] . "</th>";
                            }
                            ?>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($task as $row): ?>
                            <tr>
                                <?php foreach ($columns as $key): ?>
                                    <td><?= htmlspecialchars($row[$key] ?? '') ?></td>
                                <?php endforeach; ?>
                                <td>
                                    <i class="fa-solid fa-trash fa-2x" style="color: #dc3545; cursor: pointer;" onclick="confirmHapus('<?= $row['kdObatSK'] ?>')"></i>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: red; font-weight: bold;">Tidak ada data ditemukan untuk "<?= htmlspecialchars($keyword) ?>"</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmHapus(kdObatSK) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `?page=hapus_obat_lokal&kdObatSK=${kdObatSK}`;
        }
    });
}
</script>
</body>
</html>
