<?php
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kdMCU'], $_POST['noKunjungan'])) {
    $kdMCU = $_POST['kdMCU'];
    $noKunjungan = $_POST['noKunjungan'];

    if (!$kdMCU || !$noKunjungan) {
        echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
        exit();
    }

    $tStamp  = strval(time());
    $headers = generateBpjsHeaders($tStamp);
    $url     = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/MCU/$kdMCU/kunjungan/$noKunjungan";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result  = json_decode($response, true);
    $message = $result['metaData']['message'] ?? 'Tidak ada pesan dari BPJS';

    if ($httpCode == 200 || $httpCode == 201 || $httpCode == 401) {
        // Hapus dari database lokal
        $stmt = $conn->prepare("DELETE FROM pcare_mcu WHERE noKunjungan = ?");
        if ($stmt) {
            $stmt->bind_param("s", $noKunjungan);
            if ($stmt->execute()) {
                echo "<script>alert('Data Berhasil Di hapus'); window.history.back();</script>";
            } else {
                header("Location: ?page=data_mcu&hapus=partial");
                exit();
            }
            $stmt->close();
        } else {
            echo "<script>alert('❌ Prepare statement gagal.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('❌ Gagal menghapus mcu dari BPJS: $message'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('❌ Permintaan tidak valid atau data tidak lengkap.'); window.history.back();</script>";
}

// Fungsi untuk generate headers

?>
