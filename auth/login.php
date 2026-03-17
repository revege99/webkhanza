<?php
// Jika sudah login, langsung ke home
if (isset($_SESSION['login'])) {
    header("Location: index.php?page=home");
    exit;
}

// Tangkap error
$error = isset($_GET['error']) ? $_GET['error'] : null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login - Sistem Bridging Khanza PCare</title>

<style>

body{
    margin:0;
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: linear-gradient(135deg,#0d6efd,#0a58ca);
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* Card Login */

.login-card{
    width:380px;
    background:white;
    padding:35px;
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.2);
}

/* Title */

.system-title{
    text-align:center;
    margin-bottom:25px;
}

.system-title h2{
    margin:0;
    color:#0d6efd;
}

.system-title p{
    margin:5px 0 0;
    font-size:14px;
    color:#666;
}

/* Input */

.form-group{
    margin-top:15px;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:6px;
    font-size:14px;
}

input:focus{
    outline:none;
    border-color:#0d6efd;
}

/* Button */

button{
    width:100%;
    margin-top:20px;
    padding:12px;
    border:none;
    background:#0d6efd;
    color:white;
    font-size:15px;
    border-radius:6px;
    cursor:pointer;
    transition:0.3s;
}

button:hover{
    background:#084298;
}

/* Error */

.error{
    background:#f8d7da;
    color:#842029;
    padding:10px;
    border-radius:6px;
    margin-bottom:15px;
    font-size:14px;
}

.footer{
    text-align:center;
    font-size:12px;
    color:#777;
    margin-top:15px;
}

</style>
</head>

<body>

<div class="login-card">

<div class="system-title">
<h2>Sistem Bridging</h2>
<p>Khanza - BPJS PCare</p>
</div>

<?php if ($error == 1): ?>
<div class="error">
Username atau Password salah
</div>
<?php endif; ?>

<form method="post" action="index.php?page=proses_login">

<div class="form-group">
<input type="text" name="username" placeholder="Username" required>
</div>

<div class="form-group">
<input type="password" name="password" placeholder="Password" required>
</div>

<button type="submit">
Login Sistem
</button>

</form>

<div class="footer">
© <?php echo date("Y"); ?> Sistem Bridging Khanza PCare
</div>

</div>

</body>
</html>

