
<?php
$function_path = __DIR__ . '/../function/function_klinik.php';
if (file_exists($function_path)) {
    require_once $function_path;
} else {
    die("Error: File function.php tidak ditemukan di $function_path");
}

if (isset($_GET['kdObatSK'])) {
    $kdObatSK = $_GET['kdObatSK'];

    $stmt = $conn->prepare("DELETE FROM pcare_obat_diberikan WHERE kdObatSK = ?");
    $stmt->bind_param("s", $kdObatSK);

    if ($stmt->execute()) {
        header("Location: ?page=data_obat_pcare_lokal&hapus=success");
        exit;
    } else {

        header("Location: ?page=detail_coa&hapus=fail");
        exit;
    }
}
?>
