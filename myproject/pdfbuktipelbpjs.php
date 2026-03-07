<?php
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





// Ambil data dari parameter GET
$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 
$jam_rawat = isset($_GET['jam_rawat']) ? $_GET['jam_rawat'] : '';
$tgl_perawatan1 = isset($_GET['tgl_perawatan1']) ? $_GET['tgl_perawatan1'] : '';
$tgl_perawatan2 = isset($_GET['tgl_perawatan2']) ? $_GET['tgl_perawatan2'] : '';

// var_dump($jam_rawat, $tgl_perawatan);
// die();

// Koneksi ke database
$koneksi = new mysqli("192.168.3.250", "rssl", "s1ntluc14", "sik");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");
// Gunakan prepared statement untuk keamanan
$query = $koneksi->prepare("
SELECT pr.*, rp.*, p.*, pk.nm_poli, dk.nm_dokter
FROM pemeriksaan_ralan pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pk ON rp.kd_poli = pk.kd_poli
LEFT JOIN dokter dk ON rp.kd_dokter = dk.kd_dokter
WHERE pr.no_rawat = ?
AND pr.nip = rp.kd_dokter
LIMIT 1
");
$query->bind_param("s", $no_rawat);
$query->execute();

// Ambil hasil
$data_pasien = $query->get_result()->fetch_assoc();
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
$result_icd10 = $koneksi->query($sql_icd10);

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
$result_icd9 = $koneksi->query($sql_icd9);

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
$result_cariObat = $koneksi->query($cari_obat);

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
    $logo_path = 'http://localhost/klinik2/img/logo.png';
}
if (!file_exists($logo_paripurna)) {
    $logo_paripurna = 'http://localhost/klinik2/img/paripurna.png';
}

// Buat tabel header
$html = '
<table cellspacing="0" cellpadding="3">
    
    <tr style="background-color: #F0F0DC;">
        <td colspan="10" style="text-align: right; font-weight: bold; ">
            RM. 10.8/BP/REV.I/2025
        </td>
    </tr>

    <tr>
        <td colspan="2" style="text-align: center; vertical-align: middle; height: 80px;">
        <span></span><br>
            <img src="' . $logo_path . '" width="70">
        </td>
        <td colspan="6" style="text-align: center;">
            <b style="font-size:17px;">Rumah Sakit Umum Sint Lucia</b><br>
            <b>Jl. Sisingamangaraja No. 171/173</b><br>
            <b>Kel. Pasar Siborongborong, Kec. Siborongborong</b><br>
            <b>Kab. Tapanuli Utara – Sumatera Utara</b><br>
            <b>Telp: 0852-6190-2900, Email: <font color = "blue"><u>rssintlucia@gmail.com</u></font></b><br>
            <b>Nomor: 076/SERT-AKR/LAM-KPRS/Set/XII/2022</b><br>
        </td>
        <td colspan="2" style="text-align: center;">
            <img src="' . $logo_paripurna . '" width="70">
        </td>
    </tr>
    <tr>
        <td colspan="10" style="border-top: 2px solid black;"></td>
    </tr>
    <tr style="background-color: #F0F0DC;">
    <td colspan="2" style="border-left: 1px solid black; border-top: 1px solid black; width:18%">
            Nama<br>
            Tanggal Lahir<br>
            No. Rekam Medis 
        </td>
        <td colspan="1" style="border-top: 1px solid black; width:3% ">
            :<br>
            :<br>
            : 
        </td>
    <td colspan="4" style="border-top: 1px solid black; width:44%">
            ' . htmlspecialchars($data_pasien['nm_pasien'] ?? '') . '  (' . htmlspecialchars($data_pasien['jk'] ?? '') . ')  <br>
            ' . htmlspecialchars($data_pasien['tgl_lahir'] ?? '') . '  (' . htmlspecialchars($data_pasien['umurdaftar'] ?? '') . '' . htmlspecialchars($data_pasien['sttsumur'] ?? '') . ') <br>
            ' . htmlspecialchars($data_pasien['no_rkm_medis'] ?? '') . ' 
        </td>
        <td colspan="5" style="text-align: center;border-top: 1px solid black;border-left: 1px solid black; border-right: 1px solid black;width:35%">
        <br><br>
            <i>Mohon Diisi Dengan Lengkap</i>
        </td>

        
    </tr>
    <tr style="background-color: #F0F0DC;">
        <td colspan="12" style="text-align: center; font-weight: bold; border: 1px solid black;">
            DATA MCU 
        </td>
    </tr>
    <br><br>
    <tr>
        <td colspan="12" style="text-align: left; font-weight: bold; border: 1px solid black;">
            Pemeriksaan Fisik
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid black;">
            Tekanan Darah Sistole : 
        </td>
        <td colspan="6" style="border: 1px solid black;">
            Tekanan Darah Diastole : 
        </td>
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; font-weight: bold; border: 1px solid black;">
            Pemeriksaan Darah Rutin
        </td>
    </tr>
    <tr>
        <td colspan="4" style="border: 1px solid black;">
            Darah Rutin Hemo : 
        </td>
        <td colspan="4" style="border: 1px solid black;">
            Darah Rutin Leu : 
        </td>
        <td colspan="4" style="border: 1px solid black;">
            Darah Rutin Erit : 
        </td>
    </tr>
    <tr>
        <td colspan="4" style="border: 1px solid black;">
            Darah Rutin Laju : 
        </td>
        <td colspan="4" style="border: 1px solid black;">
            Darah Rutin Hema : 
        </td>
        <td colspan="4" style="border: 1px solid black;">
            Darah Rutin Trom : 
        </td>
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; font-weight: bold; border: 1px solid black;">
            Profil Lipil
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border: 1px solid black;">
            Lemak Darah HDL :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Lemak Darah LDL :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Lemak Darah Chol :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Lemak Darah Trigil :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; font-weight: bold; border: 1px solid black;">
            Gula Darah
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border: 1px solid black;">
            Gula Darah Sewaktu :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Gula Darah Puasa :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Gula Darah Post Prandial :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Lemak Darah Trigil :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="text-align: left; font-weight: bold; border: 1px solid black;">
            Fungsi Hati
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border: 1px solid black;">
            Gula Darah Sewaktu :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Gula Darah Puasa :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Gula Darah Post Prandial :
        </td>
        <td colspan="3" style="border: 1px solid black;">
            Lemak Darah Trigil :
        </td>
    </tr>
    ';






$html .= '</table>';

// Tambahkan ke PDF
$pdf->SetFont('times', '', 11);
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Bukti_Pelayanan_BPJS_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data_pasien['nm_pasien'] ?? '') . '.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
