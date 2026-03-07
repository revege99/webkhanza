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
      SELECT p.*, ps.nm_pasien, ps.tgl_lahir, ps.jk, d.nm_dokter, r.hubunganpj, r.no_rkm_medis, ps.umur
    FROM penilaian_medis_ranap AS p
    LEFT JOIN reg_periksa AS r ON p.no_rawat = r.no_rawat
    LEFT JOIN pasien AS ps ON r.no_rkm_medis = ps.no_rkm_medis
    LEFT JOIN dokter AS d ON p.kd_dokter = d.kd_dokter
    WHERE p.no_rawat = ?
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
$pdf = new TCPDF('P', 'mm', array(210,297), true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 0);
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

// Buat tabel header
$html = '
<table cellspacing="0" cellpadding="3" >
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
        <td colspan="10" style="border-top: 2px solid black;"></td>
    </tr>
    <tr style="background-color: #F0F0DC;">
        <td colspan="12" style="text-align: center; font-weight: bold; border: 1px solid black;">
            PENILAIAN MEDIS RAWAT INAP
        </td>
    </tr>
    <tr>
        <td colspan="3" style="border: 1px solid black;">
            <b>NO RM:</b> ' . htmlspecialchars($data_pasien['no_rkm_medis']) . ' <br>
            <b>NAMA:</b> ' . htmlspecialchars($data_pasien['nm_pasien']) . '
        </td>
        <td colspan="3" style="border: 1px solid black;">
            <b>Jenik Kelamin:</b> ' . htmlspecialchars($data_pasien['jk']) . ' <br>
            <b>Tanggal Lahir:</b> ' . htmlspecialchars($data_pasien['tgl_lahir']) . '
        </td>
        <td colspan="6" style="border: 1px solid black;">
            <b>Tanggal:</b> ' . htmlspecialchars($data_pasien['tanggal']) . ' <br>
            <b>Anamnesis:</b> ' . htmlspecialchars($data_pasien['anamnesis']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            <b>I. Riwayat Kesehatan</b><br>
            <span>Keluhan Utama: ' . nl2br(htmlspecialchars($data_pasien['keluhan_utama'])) . '</span>
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            Riwayat Penyakit Sekarang : ' . htmlspecialchars($data_pasien['rps']) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border: 1px solid black;">
            Riwayat Penyakit Dahulu : ' . htmlspecialchars($data_pasien['rpd']) . '
        </td>
        <td colspan="7" style="border: 1px solid black;">
            Riwayat Penyakit dalam Keluarga : ' . htmlspecialchars($data_pasien['rpk']) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border: 1px solid black;">
            Riwayat Pengobatan : ' . htmlspecialchars($data_pasien['rpo']) . '
        </td>
        <td colspan="7" style="border: 1px solid black;">
           Riwayat Alergi : ' . htmlspecialchars($data_pasien['alergi']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;">
            <b>II. Pemeriksaan Fisik</b>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="border-left: 1px solid black;vertical-align: middle; width : 33%">
            Keadaan Umum : ' . htmlspecialchars($data_pasien['keadaan']) . '
        </td>
        <td colspan="4" style="vertical-align: middle; width : 33%">
           Kesadaran : ' . htmlspecialchars($data_pasien['kesadaran']) . '
        </td>
        <td colspan="4" style="border-right: 1px solid black;vertical-align: middle; width : 33%; text-align:left">
           GCS(E,V,M) : ' . htmlspecialchars($data_pasien['gcs']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: center; vertical-align: middle; ">
            Tanda Vital: 
            TD: ' . htmlspecialchars($data_pasien["td"]) . ' mmHg | 
            N: ' . htmlspecialchars($data_pasien["nadi"]) . ' x/m | 
            R: ' . htmlspecialchars($data_pasien["rr"]) . ' x/m | 
            S: ' . htmlspecialchars($data_pasien["suhu"]) . '° | 
            SpO2: ' . htmlspecialchars($data_pasien["spo"]) . ' % | 
            BB: ' . htmlspecialchars($data_pasien["bb"]) . ' Kg| 
            TB: ' . htmlspecialchars($data_pasien["tb"]) . ' cm
        </td>
    </tr>
    <tr>
         <td colspan="2" style="border: 1px solid black;  vertical-align: middle; width: 14%;">
            Kepala<br>
            Mata<br>
            Gigi & Mulut<br>
            THT<br>
            Thoraks<br>
            Jantung
        </td>
        <td colspan="1" style="border: 1px solid black; text-align: center; vertical-align: middle; width: 11%;">
            ' . htmlspecialchars($data_pasien["kepala"]) . '<br>
            ' . htmlspecialchars($data_pasien["mata"]) . '<br>
            ' . htmlspecialchars($data_pasien["gigi"]) . '<br>
            ' . htmlspecialchars($data_pasien["tht"]) . '<br>
            ' . htmlspecialchars($data_pasien["thoraks"]) . '<br>
            ' . htmlspecialchars($data_pasien["jantung"]) . '
        </td>
        <td colspan="2" style="border: 1px solid black; vertical-align: middle; width: 14%;">
            Paru<br> 
           Abdomen<br> 
           Genital & Anus<br> 
           Extremitas<br> 
           Kulit
        </td>
        <td colspan="1" style="border: 1px solid black; text-align: center; vertical-align: middle; width: 11%;">
            ' . htmlspecialchars($data_pasien["paru"]) . '<br>
            ' . htmlspecialchars($data_pasien["abdomen"]) . '<br>
            ' . htmlspecialchars($data_pasien["genital"]) . '<br>
            ' . htmlspecialchars($data_pasien["ekstremitas"]) . '<br>
            ' . htmlspecialchars($data_pasien["kulit"]) . '
        </td>
        <td colspan="6" style="border: 1px solid black; vertical-align: middle; width: 50%;">
            ' . nl2br(htmlspecialchars($data_pasien['ket_fisik'])) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            <b>III. STATUS LOKALIS</b>
        </td>
    </tr>
    <tr>
        <td colspan="12" style="text-align: center; vertical-align: middle; height: 80px; border: 1px solid black;">
            <img src="' . $img_awal_medis . '" style="width: 900; height: 250px;">
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            Keterangan : <br>
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            <b>IV. PEMERIKSAAN PENUNJANG</b>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="border: 1px solid black;  ">
            ' . nl2br(htmlspecialchars("Laboratorium :\n" . $data_pasien['lab'])) . '
        </td>
        <td colspan="4" style="border: 1px solid black; text-align: left; ">
        Radiologi : <br>
            ' . htmlspecialchars($data_pasien["rad"]) . '
        </td>
        <td colspan="4" style="border: 1px solid black; text-align: left; ">
        Penunjang Lainnya : <br>
           ' . htmlspecialchars($data_pasien["penunjang"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            <b>V. DIAGNOSIS</b><br>
            ' . nl2br(htmlspecialchars($data_pasien['diagnosis'])) . '

        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: left; ">
            <b>VI. TATALAKSANA</b><br>
            ' . nl2br(htmlspecialchars($data_pasien['tata'])) . '

        </td>
    </tr>
    <tr>
       <td colspan="12" style="border: 1px solid black; text-align: left; text-indent: 0px;">
        <b>VII. EDUKASI</b><br>
            ' . nl2br(htmlspecialchars($data_pasien['edukasi'])) . '

        </td>
    </tr>
     <tr>
        <td colspan="6" style="border: 1px solid black;  text-align: center; vertical-align: middle;">
           Tanggal dan Jam
        </td>
        <td colspan="6" style="border: 1px solid black;  text-align: center; vertical-align: middle;">
            Nama Dokter dan Tanda Tangan
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border: 1px solid black;  text-align: center; vertical-align: middle;">
           <br><br>' . htmlspecialchars($data_pasien["tanggal"]) . ' WIB
        </td>
        <td colspan="6" style="border: 1px solid black;  text-align: center; vertical-align: middle;">
             <br><br><br>' . htmlspecialchars($data_pasien["nm_dokter"]) . '
        </td>
    </tr>
    ';
    $html .= '</table>';
    

// Tambahkan ke PDF
$pdf->SetFont('times', '', 10);
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
$pdf->Output('Laporan_Pemeriksaan_Ranap.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
