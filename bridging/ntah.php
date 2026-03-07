<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cons_id    = "25685";
    $secret_key = "9hX4AEEB8C";
    $user_key   = "a0e225428271c8e127fc2c539ff0192f";
    $username   = "tester.stmartina";
    $password   = "Bpjs123**";
    $kd_aplikasi = "095";

    date_default_timezone_set('UTC');
    $timestamp = time();
    $data = $cons_id . "&" . $timestamp;
    $signature = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
    $authorization = "Basic " . base64_encode("$username:$password:$kd_aplikasi");

    // Ambil data dari form
    $body = [
        "kdObatSK"        => (int)$_POST['kdObatSK'],
        "noKunjungan"     => $_POST['noKunjungan'],
        "racikan"         => $_POST['racikan'] === "true",
        "kdRacikan"       => ($_POST['kdRacikan'] === "" ? null : $_POST['kdRacikan']),
        "obatDPHO"        => $_POST['obatDPHO'] === "true",
        "kdObat"          => $_POST['kdObat'],
        "signa1"          => (int)$_POST['signa1'],
        "signa2"          => (int)$_POST['signa2'],
        "jmlObat"         => (int)$_POST['jmlObat'],
        "jmlPermintaan"   => (int)$_POST['jmlPermintaan'],
        "nmObatNonDPHO"   => $_POST['nmObatNonDPHO']
    ];

    $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $headers = [
        "Content-Type: application/json",
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "X-authorization: $authorization",
        "user_key: $user_key"
    ];

    file_put_contents("debug.txt", "timestamp: $timestamp\nsignature: $signature\nauthorization: $authorization\n\nBODY:\n$jsonBody\n\nHEADERS:\n" . print_r($headers, true));

    $ch = curl_init("https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/obat/kunjungan");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<pre style='margin-left:13rem'>Status: $http_status\nResponse:\n$response</pre>";
    echo "<pre style='margin-left:13rem'>BODY Sent:\n$jsonBody</pre>";
    echo "<pre style='margin-left:13rem'>HEADERS Sent:\n" . print_r($headers, true) . "</pre>";
}
?>
