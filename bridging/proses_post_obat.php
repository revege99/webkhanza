<?php 
date_default_timezone_set('UTC');
$tStamp = strval(time());
$success = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payload = [
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

    // Debug payload
    echo '<h3 style="margin-left:20rem">Payload yang dikirim:</h3>';
    echo '<pre style="margin-left:20rem">';
    echo json_encode($payload, JSON_PRETTY_PRINT);
    echo '</pre>';

    // Panggil fungsi kirimObat dan simpan response mentah
    $raw_response = kirimObat($payload);

    // Debug response mentah dari BPJS
    echo '<h3 style="margin-left:20rem">Response mentah dari server BPJS:</h3>';
    echo '<pre style="margin-left:20rem">';
    var_dump($raw_response);
    echo '</pre>';

    // Decode JSON
    $response = json_decode($raw_response, true);

    // Cek jika decode gagal
    if ($response === null) {
        echo "<h3 style='margin-left:20rem;color:red'>Gagal decode response JSON dari server BPJS</h3>";
        exit;
    }

    // Ambil metadata
    $code = $response['metadata']['code'] ?? '0';
    $message = $response['metadata']['message'] ?? 'Tidak ada pesan';

    echo "<h3 style='margin-left:20rem'>Status Code: $code</h3>";
    echo "<h3 style='margin-left:20rem'>Pesan Server: $message</h3>";

    // Jika tidak berhasil
    if (!in_array($code, [200, 208])) {
        echo "<script>alert('Gagal: $message'); window.location.href='?page=post_obat';</script>";
        $success = false;
    } else {
        echo "<script>alert('Obat berhasil dikirim ke BPJS!'); window.location.href='?page=post_obat';</script>";
    }
}

function generateBpjsHeaders() {
    date_default_timezone_set('UTC');

    $cons_id    = "25685";
    $secret_key = "9hX4AEEB8C";
    $user_key   = "a0e225428271c8e127fc2c539ff0192f";

    $timestamp   = time();
    $data        = $cons_id . "&" . $timestamp;
    $signature   = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
    $auth        = "Basic " . base64_encode("tester.stmartina:Bpjs123**:095");

    return [
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "X-authorization: $auth",
        "user_key: $user_key",
        // "Content-Type: application/json"
    ];
}

function kirimObat($payload) {
    $dataJson = json_encode($payload);
    $url = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/obat/kunjungan";

    $headers = generateBpjsHeaders();

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    echo '<pre style="margin-left:20rem">';
    echo json_encode($headers, JSON_PRETTY_PRINT);
    echo '</pre>';
    // exit();

    $response = curl_exec($ch);

    if ($response === false) {
        return [
            'metadata' => [
                'code' => 500,
                'message' => "CURL Error: " . curl_error($ch)
            ]
        ];
    }



    curl_close($ch);
    return json_decode($response, true);
}



 ?>