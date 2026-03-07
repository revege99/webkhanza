<?php
ob_start(); // Aktifkan output buffering agar tidak ganggu TCPDF
error_reporting(0); // Sembunyikan warning/error kecil

require '../function/function_klinik.php';
require_once('tcpdf/tcpdf.php'); // Pastikan path sesuai

function tanggal_indo($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tanggal = explode('-', $tanggal);
    return $tanggal[2] . ' ' . $bulan[(int)$tanggal[1]] . ' ' . $tanggal[0];
}

function formatTanggalIndonesia($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    $timestamp = strtotime($tanggal);
    $nama_hari = $hari[date('l', $timestamp)];
    $tgl = date('j', $timestamp);
    $bln = $bulan[(int)date('n', $timestamp)];
    $thn = date('Y', $timestamp);

    return "$nama_hari, $tgl $bln $thn";
}

function fromatangka($tanggal) {
    return date('d-m-Y', strtotime($tanggal));
}

function hitungIMT($berat, $tinggi) {
    if ($berat > 0 && $tinggi > 0) {
        $tinggi_m = $tinggi / 100; // ubah cm ke meter
        $imt = $berat / ($tinggi_m * $tinggi_m);
        return number_format($imt, 2);
    } else {
        return '-';
    }
}

// Ambil data dari parameter GET
$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 
$jam_rawat = isset($_GET['jam_rawat']) ? $_GET['jam_rawat'] : '';
$tgl_perawatan1 = isset($_GET['tgl_perawatan1']) ? $_GET['tgl_perawatan1'] : '';
$tgl_perawatan2 = isset($_GET['tgl_perawatan2']) ? $_GET['tgl_perawatan2'] : '';

