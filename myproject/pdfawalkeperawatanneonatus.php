<?php
require_once('tcpdf/tcpdf.php');
$function_path = __DIR__ . '/../function/function.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}
$koneksi->set_charset("utf8mb4");

$no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; 


// var_dump($no_rawat);
// die();
// Koneksi ke database
// $koneksi = new mysqli("192.168.3.250", "rssl", "s1ntluc14", "sik");
// if ($koneksi->connect_error) {
//     die("Koneksi gagal: " . $koneksi->connect_error);
// }

// Gunakan prepared statement untuk keamanan
$query = $conn->prepare("
      SELECT p.*, ps.*, r.*, pg.*, DATE(p.tanggal) AS tanggal
FROM penilaian_awal_keperawatan_ranap_neonatus AS p
LEFT JOIN reg_periksa AS r ON p.no_rawat = r.no_rawat
LEFT JOIN pasien AS ps ON r.no_rkm_medis = ps.no_rkm_medis
LEFT JOIN pegawai AS pg ON p.kd_dokter = pg.nik
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
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
// $pdf = new TCPDF('P', 'mm', array(210,500), true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(5, 10, 5);
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
        <td colspan="12" style="border-top: 2px solid black;"></td>
    </tr>
    <tr style="background-color: #F0F0DC;">
        <td colspan="12" style="text-align: center; font-weight: bold; border: 1px solid black;">
            PENILAIAN AWAL KEPERAWATAN RAWAT INAP NEONATUS
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border: 1px solid black;vertical-align: middle; width : 50%">
            <b>NO RM:</b> ' . htmlspecialchars($data_pasien['no_rkm_medis']) . ' <br>
            <b>NAMA:</b> ' . htmlspecialchars($data_pasien['nm_pasien']) . ' <br>
            <b>Tanggal Lahir:</b> ' . htmlspecialchars($data_pasien['tgl_lahir']) . ' <br>
            <b>Jenis Kelamin:</b> ' . htmlspecialchars($data_pasien['jk']) . ' <br>
            <b>Asal Pasien:</b> ' . htmlspecialchars($data_pasien['asal_pasien']) . '
        </td>
         <td colspan="5" style="border: 1px solid black;vertical-align: middle; width : 50%">
            <b>Tanggal Kunjungan:</b> ' . htmlspecialchars($data_pasien['tanggal']) . ' <br>
            <b>Diperoleh Dari:</b> ' . htmlspecialchars($data_pasien['diperoleh_dari']) . ' <br>
            <b>Hubungan Dengan Pasien:</b> ' . htmlspecialchars($data_pasien['hubungan_dengan_pasien']) . ' <br>
            <b>Cara Masuk:</b> ' . htmlspecialchars($data_pasien['cara_masuk']) . ' <br>
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>I. RIWAYAT KESEHATAN</b><br>
            Keluhan Utama: ' . htmlspecialchars($data_pasien['keluhan_utama']) . ' <br>
            <b>Riwayat Prenatal:</b>
        </td>
    </tr>
    <tr style = "text-indent: 20px;">
        <td colspan="12" style="border-left: 1px solid black; border-right: 1px solid black; ">
            Riwayat Obstetri: &nbsp;&nbsp;&nbsp;
            G ' . htmlspecialchars($data_pasien['prenatal_g']) . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            P ' . htmlspecialchars($data_pasien['prenatal_p']) . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            A ' . htmlspecialchars($data_pasien['prenatal_a']) . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            UK ' . htmlspecialchars($data_pasien['prenatal_uk']) . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Riwayat Penyakit Ibu : ' . htmlspecialchars($data_pasien['prenatal_riwayat_penyakit_ibu']) . '
        </td>
    </tr>
    <tr style = "text-indent: 20px;">
        <td colspan="12" style="border-left: 1px solid black; border-right: 1px solid black; ">
            Riwayat Pengobatan Ibu Selama Hamil: &nbsp;&nbsp;&nbsp;' . htmlspecialchars($data_pasien['prenatal_riwayat_pengobatan_ibu_selama_hamil']) . '
        </td>
    </tr>
    <tr>
        <td colspan="7" style="border-left: 1px solid black; text-indent: 20px;">
            Pernah Dirawat: &nbsp;&nbsp;&nbsp;' . htmlspecialchars($data_pasien['prenatal_pernah_dirawat']) . '
        </td>
        <td colspan="5" style=" border-right: 1px solid black; ">
            Status Gizi: &nbsp;&nbsp;&nbsp;' . htmlspecialchars($data_pasien['prenatal_status_gizi_ibu']) . '
        </td>
    </tr>
     <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Riwayat Intranatal:</b>
        </td>
    </tr>
    <tr style = "text-indent: 20px;">
        <td colspan="12" style="border-left: 1px solid black; border-right: 1px solid black; ">
            Riwayat Obstetri: &nbsp;&nbsp;&nbsp;
            G ' . htmlspecialchars($data_pasien['intranatal_g']) . ' &nbsp;&nbsp;&nbsp;
            P ' . htmlspecialchars($data_pasien['intranatal_p']) . ' &nbsp;&nbsp;&nbsp;
            A ' . htmlspecialchars($data_pasien['intranatal_a']) . ' &nbsp;&nbsp;&nbsp;
            Tanggal Lahir : ' . htmlspecialchars($data_pasien['tanggal']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kondisi Saat Lahir : ' . htmlspecialchars($data_pasien['intranatal_kondisi_lahir']) . '
        </td>
    </tr>
    <tr style = "text-indent: 20px;">
        <td colspan="12" style="border-left: 1px solid black; border-right: 1px solid black; ">
            Cara Persalinan : 
            ' . htmlspecialchars($data_pasien['intranatal_cara_persalinan']) . ' &nbsp;&nbsp;&nbsp;
            APGAR ' . htmlspecialchars($data_pasien['intranatal_apgar']) . ' &nbsp;&nbsp;&nbsp;
            Letak ' . htmlspecialchars($data_pasien['intranatal_letak']) . ' &nbsp;&nbsp;&nbsp;
            Tali Pusat : ' . htmlspecialchars($data_pasien['intranatal_tali_pusat']) . '
        </td>
    </tr>
    <tr style = "text-indent: 20px;">
        <td colspan="12" style="border-left: 1px solid black; border-right: 1px solid black; ">
            Ketuban : 
            ' . htmlspecialchars($data_pasien['intranatal_ketuban']) . ' &nbsp;&nbsp;&nbsp;
            Antopometri BBL : BB ' . htmlspecialchars($data_pasien['intranatal_bb']) . ' gr, &nbsp;&nbsp;&nbsp;
            PB ' . htmlspecialchars($data_pasien['intranatal_pb']) . 'cm, &nbsp;&nbsp;&nbsp;
            LK : ' . htmlspecialchars($data_pasien['intranatal_lk']) . 'cm,&nbsp;&nbsp;&nbsp;
            LD : ' . htmlspecialchars($data_pasien['intranatal_ld']) . 'cm,&nbsp;&nbsp;&nbsp;
            LP : ' . htmlspecialchars($data_pasien['intranatal_lp']) . 'cm
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Faktor Risiko Infeksi:</b>
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; ">
            Mayor : 
            ' . htmlspecialchars($data_pasien['risiko_infeksi_mayor']) . ' &nbsp;&nbsp;
            '. htmlspecialchars($data_pasien['risiko_infeksi_mayor_keterangan']) . '
        </td>
        <td colspan="5" style=" border-right: 1px solid black;">
            Minor ' . htmlspecialchars($data_pasien['risiko_infeksi_minor']) . ' &nbsp;&nbsp;&nbsp;
            '. htmlspecialchars($data_pasien['risiko_infeksi_minor_keterangan']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Kebutuhan Biologis:</b>
        </td>
    </tr>
    <tr>
        <td colspan="7" style="border-left: 1px solid black; text-indent: 20px; ">
            Nutrisi : 
            ' . htmlspecialchars($data_pasien['kebutuhan_biologis_nutrisi']) . ' &nbsp;&nbsp;
            '. htmlspecialchars($data_pasien['kebutuhan_biologis_nutrisi_keterangan']) . ',
        </td>
        <td colspan="3" style=" border-right: 1px solid black;">
            Frekuensi :  ' . htmlspecialchars($data_pasien['kebutuhan_biologis_nutrisi_frekuensi']) . 'cc /
            '. htmlspecialchars($data_pasien['kebutuhan_biologis_nutrisi_kali']) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; ">
                Eliminasi : <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;BAK : 
            ' . htmlspecialchars($data_pasien['kebutuhan_biologis_bak']) . ' &nbsp;&nbsp;
            '. htmlspecialchars($data_pasien['kebutuhan_biologis_bak_keterangan']) . '|
        </td>
        <td colspan="5" style=" border-right: 1px solid black;">
            BAB :  ' . htmlspecialchars($data_pasien['kebutuhan_biologis_bab']) . ' | 
            '. htmlspecialchars($data_pasien['kebutuhan_biologis_bab_keterangan']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Alergi/Reaksi (Pada Orang Tua) : </b>
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; ">
            Alergi Obat : 
            ' . htmlspecialchars($data_pasien['alergi_obat']) . ' | 
            '. htmlspecialchars($data_pasien['alergi_obat_keterangan']) . '
        </td>
        <td colspan="5" style=" border-right: 1px solid black;">
            Jika Ada, Reaksi :<br> ' . htmlspecialchars($data_pasien['alergi_obat_reaksi']) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; ">
            Alergi Makanan : 
            ' . htmlspecialchars($data_pasien['alergi_makanan']) . ' | 
            '. htmlspecialchars($data_pasien['alergi_makanan_keterangan']) . '
        </td>
        <td colspan="5" style=" border-right: 1px solid black;">
            Jika Ada, Reaksi :<br> ' . htmlspecialchars($data_pasien['alergi_makanan_reaksi']) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; ">
            Alergi Lainnya : 
            ' . htmlspecialchars($data_pasien['alergi_lainnya']) . ' | 
            '. htmlspecialchars($data_pasien['alergi_lainnya_keterangan']) . '
        </td>
        <td colspan="5" style=" border-right: 1px solid black;">
            Jika Ada, Reaksi :<br> ' . htmlspecialchars($data_pasien['alergi_lainnya_reaksi']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Riwayat Penyakit Keluarga : </b> ' . htmlspecialchars($data_pasien['riwayat_penyakit_keluarga']) . ' | ' . htmlspecialchars($data_pasien['riwayat_penyakit_keluarga_keterangan']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Riwayat Imunisasi : </b> ' . htmlspecialchars($data_pasien['riwayat_imunisasi']) . ' | ' . htmlspecialchars($data_pasien['riwayat_imunisasi_keterangan']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Riwayat Transfusi Darah : </b> ' . htmlspecialchars($data_pasien['riwayat_tranfusi_darah']) . ' | ' . htmlspecialchars($data_pasien['riwayat_tranfusi_darah_keterangan']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            <b>Riwayat Transfusi Darah : </b> ' . htmlspecialchars($data_pasien['riwayat_tranfusi_darah']) . ' | ' . htmlspecialchars($data_pasien['riwayat_tranfusi_darah_keterangan']) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
           <b> Kebiasan Ibu : </b>
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; ">
            Obat-obatan Diminium : 
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_obat_diminum']) . ' &nbsp;&nbsp;
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_obat_diminum_keterangan']) . ',
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
            Merokok : 
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_merokok']) . ', &nbsp;Jika Ya :
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_merokok_keterangan']) . ' batang / hari

        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px; border-bottom: 1px solid black; ">
            Obat Tidur/Narkoba
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_narkoba']) . ' &nbsp;&nbsp;
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_narkoba_keterangan']) . ',
        </td>
        <td colspan="5" style="border-right: 1px solid black; border-bottom: 1px solid black; ">
            Alkohol : 
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_alkohol']) . ', &nbsp;Jika Ya :
            ' . htmlspecialchars($data_pasien['kebiasan_ibu_alkohol_keterangan']) . ' gelas / hari

        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;border-right: 1px solid black;">
            <b>II. PEMERIKSAAN FISIK</b>
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border-left: 1px solid black; ">
            Kesadaran : 
            ' . htmlspecialchars($data_pasien['kesadaran']) . ' &nbsp; &nbsp;Keadaan Umum
            ' . htmlspecialchars($data_pasien['keadaan_umum']) . '
        </td>
        <td colspan="6" style="border-right: 1px solid black; ">
            GCS(E,V,M) : 
            ' . htmlspecialchars($data_pasien['gcs']) . ' &nbsp; &nbsp; TD :
            ' . htmlspecialchars($data_pasien['td']) . ' mmHg :&nbsp; &nbsp; Suhu : 
            ' . htmlspecialchars($data_pasien['suhu']) . '°C
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: center; vertical-align: middle; ">
            HR: ' . htmlspecialchars($data_pasien["hr"]) . ' x/menit  &nbsp; &nbsp;|&nbsp; &nbsp;
            RR: ' . htmlspecialchars($data_pasien["rr"]) . ' x/menit  &nbsp; &nbsp;|&nbsp; &nbsp;
            SpO2: ' . htmlspecialchars($data_pasien["spo2"]) . ' %  &nbsp; &nbsp;|&nbsp; &nbsp;
            Down Score: ' . htmlspecialchars($data_pasien["down_score"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
            BB: ' . htmlspecialchars($data_pasien["bb"]) . ' Kg  &nbsp; &nbsp;|&nbsp; &nbsp;
            TB: ' . htmlspecialchars($data_pasien["tb"]) . ' cm  &nbsp; &nbsp;|&nbsp; &nbsp;
            LK: ' . htmlspecialchars($data_pasien["lk"]) . ' cm

            
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black; text-align: center; vertical-align: middle; ">
            LD : ' . htmlspecialchars($data_pasien["ld"]) . ' cm  &nbsp; &nbsp;|&nbsp; &nbsp;
            LP : ' . htmlspecialchars($data_pasien["lp"]) . ' cm  &nbsp; &nbsp;|&nbsp; &nbsp;
            Golongan Darah Bayi : ' . htmlspecialchars($data_pasien["gd_bayi"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
            Golongan Darah Ibu: ' . htmlspecialchars($data_pasien["gd_ibu"]) . ' &nbsp; &nbsp;|&nbsp; &nbsp;
            Golongan Darah Ayah: ' . htmlspecialchars($data_pasien["gd_ayah"]) . '   
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Sistem Sususan Saraf Pusat :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black; text-indent: 20px;border-right: 1px solid black;">
           Gerak Bayi : ' . htmlspecialchars($data_pasien["saraf_pusat_gerak_bayi"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Kepala : ' . htmlspecialchars($data_pasien["saraf_pusat_kepala"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["saraf_pusat_kepala_keterangan"]) . '|&nbsp; &nbsp;
        Ubun-ubun : ' . htmlspecialchars($data_pasien["saraf_pusat_ubunubun"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["saraf_pusat_ubunubun_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Wajah : ' . htmlspecialchars($data_pasien["saraf_pusat_wajah"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["saraf_pusat_wajah_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black;">
           Kejang : ' . htmlspecialchars($data_pasien["saraf_pusat_kejang"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["saraf_pusat_kejang_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Refleks : ' . htmlspecialchars($data_pasien["saraf_pusat_refleks"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["saraf_pusat_refleks_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black;">
           Tangis Bayi : ' . htmlspecialchars($data_pasien["saraf_pusat_tangisbayi"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["saraf_pusat_tangisbayi_keterangan"]) . '
        </td>
    </tr>
     <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Kardiovaskular :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-right: 1px solid black;border-left: 1px solid black; text-indent: 20px;">
           Denyut Nadi : ' . htmlspecialchars($data_pasien["kardiovaskular_denyutnadi"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Sirkulasi : ' . htmlspecialchars($data_pasien["kardiovaskular_sirkulasi"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["kardiovaskular_sirkulasi_keterangan"]) . '
           Pulsasi : ' . htmlspecialchars($data_pasien["kardiovaskular_pulsasi"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["kardiovaskular_pulsasi_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Respirasi :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black; text-indent: 20px;">
           Pola Nafas : ' . htmlspecialchars($data_pasien["respirasi_polanafas"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Jenis Pernapasan : ' . htmlspecialchars($data_pasien["respirasi_jenispernapasan"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["respirasi_jenispernapasan_keterangan"]) . '
           Restraksi : ' . htmlspecialchars($data_pasien["respirasi_retraksi"]) . '
        </td>
    </tr>

    <tr>
        <td colspan="12" style="border-right: 1px solid black; border-left: 1px solid black; text-indent: 20px; ; vertical-align: middle;">
           Air Entry : ' . htmlspecialchars($data_pasien["respirasi_airentry"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Merintih : ' . htmlspecialchars($data_pasien["respirasi_merintih"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Suara Nafas : ' . htmlspecialchars($data_pasien["respirasi_suara_napas"]) . '
        </td>
    </tr>


     <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Gastrointestinal :
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Mulut : ' . htmlspecialchars($data_pasien["gastrointestinal_mulut"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_mulut_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
           Mulut : ' . htmlspecialchars($data_pasien["gastrointestinal_lidah"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_lidah_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Tenggorokan : ' . htmlspecialchars($data_pasien["gastrointestinal_tenggorakan"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_tenggorakan_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
           Abdomen : ' . htmlspecialchars($data_pasien["gastrointestinal_abdomen"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_abdomen_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           BAB : ' . htmlspecialchars($data_pasien["gastrointestinal_bab"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_bab_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
           Warna BAB : ' . htmlspecialchars($data_pasien["gastrointestinal_warnabab"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_warnabab_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           BAK : ' . htmlspecialchars($data_pasien["gastrointestinal_bak"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_bak_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
           Warna BAK : ' . htmlspecialchars($data_pasien["gastrointestinal_bakwarna"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["gastrointestinal_bakwarna_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Neurologi :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black; text-indent: 20px;border-right: 1px solid black;">
           Posisi Mata : ' . htmlspecialchars($data_pasien["integument_warna_kulit"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Kelopak Mata : ' . htmlspecialchars($data_pasien["neurologi_kelopak_mata"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["neurologi_kelopak_mata_keterangan"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Besar Pupil : ' . htmlspecialchars($data_pasien["neurologi_besar_pupil"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Konjungtiva : ' . htmlspecialchars($data_pasien["neurologi_konjugtiva"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["neurologi_konjugtiva_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
           Sklera : ' . htmlspecialchars($data_pasien["neurologi_sklera"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["neurologi_sklera_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Pendengaran : ' . htmlspecialchars($data_pasien["neurologi_pendengaran"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["neurologi_pendengaran_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; ">
           Penciuman : ' . htmlspecialchars($data_pasien["neurologi_penciuman"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["neurologi_penciuman_keterangan"]) . '
        </td>
    </tr>

    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Integument :
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black; text-indent: 20px;border-right: 1px solid black;">
           Warna Kulit : ' . htmlspecialchars($data_pasien["integument_warna_kulit"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["integument_warna_kulit_keterangan"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Vernic Kaseosa : ' . htmlspecialchars($data_pasien["integument_vernic_kaseosa"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["integument_vernic_kaseosa_keterangan"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Turgor : ' . htmlspecialchars($data_pasien["integument_turgor"]) . '
        </td>
    </tr>

    <tr>
        <td colspan="12" style="border-left: 1px solid black; text-indent: 20px;border-right: 1px solid black;">
           Lanugo : ' . htmlspecialchars($data_pasien["integument_lanugo"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Kulit : ' . htmlspecialchars($data_pasien["integument_kulit"]) . '&nbsp; &nbsp;|&nbsp; &nbsp;
           Kriteria Risiko Dekubitas : ' . htmlspecialchars($data_pasien["integument_risiko_dekubitas"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black; border-right: 1px solid black;">
           Reproduksi : ' . htmlspecialchars($data_pasien["reproduksi"]) . ',&nbsp;&nbsp;' . htmlspecialchars($data_pasien["reproduksi_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;  ">
            Sistem Muskuloskeletal :
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; text-indent: 20px;">
           Rekoil Telinga : ' . htmlspecialchars($data_pasien["muskuloskeletal_rekoil_telinga"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["muskuloskeletal_rekoil_telinga_keterangan"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black;">
           Lengan : ' . htmlspecialchars($data_pasien["muskuloskeletal_lengan"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["muskuloskeletal_lengan_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border-left: 1px solid black; text-indent: 20px;">
           Tungkai : ' . htmlspecialchars($data_pasien["muskuloskeletal_tungkai"]) . ',&nbsp; &nbsp;' . htmlspecialchars($data_pasien["muskuloskeletal_tungkai_keterangan"]) . '
        </td>
        <td colspan="4" style="border-right: 1px solid black;">
           Garis Telapak Kaki : ' . htmlspecialchars($data_pasien["muskuloskeletal_telapak_kaki"]) . '
        </td>
    </tr>

     <tr>
        <td colspan="12" style="border: 1px solid black;border-right: 1px solid black;">
            <b>III. RIWAYAT PSIKOLOGIS - SOSIAL - EKONOMI - BUDAYA - SPIRITUAL (ORANGTUA)</b>
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-right: 1px solid black; border-left: 1px solid black;">
           Kondisi Psikologis: ' . htmlspecialchars($data_pasien["kondisi_psikologis"]) . '&nbsp;|&nbsp;
           Gangguan Jiwa Di Masa Lalu: ' . htmlspecialchars($data_pasien["gangguan_jiwa"]) . '&nbsp;|&nbsp;
           Menerima Kondisi Bayi Saat Ini: ' . htmlspecialchars($data_pasien["menerima_kondisi_bayi"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="3" style=" border-left: 1px solid black;">
           Status Menikah: ' . htmlspecialchars($data_pasien["status_menikah"]) . '
        </td>
        <td colspan="5">
           Masalah Pernikahan: ' . htmlspecialchars($data_pasien["masalah_pernikahan"]) . ',&nbsp;' . htmlspecialchars($data_pasien["masalah_pernikahan_keterangan"]) . '
        </td>
        <td colspan="4" style="border-right: 1px solid black;">
           Pekerjaan: ' . htmlspecialchars($data_pasien["pekerjaan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style=" border-left: 1px solid black;">
           Agama: ' . htmlspecialchars($data_pasien["agama"]) . '
        </td>
       
        <td colspan="9" style="border-right: 1px solid black;">
           Nilai-nilai Kepercayaan/Budaya Yang Perlu Diperhatikan: ' . htmlspecialchars($data_pasien["nilai_kepercayaan"]) . ',&nbsp;' . htmlspecialchars($data_pasien["nilai_kepercayaan_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style=" border-left: 1px solid black;border-right: 1px solid black;">
           Suku: ' . htmlspecialchars($data_pasien["suku"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Pendidikan: ' . htmlspecialchars($data_pasien["pendidikan"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Pembayaran: ' . htmlspecialchars($data_pasien["pembayaran"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Tinggal Bersama: ' . htmlspecialchars($data_pasien["tinggal_bersama"]) . ',&nbsp;&nbsp;
           ' . htmlspecialchars($data_pasien["tinggal_bersama_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black; width : 50%">
           Hubungan Pasien Dengan Anggota Keluarga: ' . htmlspecialchars($data_pasien["hubungan_keluarga"]) . '
        </td>
        <td colspan="5" style=" border-right: 1px solid black;vertical-align: middle; width : 50%;">
           Respon Emosi: ' . htmlspecialchars($data_pasien["respon_emosi"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;border-right: 1px solid black;">
            <b>IV .KUBUTUHAN KOMUNIKASI DAN BELAJAR/EDUKASI (ORANGTUA)</b>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black; width : 35%">
           Bahasa Sehari-hari : ' . htmlspecialchars($data_pasien["bahasa_sehari_hari"]) . '
        </td>
        <td colspan="3" style=" vertical-align: middle; width : 30%;">
           Kemampuan Baca & Tulis: ' . htmlspecialchars($data_pasien["kemampuan_bacatulis"]) . '
        </td>
        <td colspan="5" style="border-right: 1px solid black; width : 40%">
           Butuh Penerjemah : ' . htmlspecialchars($data_pasien["butuh_penterjemah"]) . ', &nbsp; Jika Ya : '. htmlspecialchars($data_pasien["butuh_penterjemah_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-right: 1px solid black; border-left: 1px solid black;width : 35%">
           Terdapat Hambatan Dalam Pembelajaran: ' . htmlspecialchars($data_pasien["terdapat_hambatan_belajar"]) . ',&nbsp; Jika Ya: ' . htmlspecialchars($data_pasien["hambatan_belajar"]) . '&nbsp;&nbsp;' . htmlspecialchars($data_pasien["hambatan_belajar_keterangan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black; width : 35%">
           Hambatan Cara Bicara: ' . htmlspecialchars($data_pasien["hambatan_cara_bicara"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Hambatan Bahasa Isyarat :' . htmlspecialchars($data_pasien["hambatan_bahasa_isyarat"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Cara Belajar Yang Disukai :' . htmlspecialchars($data_pasien["cara_belajar_disukai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="6" style="border-left: 1px solid black; width : 35%">
           Kesediaan Menerima Informasi: ' . htmlspecialchars($data_pasien["kesediaan_menerima_informasi"]) . ',&nbsp;&nbsp;' . htmlspecialchars($data_pasien["kesediaan_menerima_informasi_keterangan"]) . '
        </td>
        <td colspan="4" style="border-right: 1px solid black; width : 35%">
           Pemahaman Tentang Nutrisi/Diet: ' . htmlspecialchars($data_pasien["pemahaman_nutrisi"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black; ">
           Pemahanan Tentang Penyakit: ' . htmlspecialchars($data_pasien["pemahaman_penyakit"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Pemahaman Tentang Pengobatan' . htmlspecialchars($data_pasien["pemahaman_pengobatan"]) . '&nbsp;&nbsp;|&nbsp;&nbsp;
           Pemahaman Tentang Perawatan' . htmlspecialchars($data_pasien["pemahaman_perawatan"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;border-right: 1px solid black;">
            <b>V. SKRINING GIZI</b>
        </td>
    </tr>
    <tr>
        <td colspan="7" style="border-left: 1px solid black;">
            1. Masalah Minum (ASI/PASI)
        </td>
        <td colspan="2" >
            ' . htmlspecialchars($data_pasien["masalah_gizi1"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;border-right: 1px solid black;">
            ' . htmlspecialchars($data_pasien["nilai_gizi1"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="7" style="border-left: 1px solid black;">
            2. Penurunan Berat Badan > 10% Dari BBL (Berat Badan Lahir)
        </td>
        <td colspan="2" >
            ' . htmlspecialchars($data_pasien["masalah_gizi2"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;border-right: 1px solid black;">
            ' . htmlspecialchars($data_pasien["nilai_gizi2"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="7" style="border-left: 1px solid black;">
            3. Penyakit / Kelainan Yang Menyertai (Sepsis, Jantung, BBLR, Hipoglikemi, Diare, Lain-Lain)
        </td>
        <td colspan="2" >
            ' . htmlspecialchars($data_pasien["masalah_gizi3"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;border-right: 1px solid black;">
            ' . htmlspecialchars($data_pasien["nilai_gizi3"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="8" style="border-left: 1px solid black;">
            Keterangan : ' . htmlspecialchars($data_pasien["keterangan_gizi"]) . '
        </td>
        <td colspan="1"style="text-align: right;" >
            Total : 
        </td>
        <td colspan="1" style="border-right: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["totalgizi"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;border-right: 1px solid black;">
            <b>VI. PENILAIAN RISIKO JATUH (SKALA HUMPTY DUMPTY)</b>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            1. Umur
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala1"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black; text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai1"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            2. Jenis Kelamin
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala2"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai2"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            3. Diagnosa
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala3"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai3"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            4. Gangguan Kognitif
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala4"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai4"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            5. Faktor Lingkungan
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala5"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai5"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            6. Efek Obat Penenang/Operasi/Anastesi
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala6"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai6"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            7. Penggunaan Obat
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_skala7"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_nilai7"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            Tngkat Risiko : 
        </td>
        <td colspan="4"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_hasil"]) . '
        </td>
        <td colspan="3" style=" text-align: right; vertical-align: middle;" >
            Total : 
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["penilaian_humptydumpty_totalnilai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;">
            <b>VII. PENILAIAN TINGKAT NYERI (SKALA NIPS)</b>
        </td>
    </tr>
    
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            1. Ekspresi Wajah
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["skala_nips1"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["skala_nips1_nilai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            2. Tangisan
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["skala_nips2"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["skala_nips2_nilai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            3. Pola Nafas
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["skala_nips3"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["skala_nips3_nilai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            4. Ekspresi Wajah
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["skala_nips4"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["skala_nips4_nilai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            5. Tingkat Kesadaran
        </td>
        <td colspan="7"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["skala_nips5"]) . '
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["skala_nips5_nilai"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-left: 1px solid black;">
            Keterangan : 
        </td>
        <td colspan="4"style="border: 1px solid black;" >
            ' . htmlspecialchars($data_pasien["skala_nips_keterangan"]) . '
        </td>
        <td colspan="3" style=" text-align: right; vertical-align: middle;" >
            Total : 
        </td>
        <td colspan="1" style="border-right: 1px solid black;text-align: center;" >
            ' . htmlspecialchars($data_pasien["skala_nips_total"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border: 1px solid black;">
            <b>VIII. PERENCANAAN PULANG (DISCHARGE PLANNING) </b>
        </td>
    </tr>
    <tr>
        <td colspan="5" style="border-left: 1px solid black;">
            Ibu Bayi & Keluarga Diberikan Informasi Perencanaan Pulang ? &nbsp; ' . htmlspecialchars($data_pasien["informasi_perencanaan_pulang"]) . '
        </td>
         
        <td colspan="6" style="border-right: 1px solid black;">
            Lama Rawat Rata-rata : ' . htmlspecialchars($data_pasien["lama_ratarata"]) . '&nbsp;&nbsp;&nbsp;&nbsp;
            Perencanaan Pulang : ' . htmlspecialchars($data_pasien["perencanaan_pulang"]) . '
        </td>
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            Kondisi Klinik Saat Pulang : ' . htmlspecialchars($data_pasien["kondisi_klinis_pulang"]) . '
        </td>  
    </tr>
    <tr>
        <td colspan="12" style="border-left: 1px solid black;border-right: 1px solid black;">
            Perawatan Lanjutan Yang Diberikan Dirumah : ' . htmlspecialchars($data_pasien["perawatan_lanjutan_dirumah"]) . '
        </td>  
    </tr>
     <tr>
        <td colspan="5" style="border-left: 1px solid black;">
            Cara Transportasi : ' . htmlspecialchars($data_pasien["cara_transportasi_pulang"]) . '
        </td>  
        <td colspan="5" style="border-right: 1px solid black;">
            Transportasi Yang Digunakan : ' . htmlspecialchars($data_pasien["transportasi_digunakan"]) . '
        </td>
    </tr>
     <tr >
        <td colspan="3" style="border-left: 1px solid black; border-bottom: 1px solid black;border: 1px solid black;">
            Masalah Keperawatan
        </td>  
        <td colspan="7" style="border-right: 1px solid black; border-bottom: 1px solid black; border: 1px solid black;">
            ' . htmlspecialchars($data_pasien["rencana"]) . '
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
$pdf->Output('Laporan_Pemeriksaan_Ranap.pdf', 'I');

// Tutup koneksi
$koneksi->close();
?>
