<?php
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kdObatSK'], $_POST['noKunjungan'])) {
    $kdObatSK = $_POST['kdObatSK'];
    $noKunjungan = $_POST['noKunjungan'];
    $kode_brng = $_POST['kode_brng'];
    $no_rawat = $_POST['no_rawat'];
    $jml = $_POST['jml'];


    if (!$kdObatSK || !$noKunjungan || !$kode_brng || !$no_rawat) {
        echo "<script>alert('Data tidak lengkap.'); window.history.back();</script>";
        exit();
    }

    // echo '<pre style="margin-left:20rem;">';
    // print_r($jml);
    // echo '</pre>';
    // die();


    $tStamp  = strval(time());
    $headers = generateBpjsHeaders($tStamp);
    $url     = "https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev/obat/$kdObatSK/kunjungan/$noKunjungan";

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

  if (!$kdObatSK || !$noKunjungan || !$kode_brng || !$no_rawat || $jml <= 0) {
    echo "<script>alert('❌ Data tidak lengkap.'); window.history.back();</script>";
    exit();
}

if ($httpCode == 200 || $httpCode == 201 || $httpCode == 401) {
    // Mulai transaksi
    $conn->begin_transaction();

    try {
        // 1️⃣ Hapus dari pcare_obat_diberikan
        $stmt1 = $conn->prepare("DELETE FROM pcare_obat_diberikan WHERE kdObatSK = ?");
        $stmt1->bind_param("s", $kdObatSK);
        if (!$stmt1->execute()) {
            throw new Exception("Gagal hapus dari pcare_obat_diberikan");
        }
        $stmt1->close();

        // 2️⃣ Hapus dari detail_pemberian_obat
        $stmt2 = $conn->prepare("DELETE FROM detail_pemberian_obat WHERE kode_brng = ? AND no_rawat = ?");
        $stmt2->bind_param("ss", $kode_brng, $no_rawat);
        if (!$stmt2->execute()) {
            throw new Exception("Gagal hapus dari detail_pemberian_obat");
        }
        $stmt2->close();

        // 3️⃣ Tambahkan stok ke gudangbarang
        $stmt3 = $conn->prepare("
            UPDATE gudangbarang 
            SET stok = stok + ? 
            WHERE kode_brng = ? AND kd_bangsal = 'AP'
        ");
        $stmt3->bind_param("is", $jml, $kode_brng);
        if (!$stmt3->execute()) {
            throw new Exception("Gagal update stok gudang");
        }
        $stmt3->close();

        // ✅ Commit kalau semua berhasil
        $conn->commit();
        echo "<script>alert('✅ Data berhasil dihapus dari BPJS & lokal, stok gudang ditambahkan.'); window.location.href='?page=data_obat&noKunjungan=$noKunjungan';</script>";

    } catch (Exception $e) {
        // ❌ Rollback kalau ada error
        $conn->rollback();
        echo "<script>alert('❌ Transaksi gagal: " . addslashes($e->getMessage()) . "'); window.location.href='?page=data_obat&hapus=fail';</script>";
    }

} else {
    echo "<script>alert('❌ Gagal menghapus obat dari BPJS: $message'); window.history.back();</script>";
}
}


// Fungsi untuk generate headers
function generateBpjsHeaders($timestamp) {
    date_default_timezone_set('UTC');

    $cons_id    = "25685";
    $secret_key = "9hX4AEEB8C";
    $user_key   = "a0e225428271c8e127fc2c539ff0192f";

    $data      = $cons_id . "&" . $timestamp;
    $signature = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
    $auth      = "Basic " . base64_encode("tester.stmartina:Bpjs123**:095");

    return [
        "X-cons-id: $cons_id",
        "X-timestamp: $timestamp",
        "X-signature: $signature",
        "X-authorization: $auth",
        "user_key: $user_key"
    ];
}
?>
