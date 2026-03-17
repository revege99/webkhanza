<?php 


// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "s1ntluc14", "sik_tester_lintong");

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}


// require_once '../myproject/vendor/autoload.php';
// use LZCompressor\LZString;
// date_default_timezone_set('UTC');

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


// function generateBpjsHeaders($timestamp) {
//     date_default_timezone_set('UTC');

//     $cons_id    = "13216";
//     $secret_key = "3nG5007800";
//     $user_key   = "f126b8a2c2488a9eec8e79fdd0bd55ef";
//     $signature  = base64_encode(hash_hmac('sha256', "$cons_id&$timestamp", $secret_key, true));
//     $auth       = "Basic " . base64_encode("0373B006.pcare:LebihH1dup!:095");

//     return [
//         "X-cons-id: $cons_id",
//         "X-timestamp: $timestamp",
//         "X-signature: $signature",
//         "X-authorization: $auth",
//         "user_key: $user_key"
//     ];
// }













function queryPrepared($query, $params = []) {
    global $conn; // koneksi mysqli
    $stmt = $conn->prepare($query);
    if(!$stmt) {
        echo "Prepare failed: ".$conn->error.PHP_EOL;
        return false;
    }

    if($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    if(!$stmt->execute()) {
        echo "Execute failed: ".$stmt->error.PHP_EOL;
        return false;
    }

    // jika SELECT
    if(stripos(trim($query), "SELECT") === 0) {
        $result = $stmt->get_result();
        $rows = [];
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    // untuk INSERT/UPDATE/DELETE
    return true;
}


function queryPreparedInsert($query, $params = []) {
    global $mysqli; // Pastikan variabel $mysqli sudah dideklarasikan sebagai koneksi MySQL

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        die("Error prepare statement: " . $mysqli->error);
    }

    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // semua parameter sebagai string (bisa diganti jika perlu)
        $stmt->bind_param($types, ...$params);
    }

    $result = $stmt->execute();
    if (!$result) {
        die("Error execute statement: " . $stmt->error);
    }

    return $result;
}








function insertMCUToDB($conn, $payload) {
    $stmt = $conn->prepare("INSERT INTO pcare_MCU 
        (kdMCU, noKunjungan, keluhan, sistole, diastole, beratBadan, tinggiBadan, lingkarPerut, respRate, heartRate, terapi, kdDokter, tanggal_insert)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param(
        "issiiiiiisss",
        $payload['kdMCU'],
        $payload['noKunjungan'],
        $payload['keluhan'],
        $payload['sistole'],
        $payload['diastole'],
        $payload['beratBadan'],
        $payload['tinggiBadan'],
        $payload['lingkarPerut'],
        $payload['respRate'],
        $payload['heartRate'],
        $payload['terapi'],
        $payload['kdDokter']
    );

    if (!$stmt->execute()) {
        echo "<script>alert('Kirim BPJS berhasil, tapi gagal simpan lokal: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}












?>
