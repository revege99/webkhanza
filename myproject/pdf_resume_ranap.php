<?php
require_once('tcpdf/tcpdf.php');

$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 


// var_dump($no_rawat);
// die();
// Koneksi ke database
$koneksi = new mysqli("192.168.3.250", "rssl", "s1ntluc14", "sik");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$koneksi->set_charset("utf8mb4");

// Gunakan prepared statement untuk keamanan
$query = $koneksi->prepare("
      SELECT r.*,rp.*, p.*, ki.tgl_masuk, ki.jam_masuk, ki.tgl_keluar, ki.kd_kamar,d.*, ki.jam_keluar
        FROM resume_pasien_ranap AS r
        LEFT JOIN reg_periksa AS rp ON r.no_rawat = rp.no_rawat
        LEFT JOIN pasien AS p ON rp.no_rkm_medis = p.no_rkm_medis
        LEFT JOIN kamar_inap AS ki ON rp.no_rawat = ki.no_rawat
        LEFT JOIN dokter as d on r.kd_dokter = d.kd_dokter
    WHERE r.no_rawat = ?
");
$query->bind_param("s", $no_rawat);
$query->execute();
$result = $query->get_result();

// Ambil data pasien dari baris pertama
$data_pasien = $result->fetch_assoc();
// var_dump( $data_pasien);
// die();

// Buat objek PDF
// $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf = new TCPDF('P', 'mm', array(210,350), true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
// $pdf->SetAutoPageBreak(false, 0);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();
 


// Path gambar logo
$logo_path = '../../img/logo.png'; 
$logo_paripurna = '../../paripurna.png';
$img_awal_medis = '../../semua.png';

// Gunakan URL lokal jika file tidak ditemukan
if (!file_exists($logo_path)) {
    $logo_path = 'http://localhost/klinik2/img/logo.png';
}
if (!file_exists($logo_paripurna)) {
    $logo_paripurna = 'http://localhost/klinik2/img/paripurna.png';
}
if (!file_exists($img_awal_medis)) {
    $img_awal_medis = 'http://localhost/klinik2/img/semua.png';
}
date_default_timezone_set('Asia/Jakarta');
// Buat tabel header
$html = '

<table cellspacing="0" cellpadding="0" >
    <tr>
        <td colspan="2" style="text-align: center; vertical-align: middle; height: 80px;">
        <span></span><br>
            <img src="' . $logo_path . '" width="70">
        </td>
        <td colspan="6" style="text-align: center;">
            <b style="font-size:17px;">Rumah Sakit Umum Sint Lucia</b><br>
            <b style="font-size:12px;">Jl. Sisingamangaraja No. 171/173</b><br>
            <b style="font-size:12px;">Kel. Pasar Siborongborong, Kec. Siborongborong</b><br>
            <b style="font-size:12px;">Kab. Tapanuli Utara – Sumatera Utara</b><br>
            <b style="font-size:12px;">Telp: 0852-6190-2900, Email: <font color = "blue"><u>rssintlucia@gmail.com</u></font></b><br>
            <b style="font-size:12px;">Nomor: 076/SERT-AKR/LAM-KPRS/Set/XII/2022</b><br>
        </td>
        <td colspan="2" style="text-align: center;">
            <img src="' . $logo_paripurna . '" width="70">
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-top: 2px solid black;border-bottom: 1px solid black; text-align: center; vertical-align: middle; font-size: 16px"><b>RESUME MEDIS PASIEN</b></td>
    </tr>
    <tr>
        <td colspan="1" style="  width: 11%;">
            Nama Pasien
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 41%;">
            ' . htmlspecialchars($data_pasien["nm_pasien"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            No. Rekam Medis
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 29%;">
            ' . htmlspecialchars($data_pasien["no_rkm_medis"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="1" style="  width: 11%;">
           Umur
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 41%;">
            ' . htmlspecialchars($data_pasien["umur"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            Ruang
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 29%;">
            ' . htmlspecialchars($data_pasien["kd_kamar"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="1" style="  width: 11%;">
            Tanggal Lahir
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 41%;">
            ' . htmlspecialchars($data_pasien["tgl_lahir"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            Jenis Kelamin
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 29%;">
            ' . htmlspecialchars($data_pasien["jk"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="1" style="  width: 11%;">
            Pekerjaan
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 41%;">
            ' . htmlspecialchars($data_pasien["pekerjaan"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            Tanggal Masuk
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 29%;">
            ' . date("d-m-Y", strtotime($data_pasien["tgl_masuk"])) . '&nbsp;&nbsp;' . htmlspecialchars($data_pasien["jam_masuk"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="1" style="  width: 11%;">
            Alamat
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 41%;">
            ' . htmlspecialchars($data_pasien["alamat"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            Tanggal Keluar
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="width: 29%;">
            ' . date("d-m-Y", strtotime($data_pasien["tgl_keluar"])) . '&nbsp;&nbsp;' . htmlspecialchars($data_pasien["jam_keluar"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="10" style="border-top: 1px solid black; width: 100%"></td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Diagnosa Awal Masuk  : ' . htmlspecialchars($data_pasien["diagnosa_awal"]) . '<br>
            Alasan Masuk Dirawat : ' . htmlspecialchars($data_pasien["alasan"]) . '<br>
            Keluhan Utama Riwayat Penyakit : 
        </td>
    </tr>
    <tr>
        <td style="width: 20px"></td>
        <td colspan="10">
            ' . nl2br(htmlspecialchars($data_pasien['keluhan_utama'])) . '<br>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Pemeriksaan Fisik
        </td>
    </tr>
    <tr>
    <td style="width: 20px"></td>
    <td colspan="8">
        ' . nl2br(htmlspecialchars($data_pasien['pemeriksaan_fisik'])) . '<br>
        
    </td>
</tr>
     <tr>
        <td colspan="10"
        >
            Jalannya Penyakit Selama Perawatan
        </td>
    </tr>
    <tr>
        <td style="width: 20px"></td>
        <td colspan="8">
            ' . nl2br(htmlspecialchars($data_pasien['jalannya_penyakit'])) . '<br>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Pemeriksaan Penunjang Radiologi Terpenting :
        </td>
    </tr>
    <tr>
        <td style="width: 20px"></td>
        <td colspan="8" >
            ' . htmlspecialchars($data_pasien["pemeriksaan_penunjang"]) . '<br>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Pemeriksaan Penunjang Laboratorium Terpenting :
        </td>
    </tr>
    <tr>
        <td style="width: 20px"></td>
        <td colspan="8" >
            ' . htmlspecialchars($data_pasien["hasil_laborat"]) . '<br>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Tindakan/Operasi Selama Perawatan :
        </td>
    </tr>
    <tr>
        <td style="width: 20px"></td>
        <td colspan="8">
            ' . htmlspecialchars($data_pasien["tindakan_dan_operasi"]) . '<br>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Obat-obatan Selama Perawatan :
        </td>
    </tr>
    <tr>
        <td style="width: 20px"></td>
        <td colspan="8" >
            ' . htmlspecialchars($data_pasien["obat_di_rs"]) . '<br>
        </td>
    </tr>
    <tr>
        <td colspan="8" style="">
            Diagnosa Akhir : 
        </td>
        <td colspan="2" style="text-align: center;">
            Kode ICD
        </td>
    </tr>
    <tr>
        <td colspan="3" style="text-indent: 20px; width: 25%;">
            - Diagnosa Utama
        </td>
        <td colspan="6" style="">
            :&nbsp;&nbsp;' . htmlspecialchars($data_pasien["diagnosa_utama"]) . '
        </td>
        <td colspan="2" style=" text-align: center">
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_diagnosa_utama"]) . '&nbsp;&nbsp;)
        </td>
    </tr>
    <tr>
        <td colspan="3" style="text-indent: 20px; width: 25%;">
            - Diagnosa Sekunder
        </td>
        <td colspan="6" style="">
            :&nbsp;&nbsp;1.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["diagnosa_sekunder"]) . '<br>
            :&nbsp;&nbsp;2.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["diagnosa_sekunder2"]) . '<br>
            :&nbsp;&nbsp;3.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["diagnosa_sekunder3"]) . '<br>
            :&nbsp;&nbsp;4.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["diagnosa_sekunder4"]) . '<br>
            
        </td>
        <td colspan="2" style="text-align: center">
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_diagnosa_sekunder"]) . '&nbsp;&nbsp;)<br>
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_diagnosa_sekunder2"]) . '&nbsp;&nbsp;)<br>
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_diagnosa_sekunder3"]) . '&nbsp;&nbsp;)<br>
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_diagnosa_sekunder4"]) . '&nbsp;&nbsp;)<br>
            
        </td>
    </tr>
    <tr>
        <td colspan="3" style="text-indent: 20px; width: 25%;">
            - Prosedur/Tindakan Utama
        </td>
        <td colspan="6" style="">
            :&nbsp;&nbsp;' . htmlspecialchars($data_pasien["prosedur_utama"]) . '
        </td>
        <td colspan="2" style=" text-align: center">
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_prosedur_utama"]) . '&nbsp;&nbsp;)
        </td>
    </tr>
    <tr>
        <td colspan="3" style="text-indent: 20px; width: 25%;">
            - Prosedur/Tindakan Sekunder
        </td>
        <td colspan="6" style="">
            :&nbsp;&nbsp;1.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["prosedur_sekunder"]) . '<br>
            :&nbsp;&nbsp;2.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["prosedur_sekunder2"]) . '<br>
            :&nbsp;&nbsp;3.&nbsp;&nbsp;' . htmlspecialchars($data_pasien["prosedur_sekunder3"]) . '<br>    
        </td>
        <td colspan="2" style="text-align: center">
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_prosedur_sekunder"]) . '&nbsp;&nbsp;)<br>
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_prosedur_sekunder2"]) . '&nbsp;&nbsp;)<br>
            (&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kd_prosedur_sekunder3"]) . '&nbsp;&nbsp;)<br>
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Alergi / Reaksi Obat : ' . htmlspecialchars($data_pasien["alergi"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Diet Selamat Perawatan : 
        </td>
    </tr>
    <tr>
        <td colspan="10" style="text-indent: 20px;">
            ' . htmlspecialchars($data_pasien["diet"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Hasil Lab Yang Belum Selesai (Pending) :
        </td>
    </tr>
    <tr>
        <td colspan="10" style="text-indent: 20px;">
            ' . htmlspecialchars($data_pasien["lab_belum"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="10" style="">
            Intruksi/Anjuran Dan Edukasi (Follow Up) :
        </td>
    </tr>
    <tr>
        <td colspan="10" style="text-indent: 20px;">
            ' . htmlspecialchars($data_pasien["edukasi"]) . '
        </td>
    </tr>

    <tr>
        <td colspan="1" style="  width: 15%;">
            Keadaan Pulang
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 37%;">
            ' . htmlspecialchars($data_pasien["keadaan"]) . ',&nbsp;' . htmlspecialchars($data_pasien["ket_keadaan"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            Cara Keluar
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 29%;">
            ' . htmlspecialchars($data_pasien["cara_keluar"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="1" style="  width: 15%;">
            Dilanjutkan
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 37%;">
            ' . htmlspecialchars($data_pasien["dilanjutkan"]) . ',&nbsp;' . htmlspecialchars($data_pasien["ket_dilanjutkan"]) . '
        </td>


       <td colspan="1" style="  width: 15%;">
            Tanggal Kontrol
        </td>
        <td colspan="1" style="  width: 2%;">
            :
        </td>
        <td colspan="1" style="  width: 29%;">
            ' . htmlspecialchars($data_pasien["kontrol"]) . '
        </td>
    </tr>

    <tr>
        <td colspan="8">
            Obat-obatan Waktu Pulang :
        </td>
    </tr>
    <tr>
         <td colspan="1" style="width: 5%";></td> <!-- offset 2 kolom -->
        <td colspan="6">
            ' . htmlspecialchars($data_pasien["obat_pulang"]) . '
        </td>
    </tr>
    <br>
    <tr>
        <td colspan="3"></td> <!-- offset 2 kolom -->
        <td colspan="3" style="text-align: center;">
            Dokter Penanggung Jawab<br><br>
            <br>
            <br>
            ' . htmlspecialchars($data_pasien["nm_dokter"]) . '
        </td>
    </tr>

    
';
    $html .= '</table>';
    

// Tambahkan ke PDF
$pdf->SetFont('times', '', 9);
$pdf->writeHTML($html, true, false, true, false, '');

// // Pastikan tidak ada spasi tambahan
// $pdf->setCellPaddings(0, 0, 0, 0); 
// $pdf->setCellMargins(0, 0, 0, 0);

// // Tambahkan MultiCell langsung setelah HTML tanpa jarak tambahan
// $pdf->SetY($pdf->GetY() - 7); // Mengurangi jarak ke atas (coba atur -5, -10, dst)
// $pdf->MultiCell(0, 0, htmlspecialchars($data_pasien["keluhan_utama"]), 1, 'L');

// $pdf->Ln(2); // Beri jarak 2mm
// $pdf->MultiCell(0, 0, htmlspecialchars($data_pasien["keluhan_utama"]), 0, 'L');



// $pdf->writeHTMLCell(0, 0, '', '', '<b>Keluhan Utama:</b> ' . htmlspecialchars($data_pasien["keluhan_utama"]), 1, 1, false, true, 'L');



// Output PDF
$pdf->Output('Resume_ranap_' . htmlspecialchars($data_pasien["nm_pasien"]) . '.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
