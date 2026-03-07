<?php
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}



$base_url   = 'https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/';
$endpoint   = 'kunjungan';
$cons_id    = '25685';
$secret_key = '9hX4AEEB8C';
$user_key   = 'a0e225428271c8e127fc2c539ff0192f';
$kdAplikasi = '095';
$Xauthorization = 'Basic dGVzdGVyLnN0bWFydGluYTpCcGpzMTIzKio6MDk1';


$payload = [
    "noKunjungan"         => $_POST['noKunjungan'] ?? null,
    "noKartu"             => $_POST['noKartu'] ?? null,
    "tglDaftar"           => $_POST['tglDaftar'] ?? null,
    "kdPoli"              => $_POST['kdPoli'] ?? null,
    "keluhan"             => $_POST['keluhan'] ?? null,
    "kdSadar"             => $_POST['kdSadar'] ?? null,
    "sistole"             => (int)($_POST['sistole'] ?? 0),
    "diastole"            => (int)($_POST['diastole'] ?? 0),
    "beratBadan"          => (int)($_POST['beratBadan'] ?? 0),
    "tinggiBadan"         => (int)($_POST['tinggiBadan'] ?? 0),
    "respRate"            => (int)($_POST['respRate'] ?? 0),
    "heartRate"           => (int)($_POST['heartRate'] ?? 0),
    "lingkarPerut"        => (int)($_POST['lingkarPerut'] ?? 0),
    "kdStatusPulang"      => $_POST['kdStatusPulang'] ?? null,
    "tglPulang"           => $_POST['tglPulang'] ?? null,
    "kdDokter"            => $_POST['kdDokter'] ?? null,
    "kdDiag1"             => $_POST['kdDiag1'] ?? null,
    "kdDiag2"             => $_POST['kdDiag2'] ?: null,
    "kdDiag3"             => $_POST['kdDiag3'] ?: null,
    "kdPoliRujukInternal" => $_POST['kdPoliRujukInternal'] ?: null,
    "rujukLanjut" => [
        "kdppk"         => $_POST['kdppk'] ?? null,
        "tglEstRujuk"   => $_POST['tglEstRujuk'] ?? null,
        "subSpesialis"  => [
            "kdSubSpesialis1" => $_POST['kdSubSpesialis1'] ?? null,
            "kdSarana"        => (int)($_POST['kdSarana'] ?? 0)
        ],
        "khusus"        => null
    ],
    "kdTacc"         => -1,
    "alasanTacc"     => null,
    "anamnesa"       => $_POST['anamnesa'] ?? '',
    "alergiMakan"    => $_POST['alergiMakan'] ?? '',
    "alergiUdara"    => $_POST['alergiUdara'] ?? '',
    "alergiObat"     => $_POST['alergiObat'] ?? '',
    "kdPrognosa"     => $_POST['kdPrognosa'] ?? '',
    "terapiObat"     => $_POST['terapiObat'] ?? '',
    "terapiNonObat"  => $_POST['terapiNonObat'] ?? '',
    "bmhp"           => $_POST['bmhp'] ?? ''
];

       echo '<h3 style="margin-left:20rem;">Debugging Info</h3>';
echo '<pre style="margin-left:20rem;">';
// print_r($payload);
echo json_encode($payload, JSON_PRETTY_PRINT);
echo '</pre>';
// exit;


date_default_timezone_set('UTC');
$tStamp = time();
$encodedSignature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $tStamp, $secret_key, true));

$headers = [
    
    "X-cons-id: $cons_id",
    "X-timestamp: $tStamp",
    "X-signature: $encodedSignature",
    "X-authorization: $Xauthorization",
    "user_key: $user_key"
];


$url = $base_url . $endpoint ;


echo '<h3 style="margin-left:20rem;">Header</h3>';
echo '<pre style="margin-left:20rem;">';
print_r($headers);
print_r($url);

echo '</pre>';
// exit;
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);


echo "<pre>Response Code: $httpcode\n";
echo "Response:\n$response</pre>";


if ($httpcode == 200 || $httpcode == 201) {
   // Query update data kunjungan di database lokal
$noKunjungan = $conn->real_escape_string($_POST['noKunjungan']);
$keluhan     = $conn->real_escape_string($_POST['keluhan']);
$kdPoli      = $conn->real_escape_string($_POST['kdPoli']);
$kdDokter    = $conn->real_escape_string($_POST['kdDokter']);
$kdDiag1     = $conn->real_escape_string($_POST['kdDiag1']);
$tglPulangRaw = $_POST['tglPulang'] ?? null;
$tglPulang = $tglPulangRaw ? date('Y-m-d', strtotime($tglPulangRaw)) : null;
$tglPulang = $conn->real_escape_string($tglPulang);

$kdStatusPulang = $conn->real_escape_string($_POST['kdStatusPulang']);
// Tambahkan field lainnya sesuai kebutuhan

$sql = "UPDATE pcare_rujuk_subspesialis 
        SET 
            keluhan = '$keluhan',
            kdPoli = '$kdPoli',
            kdDokter = '$kdDokter',
            kdDiag1 = '$kdDiag1',
            tglPulang = '$tglPulang',
            kdStatusPulang = '$kdStatusPulang'
        WHERE noKunjungan = '$noKunjungan'";

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('✅ Data lokal juga berhasil diupdate');</script>";
} else {
    echo "<script>alert('⚠️ Gagal update ke database lokal: {$conn->error}');</script>";
}

}
?>
