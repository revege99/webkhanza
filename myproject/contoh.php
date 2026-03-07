<?php
require_once('tcpdf/tcpdf.php'); // Sesuaikan dengan lokasi TCPDF

// Ambil data dari parameter GET dengan validasi
$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : ''; 
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : ''; 
$nik_dokter = isset($_GET['nik_dokter']) ? $_GET['nik_dokter'] : ''; 

// Koneksi ke database
$koneksi = new mysqli("localhost", "root", "s1ntluc14", "sik");

// Pastikan tidak ada error saat koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Gunakan prepared statement untuk menghindari SQL Injection
$query = $koneksi->prepare("
    SELECT pemeriksaan_ranap.no_rawat, reg_periksa.no_rkm_medis, pasien.nm_pasien, pemeriksaan_ranap.tgl_perawatan, 
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
    WHERE pemeriksaan_ranap.no_rawat = ? 
    AND kamar_inap.tgl_masuk BETWEEN ? AND ? 
    AND pemeriksaan_ranap.nip = ? 
");

// Bind parameter
$query->bind_param("ssss", $no_rawat, $tanggal_awal, $tanggal_akhir, $nik_dokter);
$query->execute();
$result = $query->get_result();

// Buat objek PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false); // Hapus garis paling atas
$pdf->setPrintFooter(true); // Footer tetap ada
$pdf->SetMargins(10, 10, 10); 
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Tambahkan tabel ke dalam PDF
$html = '<table border="1" cellspacing="0" cellpadding="5">
    <tr>
        <td colspan="3"><img src="../img/logo.png" alt="Gambar Contoh" width="300"></td>
        <td>Rumah Sakit Sint Lucia</td>
    </tr>
    <tr>
        <td colspan="6" style="padding: 5px; text-align: center; font-weight: bold; border: 1px solid black;">
            CATATAN PERKEMBANGAN PASIEN <br> TERINTEGRASI RAWAT INAP
        </td>
        <td colspan="6" style="padding: 5px; border: 1px solid black;">
            <b>NO RM:</b> 02-37-23 <br>
            <b>NAMA:</b> Tionarida Purba <br>
            <b>TGL LAHIR/UMUR:</b> 
        </td>
    </tr>
    <tr>
        <th style="width: 15%; text-align: center;">Tgl/Jam</th>
        <th style="width: 15%; text-align: center;">Profesional Pemberi Asuhan</th>
        <th style="width: 25%; text-align: center;">Hasil Asesmen Pasien dan Pemberian Pelayanan</th>
        <th style="width: 30%; text-align: center;">Instruksi PPA Termasuk Pasca Bedah</th>
        <th style="width: 15%; text-align: center;">Review dan Verifikasi</th>
    </tr>';

while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['tgl_perawatan']) . '<br>' . htmlspecialchars($row['jam_rawat']) . '</td>
        <td>' . htmlspecialchars($row['nama']) . '</td>
        <td>
            <b>S:</b> ' . htmlspecialchars($row['keluhan']) . '<br>
            <b>O:</b> ' . htmlspecialchars($row['pemeriksaan']) . '<br>
            <b>A:</b> ' . htmlspecialchars($row['penilaian']) . '<br>
            <b>P:</b> ' . htmlspecialchars($row['rtl']) . '<br>
            <b>TB:</b> ' . htmlspecialchars($row['tensi']) . ' | 
            <b>HR:</b> ' . htmlspecialchars($row['nadi']) . ' | 
            <b>RR:</b> ' . htmlspecialchars($row['respirasi']) . ' | 
            <b>SpO2:</b> ' . htmlspecialchars($row['spo2']) . '<br>
            <b>Temp:</b> ' . htmlspecialchars($row['suhu_tubuh']) . '°C
        </td>
        <td>
            <b>Evaluasi:</b> ' . htmlspecialchars($row['evaluasi']) . '<br>
            <b>Instruksi:</b> ' . htmlspecialchars($row['instruksi']) . '
        </td>
        <td></td>
    </tr>';
}

$html .= '</table>';

// Tulis ke PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Laporan_Pemeriksaan_Ranap.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
