<?php
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

require_once 'myproject/vendor/autoload.php';
use LZCompressor\LZString;
date_default_timezone_set('UTC');


function stringDecrypt($key, $string)
{
    $key = hex2bin(hash('sha256', $key));
    $iv = substr($string, 0, 16);
    $string = substr($string, 16);
    return openssl_decrypt($string, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

$tglAsli = $_POST['tglPelayanan']; 
$kdMCUlokal = $_POST['kdMCUlokal'];
$tglPelayanan = date('d-m-Y', strtotime($tglAsli));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ambil data dari form
    $kdMCU                 = $_POST['kdMCU'];
    $noKunjungan           = $_POST['noKunjungan'];
    $kdProvider            = $_POST['kdProvider'];

    // var_dump($kdMCU);
    // exit();

    $tekananDarahSistole   = $_POST['tds'];
    $tekananDarahDiastole  = $_POST['tdd'];
    $radiologiFoto         = $_POST['radiologiFoto'];

    $darahRutinHemo        = $_POST['darahRutinHemo'];
    $darahRutinLeu         = $_POST['darahRutinLeu'];
    $darahRutinErit        = $_POST['darahRutinErit'];
    $darahRutinLaju        = $_POST['darahRutinLaju'];
    $darahRutinHema        = $_POST['darahRutinHema'];
    $darahRutinTrom        = $_POST['darahRutinTrom'];

    $lemakDarahHDL         = $_POST['lemakDarahHDL'];
    $lemakDarahLDL         = $_POST['lemakDarahLDL'];
    $lemakDarahChol        = $_POST['lemakDarahChol'];
    $lemakDarahTrigli      = $_POST['lemakDarahTrigli'];

    $gulaDarahSewaktu      = $_POST['gulaDarahSewaktu'];
    $gulaDarahPuasa        = $_POST['gulaDarahPuasa'];
    $gulaDarahPostPrandial = $_POST['gulaDarahPostPrandial'];
    $gulaDarahHbA1c        = $_POST['gulaDarahHbA1c'];

    $fungsiHatiSGOT        = $_POST['fungsiHatiSGOT'];
    $fungsiHatiSGPT        = $_POST['fungsiHatiSGPT'];
    $fungsiHatiGamma       = $_POST['fungsiHatiGamma'];
    $fungsiHatiProtKual    = $_POST['fungsiHatiProtKual'];
    $fungsiHatiAlbumin     = $_POST['fungsiHatiAlbumin'];

    $fungsiGinjalCrea      = $_POST['fungsiGinjalCrea'];
    $fungsiGinjalUreum     = $_POST['fungsiGinjalUreum'];
    $fungsiGinjalAsam      = $_POST['fungsiGinjalAsam'];

    $fungsiJantungABI      = $_POST['fungsiJantungABI'];
    $fungsiJantungEKG      = $_POST['fungsiJantungEKG'];
    $fungsiJantungEcho     = $_POST['fungsiJantungEcho'];

    $funduskopi            = $_POST['funduskopi'];
    $pemeriksaanLain       = $_POST['pemeriksaanLain'];
    $keterangan            = $_POST['keterangan'];


    // var_dump($darahRutinHemo);
    // exit();

    // === UPDATE DB lokal ===
    $query = "UPDATE pcare_mcu SET
        noKunjungan='$noKunjungan',
        kdProvider='$kdProvider',
        tglPelayanan='$tglAsli',
        tekananDarahSistole='$tekananDarahSistole',
        tekananDarahDiastole='$tekananDarahDiastole',
        radiologiFoto='$radiologiFoto',
        darahRutinHemo='$darahRutinHemo',
        darahRutinLeu='$darahRutinLeu',
        darahRutinErit='$darahRutinErit',
        darahRutinLaju='$darahRutinLaju',
        darahRutinHema='$darahRutinHema',
        darahRutinTrom='$darahRutinTrom',
        lemakDarahHDL='$lemakDarahHDL',
        lemakDarahLDL='$lemakDarahLDL',
        lemakDarahChol='$lemakDarahChol',
        lemakDarahTrigli='$lemakDarahTrigli',
        gulaDarahSewaktu='$gulaDarahSewaktu',
        gulaDarahPuasa='$gulaDarahPuasa',
        gulaDarahPostPrandial='$gulaDarahPostPrandial',
        gulaDarahHbA1c='$gulaDarahHbA1c',
        fungsiHatiSGOT='$fungsiHatiSGOT',
        fungsiHatiSGPT='$fungsiHatiSGPT',
        fungsiHatiGamma='$fungsiHatiGamma',
        fungsiHatiProtKual='$fungsiHatiProtKual',
        fungsiHatiAlbumin='$fungsiHatiAlbumin',
        fungsiGinjalCrea='$fungsiGinjalCrea',
        fungsiGinjalUreum='$fungsiGinjalUreum',
        fungsiGinjalAsam='$fungsiGinjalAsam',
        fungsiJantungABI='$fungsiJantungABI',
        fungsiJantungEKG='$fungsiJantungEKG',
        fungsiJantungEcho='$fungsiJantungEcho',
        funduskopi='$funduskopi',
        pemeriksaanLain='$pemeriksaanLain',
        keterangan='$keterangan'
         WHERE kdMCU='$kdMCUlokal'";
    mysqli_query($conn, $query);

    // === Kirim ke BPJS ===
    $payload = [
        "kdMCU"                 => (int)$kdMCU,
        "noKunjungan"           => $noKunjungan,
        "kdProvider"            => $kdProvider,
        "tglPelayanan"          => $tglPelayanan,
        "tekananDarahSistole"   => (int)$tekananDarahSistole,
        "tekananDarahDiastole"  => (int)$tekananDarahDiastole,
        "radiologiFoto"         => $radiologiFoto === "" ? null : $radiologiFoto,
        "darahRutinHemo"        => (int)$darahRutinHemo,
        "darahRutinLeu"         => (int)$darahRutinLeu,
        "darahRutinErit"        => (int)$darahRutinErit,
        "darahRutinLaju"        => (int)$darahRutinLaju,
        "darahRutinHema"        => (int)$darahRutinHema,
        "darahRutinTrom"        => (int)$darahRutinTrom,
        "lemakDarahHDL"         => (int)$lemakDarahHDL,
        "lemakDarahLDL"         => (int)$lemakDarahLDL,
        "lemakDarahChol"        => (int)$lemakDarahChol,
        "lemakDarahTrigli"      => (int)$lemakDarahTrigli,
        "gulaDarahSewaktu"      => (int)$gulaDarahSewaktu,
        "gulaDarahPuasa"        => (int)$gulaDarahPuasa,
        "gulaDarahPostPrandial" => (int)$gulaDarahPostPrandial,
        "gulaDarahHbA1c"        => (int)$gulaDarahHbA1c,
        "fungsiHatiSGOT"        => (int)$fungsiHatiSGOT,
        "fungsiHatiSGPT"        => (int)$fungsiHatiSGPT,
        "fungsiHatiGamma"       => (int)$fungsiHatiGamma,
        "fungsiHatiProtKual"    => (int)$fungsiHatiProtKual,
        "fungsiHatiAlbumin"     => (int)$fungsiHatiAlbumin,
        "fungsiGinjalCrea"      => (int)$fungsiGinjalCrea,
        "fungsiGinjalUreum"     => (int)$fungsiGinjalUreum,
        "fungsiGinjalAsam"      => (int)$fungsiGinjalAsam,
        "fungsiJantungABI"      => (int)$fungsiJantungABI,
        "fungsiJantungEKG"      => $fungsiJantungEKG === "" ? null : $fungsiJantungEKG,
        "fungsiJantungEcho"     => $fungsiJantungEcho === "" ? null : $fungsiJantungEcho,
        "funduskopi"            => $funduskopi === "" ? null : $funduskopi,
        "pemeriksaanLain"       => $pemeriksaanLain === "" ? null : $pemeriksaanLain,
        "keterangan"            => $keterangan === "" ? null : $keterangan
    ];

    $tStamp  = strval(time());
    $headers = generateBpjsHeaders($tStamp);

    $url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/MCU";

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CUSTOMREQUEST  => "PUT",   // <== beda di sini
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// langsung redirect, ga usah tunggu response decode
if ($httpcode == 200 || $httpcode == 201) {
    header("Location: /webkhanza?page=data_mcu&noKunjungan=" . urlencode($noKunjungan));
    exit;
} else {
    echo "<h4 style='margin-left:20rem;color:red'>❌ Update MCU gagal. Response: $response</h4>";
}
}
?>
