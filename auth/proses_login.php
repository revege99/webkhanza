<?php
$function_path = __DIR__ . '/../function/function_klinik.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username == '' || $password == '') {
    header("Location: index.php?page=login&error=1");
    exit;
}
$_SESSION['login'] = true;
$_SESSION['username'] = $username; // opsional

// CEK ADA REDIRECT ATAU TIDAK
if (isset($_SESSION['redirect_after_login'])) {
    $redirect = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    header("Location: $redirect");
} else {
    header("Location: index.php");
}
exit;

$sql = "SELECT * FROM users 
        WHERE username = ? 
          AND password = ? 
          AND status = 'aktif'
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $username, $password);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    // set session login
    $_SESSION['login']    = true;
    $_SESSION['user_id']  = $row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['nama']     = $row['nama'];
    $_SESSION['level']    = $row['level'];

    header("Location: index.php?page=home");
    exit;

} else {
    header("Location: index.php?page=login&error=1");
    exit;
}
