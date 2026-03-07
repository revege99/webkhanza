<?php
require_once('tcpdf/tcpdf.php'); // Sesuaikan dengan lokasi TCPDF

$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : ''; 
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : ''; 
$nik_dokter = isset($_GET['nik_dokter']) ? $_GET['nik_dokter'] : ''; 

// var_dump($_GET);
// die();

// Koneksi ke database
$koneksi = new mysqli("localhost", "root", "", "sik");

// Pastikan tidak ada error saat koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Ambil data dari parameter GET
$no_rawat = $_GET['no_rawat'];
$tanggal_awal = $_GET['tanggal_awal'];
$tanggal_akhir = $_GET['tanggal_akhir'];
$nik_dokter = $_GET['nik_dokter'];

// Query database
$query = "SELECT pemeriksaan_ranap.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pemeriksaan_ranap.tgl_perawatan, 
    pemeriksaan_ranap.jam_rawat, pemeriksaan_ranap.suhu_tubuh, pemeriksaan_ranap.tensi, pemeriksaan_ranap.nadi, 
    pemeriksaan_ranap.respirasi, pemeriksaan_ranap.tinggi, pemeriksaan_ranap.berat, pemeriksaan_ranap.spo2, 
    pemeriksaan_ranap.gcs, pemeriksaan_ranap.kesadaran, pemeriksaan_ranap.keluhan, pemeriksaan_ranap.pemeriksaan, 
    pemeriksaan_ranap.penilaian, pemeriksaan_ranap.rtl, pemeriksaan_ranap.instruksi, pemeriksaan_ranap.evaluasi, 
    pegawai.nama
    FROM pemeriksaan_ranap
    LEFT JOIN reg_periksa on pemeriksaan_ranap.no_rawat = reg_periksa.no_rawat
    LEFT JOIN pasien on reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    LEFT JOIN kamar_inap on reg_periksa.no_rawat = kamar_inap.no_rawat
    LEFT JOIN pegawai on pemeriksaan_ranap.nip = pegawai.nik  
    WHERE pemeriksaan_ranap.no_rawat = '$no_rawat'
    AND kamar_inap.tgl_masuk BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
    AND pemeriksaan_ranap.nip = '$nik_dokter' ";

$result = $koneksi->query($query);

// Buat objek PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nama RS');
// $pdf->SetTitle('Laporan Pemeriksaan Ranap');
// $pdf->SetHeaderData('', 0, 'Laporan Pemeriksaan Ranap', 'Tanggal: ' . date('d-m-Y'));
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 12));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', 10));
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Tambahkan tabel ke dalam PDF
$html = '<h3 style="text-align:center;">RUMAH SAKIT UMUM SINT LUCIA</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>No Rawat</th>
        <th>No RM</th>
        <th>Nama Pasien</th>
        <th>Tgl Perawatan</th>
        <th>Jam</th>
        <th>Dokter</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $row['no_rawat'] . '</td>
        <td>' . $row['no_rkm_medis'] . '</td>
        <td>' . $row['nm_pasien'] . '</td>
        <td>' . $row['tgl_perawatan'] . '</td>
        <td>' . $row['jam_rawat'] . '</td>
        <td>' . $row['nama'] . '</td>
    </tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Laporan_Pemeriksaan_Ranap.pdf', 'I');

$koneksi->close();
?>
