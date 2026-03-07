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

if (isset($_POST['submit'])) {
    $kdMCU                 = $_POST['kdMCU'];
    $noKunjungan           = $_POST['noKunjungan'];
    $kdProvider            = $_POST['kdProvider'];
    // var_dump($kdProvider);
    // exit();
    $tglPelayanan = date('Y-m-d', strtotime($_POST['tglPelayanan']));

    $tekananDarahSistole   = $_POST['tekananDarahSistole'];
    $tekananDarahDiastole  = $_POST['tekananDarahDiastole'];
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
    


    $query  = "INSERT INTO pcare_mcu 
      VALUES (
        '$kdMCU', '$noKunjungan', '$kdProvider', '$tglPelayanan',
        '$tekananDarahSistole', '$tekananDarahDiastole', '$radiologiFoto',
        '$darahRutinHemo', '$darahRutinLeu', '$darahRutinErit', '$darahRutinLaju', '$darahRutinHema', '$darahRutinTrom',
        '$lemakDarahHDL', '$lemakDarahLDL', '$lemakDarahChol', '$lemakDarahTrigli',
        '$gulaDarahSewaktu', '$gulaDarahPuasa', '$gulaDarahPostPrandial', '$gulaDarahHbA1c',
        '$fungsiHatiSGOT', '$fungsiHatiSGPT', '$fungsiHatiGamma', '$fungsiHatiProtKual', '$fungsiHatiAlbumin',
        '$fungsiGinjalCrea', '$fungsiGinjalUreum', '$fungsiGinjalAsam',
        '$fungsiJantungABI', '$fungsiJantungEKG', '$fungsiJantungEcho',
        '$funduskopi', '$pemeriksaanLain', '$keterangan', '$kdProvider'
    )";

      mysqli_query($conn, $query);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = [
    "kdMCU"                   => (int)$_POST['kdMCU'],
    "noKunjungan"            => $_POST['noKunjungan'],
    "kdProvider"             => $_POST['kdProvider'],
    "tglPelayanan"           => $_POST['tglPelayanan'],
    "tekananDarahSistole"    => (int)$_POST['tekananDarahSistole'],
    "tekananDarahDiastole"   => (int)$_POST['tekananDarahDiastole'],
    "radiologiFoto"          => $_POST['radiologiFoto'] === "" ? null : $_POST['radiologiFoto'],
    "darahRutinHemo"         => (int)$_POST['darahRutinHemo'],
    "darahRutinLeu"          => (int)$_POST['darahRutinLeu'],
    "darahRutinErit"         => (int)$_POST['darahRutinErit'],
    "darahRutinLaju"         => (int)$_POST['darahRutinLaju'],
    "darahRutinHema"         => (int)$_POST['darahRutinHema'],
    "darahRutinTrom"         => (int)$_POST['darahRutinTrom'],
    "lemakDarahHDL"          => (int)$_POST['lemakDarahHDL'],
    "lemakDarahLDL"          => (int)$_POST['lemakDarahLDL'],
    "lemakDarahChol"         => (int)$_POST['lemakDarahChol'],
    "lemakDarahTrigli"       => (int)$_POST['lemakDarahTrigli'],
    "gulaDarahSewaktu"       => (int)$_POST['gulaDarahSewaktu'],
    "gulaDarahPuasa"         => (int)$_POST['gulaDarahPuasa'],
    "gulaDarahPostPrandial"  => (int)$_POST['gulaDarahPostPrandial'],
    "gulaDarahHbA1c"         => (int)$_POST['gulaDarahHbA1c'],
    "fungsiHatiSGOT"         => (int)$_POST['fungsiHatiSGOT'],
    "fungsiHatiSGPT"         => (int)$_POST['fungsiHatiSGPT'],
    "fungsiHatiGamma"        => (int)$_POST['fungsiHatiGamma'],
    "fungsiHatiProtKual"     => (int)$_POST['fungsiHatiProtKual'],
    "fungsiHatiAlbumin"      => (int)$_POST['fungsiHatiAlbumin'],
    "fungsiGinjalCrea"       => (int)$_POST['fungsiGinjalCrea'],
    "fungsiGinjalUreum"      => (int)$_POST['fungsiGinjalUreum'],
    "fungsiGinjalAsam"       => (int)$_POST['fungsiGinjalAsam'],
    "fungsiJantungABI"       => (int)$_POST['fungsiJantungABI'],
    "fungsiJantungEKG"       => $_POST['fungsiJantungEKG'] === "" ? null : $_POST['fungsiJantungEKG'],
    "fungsiJantungEcho"      => $_POST['fungsiJantungEcho'] === "" ? null : $_POST['fungsiJantungEcho'],
    "funduskopi"             => $_POST['funduskopi'] === "" ? null : $_POST['funduskopi'],
    "pemeriksaanLain"        => $_POST['pemeriksaanLain'] === "" ? null : $_POST['pemeriksaanLain'],
    "keterangan"             => $_POST['keterangan'] === "" ? null : $_POST['keterangan'],
    "kd_provider"             => $_POST['kd_provider'] === "" ? null : $_POST['kd_provider']
];

    $tStamp  = strval(time());
    $headers = generateBpjsHeaders($tStamp);

    $url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/MCU";

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $responseArr = json_decode($response, true);

    // echo "<h3 style='margin-left:20rem;'>📨 Response dari BPJS:</h3>";
    // echo "<pre style='margin-left:20rem;'>" . htmlentities($response) . "</pre>";

   
    if (($httpcode == 200 || $httpcode == 201) && isset($responseArr['response'])) {
    
    $decryptKey = $cons_id . '&' . $tStamp;

    $encodedResponse = base64_decode($responseArr['response']);
    $decrypted       = stringDecrypt($decryptKey, $encodedResponse);
    $finalJson       = LZString::decompressFromEncodedURIComponent($decrypted);

    // Ambil noKunjungan dari hasil JSON
    $data = json_decode($finalJson, true);
    $noKunjungan = isset($data['noKunjungan']) ? $data['noKunjungan'] : $noKunjungan;

    // Redirect ke halaman tujuan
    header("Location: /webkhanza?page=data_mcu&noKunjungan=" . urlencode($noKunjungan));
    exit;
} else {
    echo "<h4 style='margin-left:20rem;color:red'>❌ Tidak ada response terenkripsi untuk didekripsi.</h4>";
}

}
?>
