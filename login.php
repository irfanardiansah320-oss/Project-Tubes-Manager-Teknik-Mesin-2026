<?php
require 'config.php';
if (!empty($_SESSION['user'])) go(home_for($_SESSION['user']['role']));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!hash_equals(csrf(), $_POST['csrf'] ?? '')) {
        $error = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
    } else {
        $users = db()['users'];
        $n = find_idx($users, 'email', $email);
        if ($n !== null && password_verify($pass, $users[$n]['hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['email' => $users[$n]['email'], 'nama' => $users[$n]['nama'], 'role' => $users[$n]['role']];
            go(home_for($users[$n]['role']));
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk - <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login">
<section class="hero">
    <h1>INVENTARIS<br>BARANG</h1>
    <p>JURUSAN TEKNIK MESIN</p>
</section>
<div class="strip" aria-hidden="true"></div>
<section class="panel">
    <h2>LOGIN ACCOUNT</h2>
    <form class="card" method="post" action="login.php">
        <div class="brand">POLITEKNIK<br>NEGERI BANDUNG</div>
        <h3>MASUK</h3>
        <?php if ($error): ?><p class="err" role="alert"><?= e($error) ?></p><?php endif; ?>
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" type="email" placeholder="Username@polban.ac.id" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required>
        <label for="password">Password</label>
        <div class="pw">
            <input id="password" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
            <button type="button" id="toggle" aria-label="Tampilkan password">Lihat</button>
        </div>
        <button class="btn" type="submit">SELANJUTNYA</button>
    </form>
</section>
<script>
const pw = document.getElementById('password'), t = document.getElementById('toggle');
t.addEventListener('click', () => {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    t.textContent = show ? 'Sembunyikan' : 'Lihat';
    t.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
});
</script>
</body>
</html>
