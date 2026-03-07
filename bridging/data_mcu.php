<?php

if (isset($_GET['hapus'])) {
    if ($_GET['hapus'] == 'success') {
        echo "<script>alert('✅ MCU berhasil dihapus dari BPJS dan lokal.');</script>";
    } elseif ($_GET['hapus'] == 'partial') {
        echo "<script>alert('⚠️ MCU terhapus dari BPJS tapi gagal hapus lokal.');</script>";
    } elseif ($_GET['hapus'] == 'fail') {
        $pesan = isset($_GET['pesan']) ? urldecode($_GET['pesan']) : 'Tidak diketahui';
        echo "<script>alert('❌ Gagal menghapus MCU dari BPJS: $pesan');</script>";
    }
}

require_once 'myproject/vendor/autoload.php';

$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

use LZCompressor\LZString;
date_default_timezone_set('UTC');

if (isset($_GET['noKunjungan'])) {
    $noKunjungan = $_GET['noKunjungan'];
} else {
    die("Parameter noKunjungan tidak ditemukan.");
}

$tanggal_awal = date("Y-m-d");
$tanggal_akhir = date("Y-m-d");
$keyword = '';

// 1. Utamakan POST (user submit pencarian baru)
if (isset($_POST['submit'])) {
    $tanggal_awal = $_POST['tanggal_awal'];
    $tanggal_akhir = $_POST['tanggal_akhir'];
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
}

// 2. Kalau tidak ada POST, cek GET (user kembali dari halaman detail)
elseif (isset($_GET['tanggal_awal']) && isset($_GET['tanggal_akhir'])) {
    $tanggal_awal = $_GET['tanggal_awal'];
    $tanggal_akhir = $_GET['tanggal_akhir'];
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
}

// KeywordLike dibuat dari keyword yang aktif
$keywordLike = "%" . $keyword . "%";

$cari_pasien = "
SELECT pm.*, pk.no_rawat, p.nm_pasien
FROM pcare_mcu pm 
LEFT  JOIN pcare_kunjungan_umum pk ON pm.noKunjungan = pk.noKunjungan
INNER JOIN reg_periksa rp ON pk.no_rawat =  rp.no_rawat
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE pm.noKunjungan = ?
";


$pasien = queryPrepared($cari_pasien, [$noKunjungan]);


// Konfigurasi
$cons_id       = '13216';
$secretKey     = '3nG5007800';
$user_key      = 'f126b8a2c2488a9eec8e79fdd0bd55ef';
$authorization = 'Basic MDM3M0IwMDYucGNhcmU6TGViaWhIMWR1cCE6MDk1';

// Generate timestamp dan signature
$timestamp = time();
$signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secretKey, true));

// Header API
$headers = [
    "X-cons-id: $cons_id",
    "X-timestamp: $timestamp",
    "X-signature: $signature",
    "user_key: $user_key",
    "X-authorization: $authorization"
];

// Fungsi dekripsi string
function stringDecrypt($key, $string) {
    $encrypt_method = 'AES-256-CBC';
    $key_hash = hex2bin(hash('sha256', $key));
    $iv = substr($key_hash, 0, 16);

    // Pastikan $string valid
    if (empty($string) || !is_string($string)) {
        return null; // atau '' kalau mau tetap string
    }

    return openssl_decrypt(
        base64_decode($string),
        $encrypt_method,
        $key_hash,
        OPENSSL_RAW_DATA,
        $iv
    );
}


// Ambil parameter noKunjungan


// Panggil API
$url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/MCU/kunjungan/$noKunjungan";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Curl error: " . curl_error($ch));
}
curl_close($ch);

// Proses hasil
$result = json_decode($response, true);
$key = $cons_id . $secretKey . $timestamp;

if (!isset($result['response']) || !is_string($result['response'])) {
    $data_mcu_list = []; // biar masuk kondisi "Tidak ada data."
} else {
    $data_mcu_list = json_decode($result['response'], true);
}


$decrypted = stringDecrypt($key, $result['response']);
$original  = LZString::decompressFromEncodedURIComponent($decrypted);

if ($original === null) {
    die("Gagal mendekompresi data untuk noKunjungan $noKunjungan");
}

$data_array    = json_decode($original, true);
$data_mcu_list = [];

