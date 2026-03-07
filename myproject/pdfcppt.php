<?php
require_once('tcpdf/tcpdf.php'); // Pastikan path sesuai

// Ambil data dari parameter GET
$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 
$nik_dokter = isset($_GET['nik_dokter']) ? $_GET['nik_dokter'] : ''; 

// Koneksi ke database
$koneksi = new mysqli("192.168.3.250", "rssl", "s1ntluc14", "sik");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");
// Gunakan prepared statement untuk keamanan
$query = $koneksi->prepare("
    SELECT pemeriksaan_ranap.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pasien.umur, 
        pemeriksaan_ranap.tgl_perawatan, pemeriksaan_ranap.jam_rawat, pemeriksaan_ranap.suhu_tubuh, 
        pemeriksaan_ranap.tensi, pemeriksaan_ranap.nadi, pemeriksaan_ranap.respirasi, pemeriksaan_ranap.tinggi, 
        pemeriksaan_ranap.berat, pemeriksaan_ranap.spo2, pemeriksaan_ranap.gcs, pemeriksaan_ranap.kesadaran, 
        pemeriksaan_ranap.keluhan, pemeriksaan_ranap.pemeriksaan, pemeriksaan_ranap.penilaian, pemeriksaan_ranap.rtl, 
        pemeriksaan_ranap.instruksi, pemeriksaan_ranap.evaluasi, pegawai.nama
    FROM pemeriksaan_ranap
    LEFT JOIN reg_periksa ON pemeriksaan_ranap.no_rawat = reg_periksa.no_rawat
    LEFT JOIN pasien ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
    LEFT JOIN pegawai ON pemeriksaan_ranap.nip = pegawai.nik  
    WHERE pemeriksaan_ranap.no_rawat = ? 
    AND pemeriksaan_ranap.nip = ?
");
$query->bind_param("ss", $no_rawat, $nik_dokter);
$query->execute();
$result = $query->get_result();

// Ambil data pasien dari baris pertama
$data_pasien = $result->fetch_assoc();

// Buat objek PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
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
        <td colspan="6" style="text-align: center; font-weight: bold; border: 1px solid black;">
            CATATAN PERKEMBANGAN PASIEN <br> TERINTEGRASI RAWAT INAP
        </td>
        <td colspan="6" style="border: 1px solid black;">
            <b>NO RM:</b> ' . htmlspecialchars($data_pasien['no_rkm_medis']) . ' <br>
            <b>NAMA:</b> ' . htmlspecialchars($data_pasien['nm_pasien']) . ' <br>
            <b>TGL LAHIR/UMUR:</b> ' . htmlspecialchars($data_pasien['umur']) . ' 
        </td>
    </tr>
    <tr>
        <th style="width: 15%; text-align: center; border: 1px solid black;">Tgl/Jam</th>
        <th style="width: 15%; text-align: center; border: 1px solid black;">Profesional Pemberi Asuhan</th>
        <th style="width: 28%; text-align: center; border: 1px solid black;">Hasil Asesmen Pasien dan Pemberian Layanan</th>
        <th style="width: 27%; text-align: center; border: 1px solid black;">Instruksi PPA Termasuk Pasca Bedah</th>
        <th style="width: 15%; text-align: center; border: 1px solid black;">Review dan Verifikasi</th>
    </tr>';

// Tambahkan data ke dalam tabel
do {
    $html .= '<tr>
        <td style="border: 1px solid black;">' . htmlspecialchars($data_pasien['tgl_perawatan']) . '<br>' . htmlspecialchars($data_pasien['jam_rawat']) . '</td>
        <td style="border: 1px solid black;">' . htmlspecialchars($data_pasien['nama']) . '</td>
        <td style="border: 1px solid black;">
            <b>S:</b> ' . htmlspecialchars($data_pasien['keluhan']) . '<br>
            <b>O:</b> ' . htmlspecialchars($data_pasien['pemeriksaan']) . '<br>
            <b>A:</b> ' . htmlspecialchars($data_pasien['penilaian']) . '<br>
            <b>P:</b> ' . htmlspecialchars($data_pasien['rtl']) . '<br>
            <b>TB:</b> ' . htmlspecialchars($data_pasien['tensi']) . ' | 
            <b>HR:</b> ' . htmlspecialchars($data_pasien['nadi']) . ' | <br>
            <b>RR:</b> ' . htmlspecialchars($data_pasien['respirasi']) . ' | 
            <b>SpO2:</b> ' . htmlspecialchars($data_pasien['spo2']) . ' | <br>
            <b>Temp:</b> ' . htmlspecialchars($data_pasien['suhu_tubuh']) . '°C
        </td>
        <td style="border: 1px solid black;">
            <b>Evaluasi:</b> ' . htmlspecialchars($data_pasien['evaluasi']) . '<br>
        </td>
        <td style="border: 1px solid black;"></td>
    </tr>';
} while ($data_pasien = $result->fetch_assoc());

$html .= '</table>';

// Tambahkan ke PDF
$pdf->SetFont('times', '', 12);
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Laporan_Pemeriksaan_Ranap.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
