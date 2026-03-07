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
date_default_timezone_set('Asia/Jakarta');




// Ambil data dari parameter GET
$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 
$jam_rawat = isset($_GET['jam_rawat']) ? $_GET['jam_rawat'] : '';
$tgl_perawatan1 = isset($_GET['tgl_perawatan1']) ? $_GET['tgl_perawatan1'] : '';
$tgl_perawatan2 = isset($_GET['tgl_perawatan2']) ? $_GET['tgl_perawatan2'] : '';

// var_dump($jam_rawat, $tgl_perawatan);
// die();

// Koneksi ke database
$koneksi = new mysqli("localhost", "root", "s1ntluc14", "sik_tester_lintong");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}
$koneksi->set_charset("utf8mb4");
// Gunakan prepared statement untuk keamanan
$query = $koneksi->prepare("
SELECT p.*, pr.*, rp.*
FROM pcare_rujuk_khusus pr
INNER JOIN pasien p ON pr.no_rkm_medis = p.no_rkm_medis
INNER JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
WHERE pr.no_rawat=?
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





// Buat objek PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

/* === GARIS PINGGIR KERTAS === */
 $margin = 10;
 $pageWidth  = $pdf->getPageWidth();
 $pageHeight = $pdf->getPageHeight();

 $pdf->Rect(
     $margin,
     $margin,
     $pageWidth - ($margin * 2),
     $pageHeight - ($margin * 2)
 );


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
        <!-- KOLOM KIRI (LOGO) -->
        <td colspan="6" style="width:50%; text-align:left;">
            <img src="' . $logo_path . '" width="200">
        </td>

        <!-- KOLOM KANAN (INFO REGIONAL + CABANG) -->
        <td colspan="6" style="width:50%; font-weight:bold; font-size:10px;">
            <table cellpadding="2">
                <tr>
                    <td width="35%">Devisi Regional</td>
                    <td width="5%">:</td>
                    <td width="60%">Regional I-Sumatera Utara</td>
                </tr>
                <tr>
                    <td>Kantor Cabang</td>
                    <td>:</td>
                    <td>Sibolga</td>
                </tr>
            </table>
        </td>
    </tr>
    <br>
    <tr style="">
        <td colspan="12" style="text-align: center; font-weight: bold; font-size:14px; ">Surat Rujukan FKTP</td>
    </tr>

    
    
    
    <tr style="">
        <td colspan="4" style="text-align: left; border-top: 1px solid black;border-left: 1px solid black;">
            No. Rujukan
        </td>
        <td colspan="1" style="text-align: center; border-top: 1px solid black;">
            :
        </td>
        <td colspan="7" style="text-align: left; border-top: 1px solid black;border-right: 1px solid black;">
            ' . htmlspecialchars($data_pasien['noKunjungan'] ?? '') . '
        </td>
    </tr>

    <tr style="">
        <td colspan="4" style="text-align: left; border-left: 1px solid black;">
            Puskesmas / Dokter Keluarga
        </td>
        <td colspan="1" style="text-align: center;">
            :
        </td>
        <td colspan="7" style="text-align: left;border-right: 1px solid black;">
            Klinik Santa Lucia Lintong (003B11)
        </td>
    </tr>

    <tr style="">
        <td colspan="4" style="text-align: left; border-bottom: 1px solid black;border-left: 1px solid black;">
            Kabupaten /  Kota
        </td>
        <td colspan="1" style="text-align: center; border-bottom: 1px solid black;">
            :
        </td>
        <td colspan="7" style="text-align: left; border-bottom: 1px solid black;border-right: 1px solid black;">
            Humbang Hasundutan
        </td>
    </tr>
    <br>


    <tr style="">
        <td colspan="3" style="text-align: left;">
            Kepada Yth. TS Dokter
        </td>
        <td colspan="1" style="text-align: center;">
            :
        </td>
        <td colspan="8" style="text-align: left;">
            ' . htmlspecialchars($data_pasien['nmKhusus'] ?? '') . '
        </td>
    </tr>
    <tr style="">
        <td colspan="3" style="text-align: left;">
            Di
        </td>
        <td colspan="1" style="text-align: center;">
            :
        </td>
        <td colspan="8" style="text-align: left;">
            ' . htmlspecialchars($data_pasien['NmPPKRujukanKhusus'] ?? '') . '
        </td>
    </tr>
    <br>
     <tr style="">
        <td colspan="12" style="text-align: left;">
            Mohon pemeriksaan dan penanganan lebih lanjut pasien :
        </td>
    </tr>
    <br>

    <tr style="">
        <td colspan="2" style="text-align: left; width:15%">
            Nama
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="3" style="text-align: left;width:35%">
            ' . htmlspecialchars($data_pasien['nm_pasien'] ?? '') . '
        </td>


        <td colspan="2" style="text-align: right; width:7%;font-size:9px">
            Umur
        </td>
        <td colspan="1" style="text-align: left; width:3%;">
            :
        </td>
        <td colspan="1" style="text-align: left; width :3%;">
            ' . htmlspecialchars($data_pasien['umurdaftar'] ?? '') . ' ' . htmlspecialchars($data_pasien['sttsumur'] ?? '') . '
        </td>
        <td colspan="2" style="text-align: left; width :3%;">
            ' . tanggal_indo($data_pasien['tgl_lahir'] ?? '') . '
        </td>
    </tr>

    <tr style="">
        <td colspan="2" style="text-align: left; width:15%">
            No. Kartu BPJS
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="2" style="text-align: left;width:35%">
            ' . htmlspecialchars($data_pasien['noKartu'] ?? '') . '
        </td>


        <td colspan="2" style="text-align: right; width:7%; font-size:9px">
            Status
        </td>
        <td colspan="1" style="text-align: left; width:4%;">
            :
        </td>
        <td colspan="1" style="text-align: center; width :1%; font-size:9px;border:1px solid black;">
               1 
        </td>
        <td colspan="2" style="text-align: left; width :3%;font-size:8px">
            Utama/Tanggunan
        </td>
        <td colspan="1" style="text-align: center; width :1%; font-size:9px;border:1px solid black;">
               ' . htmlspecialchars($data_pasien['jk'] ?? '') . ' 
        </td>
        <td colspan="1" style="text-align: center; width :1%; font-size:9px;">
               (L/P)
        </td>
    </tr>

     <tr style="">
        <td colspan="2" style="text-align: left; width:15%">
            Diagnosa
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="3" style="text-align: left;width:40%">
            ' . htmlspecialchars($data_pasien['nmDiag1'] ?? '') . '
        </td>


    </tr>
    <tr style="">
        <td colspan="2" style="text-align: left; width:15%">
            Alasan TACC
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="3" style="text-align: left;width:40%">
            ' . htmlspecialchars($data_pasien['alasanTACC'] ?? '') . '
        </td>


    </tr>
    <tr style="background-color="red;>
        <td colspan="9" style="text-align: center; ">
           
        </td>
        <td colspan="3" style="text-align: center; font-size:9px">
            Salam Sejawat, ' . date('d/m/Y H:i:s') . '
        </td>

    </tr>

    <tr style="background-color="red;>
        <td colspan="9" style="text-align: left;font-size:9px; ">
           Atas Bantuannya, diucapkan terimakasih
        </td>
        <td colspan="3" style="text-align: center; font-size:9px">
        
        </td>

    </tr>
     <tr style="background-color="red;>
        <td colspan="9" style="text-align: left;font-size:9px; ">
          Tanggal Rencana Berkunjung
        </td>
        <td colspan="3" style="text-align: center; font-size:9px">
        
        </td>

    </tr>
     <tr style="">
        <td colspan="9" style="text-align: left; font-size:9px;">
            Surat Rujukan Berlaku 1 (satu) kali kunjungan, Berlaku 90 Hari
        </td>
        <td colspan="3" style="text-align: center; font-size:10px;">
            dr. Megawati Lumbantoruan
        </td>
    </tr>

    <tr>
        <td colspan="12" style="border-bottom:1px solid #000;"></td>
    </tr>
    <tr style="">
        <td colspan="12" style="text-align: center; font-weight: bold; font-size:13px; ">SURAT RUJUKAN BALIK</td>
    </tr>

    <tr style="">
        <td colspan="9" style="text-align: left; font-size:9px;">
            Teman Sejawat Yth.
        </td>
    </tr>
    <tr style="">
        <td colspan="9" style="text-align: left; font-size:9px;">
            Mohon kontrol selanjutnya penderita :
        </td>
    </tr>


    <tr style="">
        <td colspan="1" style="text-align: left;"></td>
        <td colspan="2" style="text-align: left; width:15%">
            Nama
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="3" style="text-align: left;width:40%">
            ' . htmlspecialchars($data_pasien['nm_pasien'] ?? '') . '
        </td>
    </tr>
    <tr style="">
        <td colspan="1" style="text-align: left;"></td>
        <td colspan="2" style="text-align: left; width:15%">
            Diagnosa
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="3" style="text-align: left;width:40%">
            
        </td>
    </tr>
    <tr style="">
        <td colspan="1" style="text-align: left;"></td>
        <td colspan="2" style="text-align: left; width:15%">
            Therapi
        </td>
        <td colspan="1" style="text-align: center; width:3%; ">
            :
        </td>
        <td colspan="3" style="text-align: left;width:40%">
            
        </td>
    </tr>

    <tr>
        <td colspan="9" style="text-align: left; font-size:9px;">
            <span style="display:inline-block; width:10px; height:10px; border:1px solid #000; margin-right:5px;"></span>
            Tindak lanjut yang dianjurkan :
        </td>
    </tr>

     <tr>
        <td colspan="2" style="text-align: left; font-size:20px; width:15%;">
            <label>
                <input type="checkbox" name="keadaan[]" value="Dipulangkan"> 
            </label>
        </td>
        <td colspan="5" style="text-align: left; font-size:9px;">
            Pengobatan dengan Obat - Obatan<br>..........................................................
        </td>
        <td colspan="2" style="text-align: left; font-size:20px; width:15%;">
            <label>
                <input type="checkbox" name="keadaan[]" value="Dipulangkan"> 
            </label>
        </td>
        <td colspan="3" style="text-align: left; font-size:9px;">
            Perlu Rawat Inap
        </td>
    </tr>
     <tr>
        <td colspan="2" style="text-align: left; font-size:20px;">
            <label>
                <input type="checkbox" name="keadaan[]" value="Dipulangkan"> 
            </label>
        </td>
        <td colspan="5" style="text-align: left; font-size:9px;">
            Kontrol Kembeli ke RS tanggal : ........................
        </td>
        <td colspan="2" style="text-align: left; font-size:20px; width:15%;">
            <label>
                <input type="checkbox" name="keadaan[]" value="Dipulangkan"> 
            </label>
        </td>
        <td colspan="3" style="text-align: left; font-size:9px;">
            Konsultasi
        </td>
    </tr>
     <tr>
        <td colspan="2" style="text-align: left; font-size:20px;">
            <label>
                <input type="checkbox" name="keadaan[]" value="Dipulangkan"> 
            </label>
        </td>
        <td colspan="5" style="text-align: left; font-size:9px;">
           Lain-lain : ..........................................................
        </td>
       
    </tr>

    <tr>
        <td colspan="7" style="text-align: left; font-size:20px;">
            
        </td>
        <td colspan="5" style="text-align: left; font-size:9px; text-align:center;">
           Dokter RS
        </td>
       
    </tr>
    <br><br><br><br><br>
    <tr>
        <td colspan="7" style="text-align: left; font-size:20px;">
            
        </td>

        <td colspan="5" style="text-align: left; font-size:9px;text-align:center;">
           (.................................................)
        </td>
       
    </tr>


     



    
    
    ';






$html .= '</table>';

// Tambahkan ke PDF
$pdf->SetFont('times', '', 10);
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Bukti_Pelayanan_BPJS_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data_pasien['nm_pasien'] ?? '') . '.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