if (isset($data_array['list']) && is_array($data_array['list'])) {
    foreach ($data_array['list'] as $mcu) {
        // Cari no_rawat dari hasil query lokal
        $no_rawat_local = '';
        foreach ($pasien as $lokal) {
            if ($lokal['noKunjungan'] == ($mcu['noKunjungan'] ?? '')) {
                $kdMCUlokal = $lokal['kdMCU'];
                $no_rawat_local = $lokal['no_rawat'];
                $nama_local = $lokal['nm_pasien'];
                $kdProvider_local = $lokal['kdProvider'];
                $tglPelayananlokal = $lokal['tglPelayanan'];
                $tdslocal = $lokal['tekananDarahSistole'];
                $tddlocal = $lokal['tekananDarahDiastole'];
                $darahRutinHemolokal = $lokal['darahRutinHemo'];
                $darahRutinLeulokal = $lokal['darahRutinLeu'];
                $darahRutinEritlokal = $lokal['darahRutinErit'];
                $darahRutinLajulokal = $lokal['darahRutinLaju'];
                $darahRutinHemalokal = $lokal['darahRutinHema'];
                $darahRutinTromlokal = $lokal['darahRutinTrom'];

                $lemakDarahHDLlokal = $lokal['lemakDarahHDL'];
                $lemakDarahLDLlokal = $lokal['lemakDarahLDL'];
                $lemakDarahChollokal = $lokal['lemakDarahChol'];
                $lemakDarahTriglilokal = $lokal['lemakDarahTrigli'];

                $gulaDarahSewaktulokal = $lokal['gulaDarahSewaktu'];
                $gulaDarahPuasalokal = $lokal['gulaDarahPuasa'];
                $gulaDarahPostPrandiallokal = $lokal['gulaDarahPostPrandial'];
                $gulaDarahHbA1clokal = $lokal['gulaDarahHbA1c'];
                $fungsiHatiSGOTlokal = $lokal['fungsiHatiSGOT'];
                $fungsiHatiSGPTlokal = $lokal['fungsiHatiSGPT'];
                $fungsiHatiGammalokal = $lokal['fungsiHatiGamma'];
                $fungsiHatiProtKuallokal = $lokal['fungsiHatiProtKual'];
                $fungsiHatiAlbuminlokal = $lokal['fungsiHatiAlbumin'];
                $fungsiGinjalCrealokal = $lokal['fungsiGinjalCrea'];
                $fungsiGinjalUreumlokal = $lokal['fungsiGinjalUreum'];
                $fungsiGinjalAsamlokal = $lokal['fungsiGinjalAsam'];
                $fungsiJantungABIlokal = $lokal['fungsiJantungABI'];
                $fungsiJantungEKGlokal = $lokal['fungsiJantungEKG'];
                $fungsiJantungEcholokal = $lokal['fungsiJantungEcho'];

                $funduskopilokal = $lokal['funduskopi'];
                $pemeriksaanLainlokal = $lokal['pemeriksaanLain'];
                $keteranganlokal = $lokal['keterangan'];
                $radiologiFotolokal = $lokal['radiologiFoto'];
                break;
            }
        }

        $data_mcu_list[] = [
            'kdMCUlokal'      => $kdMCUlokal ?? '-',
            'no_rawat'    => $no_rawat_local ?: '-',
            'noKunjungan'      => $mcu['noKunjungan'] ?? '-',
            'kdMCU'      => $mcu['kdMCU'] ?? '-',
            'nm_pasien'      => $nama_local ?? '-',
            'kdProvider'      => $kdProvider_local ?? '-',
            'tglPelayanan'      => $tglPelayananlokal ?? '-',
            'tekananDarahSistole'      => $tdslocal ?? '-',
            'tekananDarahDiastole'      => $tddlocal ?? '-',
            'radiologiFoto'      => $radiologiFotolokal ?? '-',
            'darahRutinHemo'      => $darahRutinHemolokal ?? '-',
            'darahRutinLeu'      => $darahRutinLeulokal ?? '-',
            'darahRutinErit'      => $darahRutinEritlokal ?? '-',
            'darahRutinLaju'      => $darahRutinLajulokal ?? '-',
            'darahRutinHema'      => $darahRutinHemalokal ?? '-',
            'darahRutinTrom'      => $darahRutinTromlokal ?? '-',
            'lemakDarahHDL'      => $lemakDarahHDLlokal ?? '-',
            'lemakDarahLDL'      => $lemakDarahLDLlokal ?? '-',
            'lemakDarahChol'      => $lemakDarahChollokal ?? '-',
            'lemakDarahTrigli'      => $lemakDarahTriglilokal ?? '-',
            'gulaDarahSewaktu'      => $gulaDarahSewaktulokal ?? '-',
            'gulaDarahPuasa'      => $gulaDarahPuasalokal ?? '-',
            'gulaDarahPostPrandial'      => $gulaDarahPostPrandiallokal ?? '-',
            'gulaDarahHbA1c'      => $gulaDarahHbA1clokal ?? '-',
            'fungsiHatiSGOT'      => $fungsiHatiSGOTlokal ?? '-',
            'fungsiHatiSGPT'      => $fungsiHatiSGPTlokal ?? '-',
            'fungsiHatiGamma'      => $fungsiHatiGammalokal ?? '-',
            'fungsiHatiProtKual'      => $fungsiHatiProtKuallokal ?? '-',
            'fungsiHatiAlbumin'      => $fungsiHatiAlbuminlokal ?? '-',
            'fungsiGinjalCrea'      => $fungsiGinjalCrealokal ?? '-',
            'fungsiGinjalUreum'      => $fungsiGinjalUreumlokal ?? '-',
            'fungsiGinjalAsam'      => $fungsiGinjalAsamlokal ?? '-',
            'fungsiJantungABI'      => $fungsiJantungABIlokal ?? '-',
            'fungsiJantungEKG'      => $fungsiJantungEKGlokal ?? '-',
            'fungsiJantungEcho'      => $fungsiJantungEcholokal ?? '-',
            'funduskopi'      => $funduskopilokal ?? '-',
            'pemeriksaanLain'      => $pemeriksaanLainlokal ?? '-',
            'keterangan'      => $keteranganlokal ?? '-',

        ];
    }
}

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
    <style type="text/css">
        
        #registrasi tr:nth-child(even) {
              background-color: #f2f9ff; /* biru muda */
          }

          #registrasi tr:nth-child(odd) {
              background-color: #ffffff; /* putih */
          }

          #registrasi th {
              background-color: #0d6efd;
              color: white;
              text-align: center;
              padding: 8px;

          }

          #registrasi td {
              padding: 1px;
              text-align: left;
            

          }


          .table-container {
        max-height: 400px; /* atau sesuai kebutuhan */
        overflow-y: auto;
    }


    table {
        border-collapse: collapse;
        width: 100%;
    }

   .modal {
  position: fixed !important;
  z-index: 99999 !important;
}
.modal-backdrop {
  z-index: 99998 !important;
}

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
  input[readonly] {
  background-color: #e9ecef; /* abu-abu Bootstrap */
  color: #495057;            /* teks abu gelap */
  cursor: not-allowed;       /* kursor terkunci */
}
    
    </style>

    
  </head>
      <body> 
          <div style="margin-top:-2rem" class="content">
            <nav class="navbar fixed-top">
            <div class="container">
              <div class="d-flex align-items-center">
                <?php foreach ($data_mcu_list as $row) : ?>
                    <h3 style="color : gray;">Data MCU <?=$row['nm_pasien'] ?></h3>    
                <?php endforeach; ?>
                
              </div>  
                    <div class="record-container">
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
                      <input  type="text" class="form-control" id="autoSizingInputGroup"  name="keyword"  value="<?=$noKunjungan; ?>">
                    </div>
                  </div>
             
                 
                  <div class="col-auto">
                    <button type="submit" class="btn btn-primary" name="submit">Cari</button>
                  </div>

         
            </form>
                </div>
                    </div>
                      <div class="container-fluid" id="data">
                        <div class="row" id="table-container" style="">
                            <table id="registrasi" width="800" height="100%" style="margin-top:2rem">
                            <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                <tr>
                                    <th>No</th>
                                    <th>KD MCU SK</th>
                                    <th>Nama Pasien</th>
                                    <th>No Kunjungan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data_mcu_list)): ?>
                                    <tr><td colspan="3">Tidak ada data.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($data_mcu_list as $mcu): ?>
                                       <tr>
                                          <td style="width:10px; text-align: center;"><?= $no++; ?></td>
                                          <td><?= htmlspecialchars($mcu['kdMCU']) ?></td>
                                          <td><?= htmlspecialchars($mcu['nm_pasien']) ?></td>
                                          <td><?= htmlspecialchars($mcu['noKunjungan']) ?></td>
                                        <td style="display: flex; justify-content: center; align-items: center; gap: 4px;">
                                             <!-- Tombol Edit -->
                                            <form>
                                                <button type="button" style="border: none; background: none;" 
                                                    onclick="editData(
                                                    '<?= htmlspecialchars($mcu['kdMCUlokal']) ?>',
                                                    '<?= htmlspecialchars($mcu['kdMCU']) ?>',
                                                    '<?= htmlspecialchars($mcu['noKunjungan']) ?>',
                                                    '<?= htmlspecialchars($mcu['kdProvider']) ?>',
                                                    '<?= htmlspecialchars($mcu['tglPelayanan']) ?>',
                                                    '<?= htmlspecialchars($mcu['tekananDarahSistole']) ?>',
                                                    '<?= htmlspecialchars($mcu['tekananDarahDiastole']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinHemo']) ?>',
                                                    '<?= htmlspecialchars($mcu['radiologiFoto']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinLeu']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinErit']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinLaju']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinHema']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinTrom']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahHDL']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahLDL']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahChol']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahTrigli']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahSewaktu']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahPuasa']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahPostPrandial']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahHbA1c']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiSGOT']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiSGPT']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiGamma']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiProtKual']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiAlbumin']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiGinjalCrea']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiGinjalUreum']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiGinjalAsam']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiJantungABI']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiJantungEKG']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiJantungEcho']) ?>',
                                                    '<?= htmlspecialchars($mcu['funduskopi']) ?>',
                                                    '<?= htmlspecialchars($mcu['pemeriksaanLain']) ?>',
                                                    '<?= htmlspecialchars($mcu['keterangan']) ?>'

                                                    )">
                                                    <i class="fa-solid fa-pen-to-square" style="color: #0d6efd; font-size: 16px; cursor: pointer;"></i>
                                                </button>
                                            </form>

                                            <!-- Tombol Hapus -->
                                            <form method="POST" action="?page=proses_del_mcu" onsubmit="return confirm('Yakin ingin menghapus MCU ini dari BPJS?')">
                                                <input type="hidden" name="kdMCU" value="<?= htmlspecialchars($mcu['kdMCU']) ?>">
                                                <input type="hidden" name="noKunjungan" value="<?= htmlspecialchars($mcu['noKunjungan']) ?>">
                                                <button type="submit" style="border: none; background: none;">
                                                    <i class="fa-solid fa-trash" style="color: #dc3545; font-size: 16px; cursor: pointer;"></i>
                                                </button>
                                            </form>

                                            <!-- Tombol Detail -->
                                            <form method="POST" action="?" onsubmit="">
                                                    <button type="button" style="border: none; background: none;" 
                                                    onclick="detaildata(
                                                    '<?= htmlspecialchars($mcu['kdMCUlokal']) ?>',
                                                    '<?= htmlspecialchars($mcu['kdMCU']) ?>',
                                                    '<?= htmlspecialchars($mcu['noKunjungan']) ?>',
                                                    '<?= htmlspecialchars($mcu['kdProvider']) ?>',
                                                    '<?= htmlspecialchars($mcu['tglPelayanan']) ?>',
                                                    '<?= htmlspecialchars($mcu['tekananDarahSistole']) ?>',
                                                    '<?= htmlspecialchars($mcu['tekananDarahDiastole']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinHemo']) ?>',
                                                    '<?= htmlspecialchars($mcu['radiologiFoto']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinLeu']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinErit']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinLaju']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinHema']) ?>',
                                                    '<?= htmlspecialchars($mcu['darahRutinTrom']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahHDL']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahLDL']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahChol']) ?>',
                                                    '<?= htmlspecialchars($mcu['lemakDarahTrigli']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahSewaktu']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahPuasa']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahPostPrandial']) ?>',
                                                    '<?= htmlspecialchars($mcu['gulaDarahHbA1c']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiSGOT']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiSGPT']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiGamma']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiProtKual']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiHatiAlbumin']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiGinjalCrea']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiGinjalUreum']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiGinjalAsam']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiJantungABI']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiJantungEKG']) ?>',
                                                    '<?= htmlspecialchars($mcu['fungsiJantungEcho']) ?>',
                                                    '<?= htmlspecialchars($mcu['funduskopi']) ?>',
                                                    '<?= htmlspecialchars($mcu['pemeriksaanLain']) ?>',
                                                    '<?= htmlspecialchars($mcu['keterangan']) ?>'

                                                    )">
                                                    <i class="fa-solid fa-eye" style="color: #198754; font-size: 16px; cursor: pointer;"></i>
                                                </button>
                                            </form>

                                        </td>
                                    </tr>

                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            
                        </table>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

                <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
                  <div class="modal-dialog modal-xl"> <!-- tambahkan modal-xl -->
                    <form method="post" action="?page=proses_update_mcu"> <!-- ganti dengan script update -->
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 style=" width:100%; font-weight:bold;" class="modal-title" id="modalEditLabel">Edit Data MCU</h4>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="row g-3">
                            <div class="col-md-3">
                              <label>KD MCU Lokal</label>
                              <input type="text" name="kdMCUlokal" id="edit_kdMCUlokal" class="form-control" readonly>
                            </div>

                            <div class="col-md-3">
                              <label>KD MCU SK</label>
                              <input type="text" class="form-control" value="" id="edit_kdMCU_display" readonly>
                              <input type="hidden" name="kdMCU" id="edit_kdMCU">
                            </div>

                            <div class="col-md-3">
                              <label>No Kunjungan</label>
                              <input type="text" class="form-control" name="noKunjungan" id="edit_noKunjungan" readonly>
                            </div>

                            <div class="col-md-3">
                              <label>Kd Provider</label>
                              <input type="text" class="form-control" name="kdProvider" id="edit_kdProvider" readonly>
                            </div>

                            <div class="col-md-3">
                              <label>Tanggal Pelayanan</label>
                              <input type="text" class="form-control" name="tglPelayanan" id="edit_tglPelayanan" readonly>
                            </div>


                            

                            <div class="row g-3">
                                <h5>Pemeriksaan Fisik</h5>
                                <div class="col-md-3">
                                  <label>Tekanan Darah Sistole</label>
                                  <input type="text" class="form-control" name="tds" id="edit_tds">
                                </div>
                                <div class="col-md-3">
                                  <label>Tekanan Darah Diastole</label>
                                  <input type="text" class="form-control" name="tdd" id="edit_tdd">
                                </div>
                            </div>

                            <div class="row g-3">
                                <h5>Pemeriksaan Darah Rutin</h5>
                                <div class="col-md-3">
                                  <label>Darah Rutin Hemo</label>
                                  <input type="text" class="form-control" name="darahRutinHemo" id="edit_darahRutinHemo">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Leu</label>
                                  <input type="text" class="form-control" name="darahRutinLeu" id="edit_darahRutinLeu">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Erit</label>
                                  <input type="text" class="form-control" name="darahRutinErit" id="edit_darahRutinErit">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Laju</label>
                                  <input type="text" class="form-control" name="darahRutinLaju" id="edit_darahRutinLaju">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Hema</label>
                                  <input type="text" class="form-control" name="darahRutinHema" id="edit_darahRutinHema">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Trom</label>
                                  <input type="text" class="form-control" name="darahRutinTrom" id="edit_darahRutinTrom">
                                </div>
                            </div>

                            <div class="row g-3">
                                <h5>Profil Lipil</h5>
                                <div class="col-md-3">
                                  <label>Lemak Darah HDL</label>
                                  <input type="text" class="form-control" name="lemakDarahHDL" id="edit_lemakDarahHDL">
                                </div>
                                <div class="col-md-3">
                                  <label>Lemak Darah LDL</label>
                                  <input type="text" class="form-control" name="lemakDarahLDL" id="edit_lemakDarahLDL">
                                </div>
                                <div class="col-md-3">
                                  <label>Lemak Darah Chol</label>
                                  <input type="text" class="form-control" name="lemakDarahChol" id="edit_lemakDarahChol">
                                </div>
                                <div class="col-md-3">
                                  <label>Lemak Darah Trigil</label>
                                  <input type="text" class="form-control" name="lemakDarahTrigli" id="edit_lemakDarahTrigli">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Gula Darah</h5>
                                <div class="col-md-3">
                                  <label>Gula Darah Sewaktu</label>
                                  <input type="text" class="form-control" name="gulaDarahSewaktu" id="edit_gulaDarahSewaktu">
                                </div>
                                <div class="col-md-3">
                                  <label>Gula Darah Puasa</label>
                                  <input type="text" class="form-control" name="gulaDarahPuasa" id="edit_gulaDarahPuasa">
                                </div>
                                <div class="col-md-3">
                                  <label>Gula Darah Post Prandial</label>
                                  <input type="text" class="form-control" name="gulaDarahPostPrandial" id="edit_gulaDarahPostPrandial">
                                </div>
                                <div class="col-md-3">
                                  <label>Gula Darah HbA1c</label>
                                  <input type="text" class="form-control" name="gulaDarahHbA1c" id="edit_gulaDarahHbA1c">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Fungsi Hati</h5>
                                <div class="col-md-3">
                                  <label>Fungsi Hati SGOT</label>
                                  <input type="text" class="form-control" name="fungsiHatiSGOT" id="edit_fungsiHatiSGOT">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati SGPT</label>
                                  <input type="text" class="form-control" name="fungsiHatiSGPT" id="edit_fungsiHatiSGPT">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati Gamma</label>
                                  <input type="text" class="form-control" name="fungsiHatiGamma" id="edit_fungsiHatiGamma">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati Protkual</label>
                                  <input type="text" class="form-control" name="fungsiHatiProtKual" id="edit_fungsiHatiProtKual">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati Albumin</label>
                                  <input type="text" class="form-control" name="fungsiHatiAlbumin" id="edit_fungsiHatiAlbumin">
                                </div>
                            </div>
                             <div class="row g-3">
                                <h5>Fungsi Ginjal</h5>
                                <div class="col-md-3">
                                  <label>Fungsi Ginjal Crea</label>
                                  <input type="text" class="form-control" name="fungsiGinjalCrea" id="edit_fungsiGinjalCrea">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Ginjal Ureum</label>
                                  <input type="text" class="form-control" name="fungsiGinjalUreum" id="edit_fungsiGinjalUreum">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Ginjal Asam</label>
                                  <input type="text" class="form-control" name="fungsiGinjalAsam" id="edit_fungsiGinjalAsam">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Fungsi Jantung</h5>
                                <div class="col-md-3">
                                  <label>Fungsi Jantung Abi</label>
                                  <input type="text" class="form-control" name="fungsiJantungABI" id="edit_fungsiJantungABI">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Jantung EKG</label>
                                  <input type="text" class="form-control" name="fungsiJantungEKG" id="edit_fungsiJantungEKG">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Jantung Echo</label>
                                  <input type="text" class="form-control" name="fungsiJantungEcho" id="edit_fungsiJantungEcho">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Pemeriksaan Mata</h5>
                                <div class="col-md-3">
                                  <label>Funduskopi</label>
                                  <input type="text" class="form-control" name="funduskopi" id="edit_funduskopi">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Pemeriksaan Radiologi</h5>
                                <div class="col-md-3">
                                  <label>Radiologi Foto</label>
                                  <input type="text" class="form-control" name="radiologiFoto" id="edit_radiologiFoto">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Pemeriksaan Tambahan</h5>
                                <div class="col-md-3">
                                  <label>Pemeriksaan Lain</label>
                                  <input type="text" class="form-control" name="pemeriksaanLain" id="edit_pemeriksaanLain">
                                </div>
                                <div class="col-md-3">
                                  <label>Keterangan</label>
                                  <input type="text" class="form-control" name="keterangan" id="edit_keterangan">
                                </div>
                            </div>

                          </div>

                        </div>
                        <div class="modal-footer">
                          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>


                <!-- modal detail -->
                <div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
                  <div class="modal-dialog modal-xl"> <!-- tambahkan modal-xl -->
                    <form method="post" action="?page=proses_update_mcu"> <!-- ganti dengan script update -->
                      <div class="modal-content">
                        <div class="modal-header">
                              <h4 style="font-weight:bold;" class="modal-title" id="modalEditLabel">Detail MCU</h4>
                              <button style="margin-left: 1rem;" type="button" class="btn btn-success btn-sm" onclick="printModal('modalEdit')">
                                <i class="fa fa-print"></i> Cetak
                              </button>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                        <div class="modal-body">
                          <div class="row g-3">
                            <div class="col-md-3">
                              <label>KD MCU Lokal</label>
                              <input type="text" name="kdMCUlokal" id="edit_kdMCUlokal" class="form-control" readonly>
                            </div>

                            <div class="col-md-3">
                              <label>KD MCU SK</label>
                              <input type="text" class="form-control" value="" id="edit_kdMCU_display" readonly>
                              <input type="hidden" name="kdMCU" id="edit_kdMCU">
                            </div>

                            <div class="col-md-3">
                              <label>No Kunjungan</label>
                              <input type="text" class="form-control" name="noKunjungan" id="edit_noKunjungan" readonly>
                            </div>

                            <div class="col-md-3">
                              <label>Kd Provider</label>
                              <input type="text" class="form-control" name="kdProvider" id="edit_kdProvider" readonly>
                            </div>

                            <div class="col-md-3">
                              <label>Tanggal Pelayanan</label>
                              <input type="text" class="form-control" name="tglPelayanan" id="edit_tglPelayanan" readonly>
                            </div>


                            

                            <div class="row g-3">
                                <h5>Pemeriksaan Fisik</h5>
                                <div class="col-md-3">
                                  <label>Tekanan Darah Sistole</label>
                                  <input type="text" class="form-control" name="tds" id="edit_tds">
                                </div>
                                <div class="col-md-3">
                                  <label>Tekanan Darah Diastole</label>
                                  <input type="text" class="form-control" name="tdd" id="edit_tdd">
                                </div>
                            </div>

                            <div class="row g-3">
                                <h5>Pemeriksaan Darah Rutin</h5>
                                <div class="col-md-3">
                                  <label>Darah Rutin Hemo</label>
                                  <input type="text" class="form-control" name="darahRutinHemo" id="edit_darahRutinHemo">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Leu</label>
                                  <input type="text" class="form-control" name="darahRutinLeu" id="edit_darahRutinLeu">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Erit</label>
                                  <input type="text" class="form-control" name="darahRutinErit" id="edit_darahRutinErit">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Laju</label>
                                  <input type="text" class="form-control" name="darahRutinLaju" id="edit_darahRutinLaju">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Hema</label>
                                  <input type="text" class="form-control" name="darahRutinHema" id="edit_darahRutinHema">
                                </div>
                                <div class="col-md-3">
                                  <label>Darah Rutin Trom</label>
                                  <input type="text" class="form-control" name="darahRutinTrom" id="edit_darahRutinTrom">
                                </div>
                            </div>

                            <div class="row g-3">
                                <h5>Profil Lipil</h5>
                                <div class="col-md-3">
                                  <label>Lemak Darah HDL</label>
                                  <input type="text" class="form-control" name="lemakDarahHDL" id="edit_lemakDarahHDL">
                                </div>
                                <div class="col-md-3">
                                  <label>Lemak Darah LDL</label>
                                  <input type="text" class="form-control" name="lemakDarahLDL" id="edit_lemakDarahLDL">
                                </div>
                                <div class="col-md-3">
                                  <label>Lemak Darah Chol</label>
                                  <input type="text" class="form-control" name="lemakDarahChol" id="edit_lemakDarahChol">
                                </div>
                                <div class="col-md-3">
                                  <label>Lemak Darah Trigil</label>
                                  <input type="text" class="form-control" name="lemakDarahTrigli" id="edit_lemakDarahTrigli">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Gula Darah</h5>
                                <div class="col-md-3">
                                  <label>Gula Darah Sewaktu</label>
                                  <input type="text" class="form-control" name="gulaDarahSewaktu" id="edit_gulaDarahSewaktu">
                                </div>
                                <div class="col-md-3">
                                  <label>Gula Darah Puasa</label>
                                  <input type="text" class="form-control" name="gulaDarahPuasa" id="edit_gulaDarahPuasa">
                                </div>
                                <div class="col-md-3">
                                  <label>Gula Darah Post Prandial</label>
                                  <input type="text" class="form-control" name="gulaDarahPostPrandial" id="edit_gulaDarahPostPrandial">
                                </div>
                                <div class="col-md-3">
                                  <label>Gula Darah HbA1c</label>
                                  <input type="text" class="form-control" name="gulaDarahHbA1c" id="edit_gulaDarahHbA1c">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Fungsi Hati</h5>
                                <div class="col-md-3">
                                  <label>Fungsi Hati SGOT</label>
                                  <input type="text" class="form-control" name="fungsiHatiSGOT" id="edit_fungsiHatiSGOT">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati SGPT</label>
                                  <input type="text" class="form-control" name="fungsiHatiSGPT" id="edit_fungsiHatiSGPT">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati Gamma</label>
                                  <input type="text" class="form-control" name="fungsiHatiGamma" id="edit_fungsiHatiGamma">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati Protkual</label>
                                  <input type="text" class="form-control" name="fungsiHatiProtKual" id="edit_fungsiHatiProtKual">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Hati Albumin</label>
                                  <input type="text" class="form-control" name="fungsiHatiAlbumin" id="edit_fungsiHatiAlbumin">
                                </div>
                            </div>
                             <div class="row g-3">
                                <h5>Fungsi Ginjal</h5>
                                <div class="col-md-3">
                                  <label>Fungsi Ginjal Crea</label>
                                  <input type="text" class="form-control" name="fungsiGinjalCrea" id="edit_fungsiGinjalCrea">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Ginjal Ureum</label>
                                  <input type="text" class="form-control" name="fungsiGinjalUreum" id="edit_fungsiGinjalUreum">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Ginjal Asam</label>
                                  <input type="text" class="form-control" name="fungsiGinjalAsam" id="edit_fungsiGinjalAsam">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Fungsi Jantung</h5>
                                <div class="col-md-3">
                                  <label>Fungsi Jantung Abi</label>
                                  <input type="text" class="form-control" name="fungsiJantungABI" id="edit_fungsiJantungABI">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Jantung EKG</label>
                                  <input type="text" class="form-control" name="fungsiJantungEKG" id="edit_fungsiJantungEKG">
                                </div>
                                <div class="col-md-3">
                                  <label>Fungsi Jantung Echo</label>
                                  <input type="text" class="form-control" name="fungsiJantungEcho" id="edit_fungsiJantungEcho">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Pemeriksaan Mata</h5>
                                <div class="col-md-3">
                                  <label>Funduskopi</label>
                                  <input type="text" class="form-control" name="funduskopi" id="edit_funduskopi">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Pemeriksaan Radiologi</h5>
                                <div class="col-md-3">
                                  <label>Radiologi Foto</label>
                                  <input type="text" class="form-control" name="radiologiFoto" id="edit_radiologiFoto">
                                </div>
                            </div>
                            <div class="row g-3">
                                <h5>Pemeriksaan Tambahan</h5>
                                <div class="col-md-3">
                                  <label>Pemeriksaan Lain</label>
                                  <input type="text" class="form-control" name="pemeriksaanLain" id="edit_pemeriksaanLain">
                                </div>
                                <div class="col-md-3">
                                  <label>Keterangan</label>
                                  <input type="text" class="form-control" name="keterangan" id="edit_keterangan">
                                </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>



    <script>
        function editData(
            kdMCUlokal,
            kdMCU,
            noKunjungan, 
            kdProvider, 
            tglPelayanan,
            tds,
            tdd,
            darahRutinHemo,
            radiologiFoto,
            
            darahRutinLeu,
            darahRutinErit,
            darahRutinLaju,
            darahRutinHema,
            darahRutinTrom,
            lemakDarahHDL,
            lemakDarahLDL,
            lemakDarahChol,
            lemakDarahTrigli,
            gulaDarahSewaktu,
            gulaDarahPuasa,
            gulaDarahPostPrandial,
            gulaDarahHbA1c,
            fungsiHatiSGOT,
            fungsiHatiSGPT,
            fungsiHatiGamma,
            fungsiHatiProtKual,
            fungsiHatiAlbumin,
            fungsiGinjalCrea,
            fungsiGinjalUreum,
            fungsiGinjalAsam,
            fungsiJantungABI,
            fungsiJantungEKG,
            fungsiJantungEcho,
            funduskopi,
            pemeriksaanLain,
            keterangan
             ) {
        document.getElementById('edit_kdMCUlokal').value = kdMCUlokal;         // hidden (untuk submit)
        document.getElementById('edit_kdMCU').value = kdMCU;         // hidden (untuk submit)
        document.getElementById('edit_kdMCU_display').value = kdMCU; // hanya untuk ditampilkan
        document.getElementById('edit_noKunjungan').value = noKunjungan;
        document.getElementById('edit_kdProvider').value = kdProvider;
        document.getElementById('edit_tglPelayanan').value = tglPelayanan;
        document.getElementById('edit_tds').value = tds;
        document.getElementById('edit_tdd').value = tdd;
        document.getElementById('edit_radiologiFoto').value = radiologiFoto;
        document.getElementById('edit_darahRutinHemo').value = darahRutinHemo;
        document.getElementById('edit_darahRutinLeu').value = darahRutinLeu;
        document.getElementById('edit_darahRutinErit').value = darahRutinErit;
        document.getElementById('edit_darahRutinLaju').value = darahRutinLaju;
        document.getElementById('edit_darahRutinHema').value = darahRutinHema;
        document.getElementById('edit_darahRutinTrom').value = darahRutinTrom;
        document.getElementById('edit_lemakDarahHDL').value = lemakDarahHDL;
        document.getElementById('edit_lemakDarahLDL').value = lemakDarahLDL;
        document.getElementById('edit_lemakDarahChol').value = lemakDarahChol;
        document.getElementById('edit_lemakDarahTrigli').value = lemakDarahTrigli;
        document.getElementById('edit_gulaDarahSewaktu').value = gulaDarahSewaktu;
        document.getElementById('edit_gulaDarahPuasa').value = gulaDarahPuasa;
        document.getElementById('edit_gulaDarahPostPrandial').value = gulaDarahPostPrandial;
        document.getElementById('edit_gulaDarahHbA1c').value = gulaDarahHbA1c;
        document.getElementById('edit_fungsiHatiSGOT').value = fungsiHatiSGOT;
        document.getElementById('edit_fungsiHatiSGPT').value = fungsiHatiSGPT;
        document.getElementById('edit_fungsiHatiGamma').value = fungsiHatiGamma;
        document.getElementById('edit_fungsiHatiProtKual').value = fungsiHatiProtKual;
        document.getElementById('edit_fungsiHatiAlbumin').value = fungsiHatiAlbumin;
        document.getElementById('edit_fungsiGinjalCrea').value = fungsiGinjalCrea;
        document.getElementById('edit_fungsiGinjalUreum').value = fungsiGinjalUreum;
        document.getElementById('edit_fungsiGinjalAsam').value = fungsiGinjalAsam;
        document.getElementById('edit_fungsiJantungABI').value = fungsiJantungABI;
        document.getElementById('edit_fungsiJantungEKG').value = fungsiJantungEKG;
        document.getElementById('edit_fungsiJantungEcho').value = fungsiJantungEcho;
        document.getElementById('edit_funduskopi').value = funduskopi;
        document.getElementById('edit_pemeriksaanLain').value = pemeriksaanLain;
        document.getElementById('edit_keterangan').value = keterangan;
        

        var modal = new bootstrap.Modal(document.getElementById('modalEdit'));
        modal.show();
    }
    </script>

    <script>
        function detaildata(
            kdMCUlokal,
            kdMCU,
            noKunjungan, 
            kdProvider, 
            tglPelayanan,
            tds,
            tdd,
            darahRutinHemo,
            radiologiFoto,
            
            darahRutinLeu,
            darahRutinErit,
            darahRutinLaju,
            darahRutinHema,
            darahRutinTrom,
            lemakDarahHDL,
            lemakDarahLDL,
            lemakDarahChol,
            lemakDarahTrigli,
            gulaDarahSewaktu,
            gulaDarahPuasa,
            gulaDarahPostPrandial,
            gulaDarahHbA1c,
            fungsiHatiSGOT,
            fungsiHatiSGPT,
            fungsiHatiGamma,
            fungsiHatiProtKual,
            fungsiHatiAlbumin,
            fungsiGinjalCrea,
            fungsiGinjalUreum,
            fungsiGinjalAsam,
            fungsiJantungABI,
            fungsiJantungEKG,
            fungsiJantungEcho,
            funduskopi,
            pemeriksaanLain,
            keterangan
             ) {
        document.getElementById('edit_kdMCUlokal').value = kdMCUlokal;         // hidden (untuk submit)
        document.getElementById('edit_kdMCU').value = kdMCU;         // hidden (untuk submit)
        document.getElementById('edit_kdMCU_display').value = kdMCU; // hanya untuk ditampilkan
        document.getElementById('edit_noKunjungan').value = noKunjungan;
        document.getElementById('edit_kdProvider').value = kdProvider;
        document.getElementById('edit_tglPelayanan').value = tglPelayanan;
        document.getElementById('edit_tds').value = tds;
        document.getElementById('edit_tdd').value = tdd;
        document.getElementById('edit_radiologiFoto').value = radiologiFoto;
        document.getElementById('edit_darahRutinHemo').value = darahRutinHemo;
        document.getElementById('edit_darahRutinLeu').value = darahRutinLeu;
        document.getElementById('edit_darahRutinErit').value = darahRutinErit;
        document.getElementById('edit_darahRutinLaju').value = darahRutinLaju;
        document.getElementById('edit_darahRutinHema').value = darahRutinHema;
        document.getElementById('edit_darahRutinTrom').value = darahRutinTrom;
        document.getElementById('edit_lemakDarahHDL').value = lemakDarahHDL;
        document.getElementById('edit_lemakDarahLDL').value = lemakDarahLDL;
        document.getElementById('edit_lemakDarahChol').value = lemakDarahChol;
        document.getElementById('edit_lemakDarahTrigli').value = lemakDarahTrigli;
        document.getElementById('edit_gulaDarahSewaktu').value = gulaDarahSewaktu;
        document.getElementById('edit_gulaDarahPuasa').value = gulaDarahPuasa;
        document.getElementById('edit_gulaDarahPostPrandial').value = gulaDarahPostPrandial;
        document.getElementById('edit_gulaDarahHbA1c').value = gulaDarahHbA1c;
        document.getElementById('edit_fungsiHatiSGOT').value = fungsiHatiSGOT;
        document.getElementById('edit_fungsiHatiSGPT').value = fungsiHatiSGPT;
        document.getElementById('edit_fungsiHatiGamma').value = fungsiHatiGamma;
        document.getElementById('edit_fungsiHatiProtKual').value = fungsiHatiProtKual;
        document.getElementById('edit_fungsiHatiAlbumin').value = fungsiHatiAlbumin;
        document.getElementById('edit_fungsiGinjalCrea').value = fungsiGinjalCrea;
        document.getElementById('edit_fungsiGinjalUreum').value = fungsiGinjalUreum;
        document.getElementById('edit_fungsiGinjalAsam').value = fungsiGinjalAsam;
        document.getElementById('edit_fungsiJantungABI').value = fungsiJantungABI;
        document.getElementById('edit_fungsiJantungEKG').value = fungsiJantungEKG;
        document.getElementById('edit_fungsiJantungEcho').value = fungsiJantungEcho;
        document.getElementById('edit_funduskopi').value = funduskopi;
        document.getElementById('edit_pemeriksaanLain').value = pemeriksaanLain;
        document.getElementById('edit_keterangan').value = keterangan;
        

        var modal = new bootstrap.Modal(document.getElementById('modalDetail'));
        modal.show();
    }
    </script>
    </body>
</html>