// Jalankan query
$query = $conn->prepare("
SELECT
    rp.no_rkm_medis,
    p.no_peserta,
    p.no_ktp,
    p.nm_pasien,
    p.jk,
    p.no_tlp,
    p.tgl_lahir,
    CONCAT (rp.umurdaftar,' ',rp.sttsumur) AS umur,
    rp.tgl_registrasi AS tgl_pelayanan,
    pp.kdTkp AS jenis_pelayanan,
    p.alamat,
    pku.keluhan,
    pku.nmSadar AS kesadaran,
    pku.sistole,
    pku.respRate,
    pku.tinggibadan,
    pku.lingkarperut,
    pr.suhu_tubuh,
    pku.diastole,
    pku.heartRate,
    pku.beratBadan,
    pku.nmDiag1,
    pku.nmDiag2,
    d.nm_dokter,
    pr.alergi,
    pku.terapi,
    pku.bmhp,
    pr.instruksi,
    pku.NmPrognosa,
    pku.noKunjungan,
    rp.tgl_registrasi
FROM pcare_pendaftaran pp
LEFT JOIN reg_periksa rp ON rp.no_rawat = pp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN pcare_kunjungan_umum pku ON pp.no_rawat = pku.no_rawat
LEFT JOIN pemeriksaan_ralan pr ON pp.no_rawat = pr.no_rawat
LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
WHERE rp.no_rawat = ?
");
$query->bind_param("s", $no_rawat);
$query->execute();

// Ambil hasil
$data_pasien = $query->get_result()->fetch_assoc();

// Hitung IMT setelah data diambil
$imt = hitungIMT($data_pasien['beratBadan'] ?? 0, $data_pasien['tinggibadan'] ?? 0);
// var_dump($data_pasien);
// exit();
if (!$data_pasien) {
    echo '
    <div style="
        width: 100%;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f9f9f9;
        font-family: Arial, sans-serif;
    ">
        <div style="
            text-align: center;
            border: 2px solid #ccc;
            padding: 30px 50px;
            background-color: #fff;
            box-shadow: 2px 2px 12px rgba(0,0,0,0.1);
            border-radius: 10px;
        ">
            <h2 style="color: #d9534f; margin-bottom: 10px;">Data Kosong</h2>
            <p style="font-size: 16px; color: #333;">
                Bukti Pelayanan BPJS untuk <strong>No. Rawat: ' . htmlspecialchars($no_rawat) . '</strong> belum diisi.
            </p>
            <p style="font-size: 14px; color: #555;">
                Silakan cek kembali data pasien atau input data terlebih dahulu.
            </p>
        </div>
    </div>
    ';
    exit; // hentikan proses PDF karena tidak ada data
}



// ✅ Tutup statement setelah selesai
$query->close();


$sql_icd10 = "
    SELECT p.nm_penyakit, p.kd_penyakit
    FROM diagnosa_pasien dp
    JOIN penyakit p ON dp.kd_penyakit = p.kd_penyakit
    WHERE dp.no_rawat = '" . $no_rawat . "'
";
$result_icd10 = $conn->query($sql_icd10);

$icd10_list = [];
while ($row = $result_icd10->fetch_assoc()) {
    $icd10_list[] = htmlspecialchars($row['nm_penyakit'] ?? '') .
                    ' ( ' . htmlspecialchars($row['kd_penyakit'] ?? '') . ' )';
}

// Gabungkan dengan baris baru (<br>)
$icd10_str = implode('<br>', $icd10_list);

$sql_icd9 = "
    SELECT i.deskripsi_pendek, i.kode
    FROM prosedur_pasien pp
    JOIN icd9 i ON pp.kode = i.kode
    WHERE pp.no_rawat = '" . $no_rawat . "'
";
$result_icd9 = $conn->query($sql_icd9);

$icd9_list = [];
while ($row = $result_icd9->fetch_assoc()) {
    $icd9_list[] = htmlspecialchars($row['deskripsi_pendek'] ?? '') .
                   ' ( ' . htmlspecialchars($row['kode'] ?? '') . ' )';
}

$icd9_str = implode('<br>', $icd9_list);



$cari_obat = "
    SELECT rd.kode_brng, db.nama_brng, rd.jml, ks.satuan, rd.aturan_pakai, obo.kode_brng
FROM resep_dokter rd
LEFT JOIN resep_obat ro ON rd.no_resep =  ro.no_resep 
INNER JOIN databarang db ON rd.kode_brng = db.kode_brng
INNER JOIN kodesatuan ks ON db.kode_sat = ks.kode_sat
INNER JOIN obat_bmhp_oksigen obo ON db.kode_brng = obo.kode_brng
 WHERE obo.kode_kat = 1 AND ro.no_rawat = '" . $no_rawat . "'
 
    
";
$result_cariObat = $conn->query($cari_obat);

$cariObat_list = [];
while ($row = $result_cariObat->fetch_assoc()) {
   $cariObat_list[] = 
    '<span style="font-size:9px">' . htmlspecialchars($row['nama_brng'] ?? '') . '</span> ' .
    '<span style="font-size:9px">' . htmlspecialchars($row['jml'] ?? '') . ' ' . htmlspecialchars($row['satuan'] ?? '') . '</span> ' .
    '<span style="color:grey;font-size:9px">s</span> '
   . '<span style="font-size:9px">' . htmlspecialchars($row['aturan_pakai'] ?? '') . '</span> ';

}

$obat = implode('<br>', $cariObat_list);


// Buat objek PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Path gambar logo
$logo_path = '../../img/logo.png'; 
$logo_paripurna = '../../paripurna.png';

// Gunakan URL lokal jika file tidak ditemukan
if (!file_exists($logo_path)) {
    $logo_path = 'http://localhost/webkhanza/img/bpjslogo.png';
}
if (!file_exists($logo_paripurna)) {
    $logo_paripurna = 'http://localhost/klinik2/img/paripurna.png';
}

// Buat tabel header
$html = '
<table cellspacing="0" cellpadding="3">

    <tr>
        <td colspan="4" style="text-align: left; vertical-align: middle; height: 60px;">
        <span></span><br>
            <img src="' . $logo_path . '" width="160">
        </td>
        <td colspan="8" style="text-align: left; vertical-align: middle; height: 60px; ">
        <span></span><br>
            <b style="font-size:17px;">Formulir Klaim Pelayanan Primer</b><br>
            <b>0030B011 - Klinik Pratama ST. MARTINA</b>
        </td>
        
    </tr>
    <tr>
        <td colspan="12" style="border-bottom: 2px solid black;"></td>
    </tr>
    <tr>
        <td colspan="6" style="text-align: left; font-weight: bold; ">
            Indentitas Pasien
        </td>
        <td colspan="6" style="text-align: right; font-weight: bold; ">
            Nomor Kunjungan : ' . htmlspecialchars($data_pasien['noKunjungan'] ?? '') . '
        </td>
    </tr>


    <tr>
    <td colspan="2" style="border-bottom: 1px solid black; width:18%">
            Nomor Rekam Medis<br>
            Nomor Kartu Peserta<br>
            NIK<br>
            Nama<br>
            Jenis Kelamin<br>
            Nomor Hp
        </td>
        <td colspan="1" style="border-bottom: 1px solid black; width:3% ">
            :<br>
            :<br>
            :<br>
            :<br>
            :<br>
            : 
        </td>
    <td colspan="3" style="border-bottom: 1px solid black; width:29%">
            ' . htmlspecialchars($data_pasien['no_rkm_medis'] ?? '') . '<br>
            ' . htmlspecialchars($data_pasien['no_peserta'] ?? '') . '<br>
            ' . htmlspecialchars($data_pasien['no_ktp'] ?? '') . '<br>
            ' . htmlspecialchars($data_pasien['nm_pasien'] ?? '') . '  (' . htmlspecialchars($data_pasien['jk'] ?? '') . ')  <br>
            ' . htmlspecialchars($data_pasien['jk'] ?? '') . '<br>
            ' . htmlspecialchars($data_pasien['no_tlp'] ?? '') . '
             
        </td>
        <td colspan="2" style="border-bottom: 1px solid black; width:18%">
            Tanggal Lahir<br>
            Umur<br>
            Tanggal Pelayanan<br>
            Jenis Pelayanan<br>
            Alamat
        </td>
        <td colspan="1" style="border-bottom: 1px solid black; width:3% ">
            :<br>
            :<br>
            :<br>
            :<br>
            : 
        </td>
        <td colspan="3" style="border-bottom: 1px solid black; width:29%">
            ' . htmlspecialchars(!empty($data_pasien['tgl_lahir']) ? date('d/m/Y', strtotime($data_pasien['tgl_lahir'])) : '') . ' <br>
            ' . htmlspecialchars($data_pasien['umur'] ?? '') . '<br>
            ' . htmlspecialchars(!empty($data_pasien['tgl_registrasi']) ? date('d/m/Y', strtotime($data_pasien['tgl_registrasi'])) : '') . ' <br>
            ' . htmlspecialchars(
                !empty($data_pasien['jenis_pelayanan'])
                    ? (
                        (substr($data_pasien['jenis_pelayanan'], 0, 2) == '10')
                            ? 'RJTP'
                            : ((substr($data_pasien['jenis_pelayanan'], 0, 2) == '20') ? 'RITP' : '')
                      )
                    : ''
            ) . '
             <br>
            ' . htmlspecialchars($data_pasien['alamat'] ?? '') . '

            
        </td>

    </tr>

    <tr>
        <td colspan="12" style="text-align:left;border-bottom: 1px solid black;">Pelayanan</td>

    </tr>
    
  
   <tr>
    <td colspan="12" style="text-align:left; ">Pasien / Keluarga menyatakan bahwa benar, pasien telah mendapatkan pelayanan tanpa dikenakan iur biaya serta memberikan persetujuan kepada BPJS kesehatan untuk menggunakan informasi medis yang tertera di status kesehatan pasien sebagai salah satu syarat pengajuan kalaim pelayanan program JKN</td>
    </tr>
    <br>
 <tr>
    <td colspan="7" style="font-weight:bold;text-align:left;"></td>
    <td colspan="5" style=" text-align:left;">Penanggung Jawab Klaim</td>
  </tr>
<br>
  <tr>
    <td colspan="6" style="text-align:left;"></td>
    <td colspan="6" style="text-align:left;"></td>
  </tr>
  <br><br>
   <tr>
    <td colspan="7" style="text-align:left;"></td>
    <td colspan="5" style="text-align:left;"><b>' . htmlspecialchars($data_pasien['nm_dokter'] ?? '') . '</b> <br>' . htmlspecialchars($data_pasien['no_tlp'] ?? '') . '</td>
  </tr>
    
    ';






$html .= '</table>';

// Tambahkan ke PDF
$pdf->SetFont('times', '', 10);
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('spp' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data_pasien['nm_pasien'] ?? '') . '.pdf', 'I');

// Tutup conn
$conn->close();
?>
