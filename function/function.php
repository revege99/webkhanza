<?php 


// Koneksi ke database
$conn = mysqli_connect("192.168.3.250", "rssl", "s1ntluc14", "sik");

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Fungsi untuk query biasa (mengembalikan array hasil)
function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Fungsi untuk query dengan prepared statement (menghindari SQL Injection)
function queryPrepared($query, $params = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt === false) {
        die('Query error: ' . mysqli_error($conn));
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // Semua parameter dianggap string
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $rows;
}









function kirimTaskBPJS($kodebooking, $taskid, $waktu) {
    // Konfigurasi
    $cons_id    = "22020";
    $secretKey  = "3aLBB8C8D8";
    $user_key   = "1cae203f209aa3d28db949c8a3806069";
    $url        = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/updatewaktu";

    date_default_timezone_set('UTC');
    $timestamp = time();
    $signature = base64_encode(hash_hmac('sha256', $cons_id . "&" . $timestamp, $secretKey, true));

    $headers = [
        "Content-Type: application/json",
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "user_key: $user_key"
    ];

    $body = json_encode([
        "kodebooking" => $kodebooking,
        "taskid"      => (int)$taskid,
        "waktu"       => (int)$waktu
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [
            "status" => false,
            "message" => curl_error($ch)
        ];
    }

    curl_close($ch);
    return json_decode($response, true); // Kembalikan sebagai array
}



















?>
