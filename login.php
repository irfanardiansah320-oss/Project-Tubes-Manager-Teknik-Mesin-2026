<?php
require 'config.php';
if (!empty($_SESSION['user'])) go(home_for($_SESSION['user']['role']));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $lab = trim($_POST['laboratorium'] ?? '');

    if (!hash_equals(csrf(), $_POST['csrf'] ?? '')) {
        $error = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
    } else {
        $users = db()['users'];
        $matchedUser = null;

        foreach ($users as $u) {
            $emailMatch = strcasecmp($u['email'] ?? '', $identitas) === 0;
            $shortUser = explode('@', $u['email'] ?? '')[0];
            $shortMatch = strcasecmp($shortUser, $identitas) === 0;
            $nimMatch = !empty($u['nim']) && strcasecmp($u['nim'], $identitas) === 0;
            $nipMatch = !empty($u['nip']) && strcasecmp($u['nip'], $identitas) === 0;
            $namaMatch = strcasecmp($u['nama'] ?? '', $identitas) === 0;

            if ($emailMatch || $shortMatch || $nimMatch || $nipMatch || $namaMatch) {
                $matchedUser = $u;
                break;
            }
        }

        if ($matchedUser && password_verify($pass, $matchedUser['hash'])) {
            $uRole = norm_role($matchedUser['role']);
            $uJabatan = norm_jabatan($matchedUser);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'email' => $matchedUser['email'],
                'nama' => $matchedUser['nama'],
                'role' => $uRole,
                'jabatan' => $uJabatan,
                'nim' => $matchedUser['nim'] ?? '',
                'nip' => $matchedUser['nip'] ?? '',
                'lab' => $lab !== '' ? $lab : (!empty($matchedUser['lab']) ? $matchedUser['lab'] : 'Lab Permesinan'),
            ];
            go(home_for($uRole));
        }
        $error = 'Username / Email / NIM / NIP atau password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk - <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="wf-login-body">

<div class="wf-login-stage" style="max-width:500px">
    <?php if ($error): ?>
        <div class="wf-login-alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="wf-login-single" method="post" action="login.php">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
            <h2 style="margin:0;color:#165B33;font-size:26px;font-weight:800">Inventaris Lab</h2>
            <span class="wf-pill wf-pill-staf" style="margin:0">Jurusan Teknik Mesin</span>
        </div>
        <p class="wf-login-sub" style="margin:2px 0 18px">Sistem Inventarisasi Bahan Habis Pakai (5 Role Utama)</p>

        <label for="login_user">Email / Username / NIM / NIP</label>
        <input id="login_user" name="username" type="text" placeholder="Masukkan email, username, NIM, atau NIP" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required>

        <label for="login_pw">Password</label>
        <div class="pw">
            <input id="login_pw" name="password" type="password" placeholder="••••••" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" data-target="login_pw">Lihat</button>
        </div>

        <label for="laboratorium">Laboratorium / Unit Kerja (Opsional)</label>
        <select id="laboratorium" name="laboratorium">
            <option value="">-- Sesuai Penempatan Akun --</option>
            <option value="Lab Permesinan">Lab Permesinan</option>
            <option value="Lab Pengelasan &amp; Fabrikasi">Lab Pengelasan &amp; Fabrikasi</option>
            <option value="Lab CNC &amp; Otomasi">Lab CNC &amp; Otomasi</option>
            <option value="Lab Uji Bahan &amp; Metrologi">Lab Uji Bahan &amp; Metrologi</option>
            <option value="Kantor Jurusan Teknik Mesin">Kantor Jurusan Teknik Mesin</option>
        </select>

        <button class="btn wf-btn-staf" type="submit" style="width:100%;padding:11px 20px !important;font-size:14px">Masuk</button>

        <div class="wf-login-demo-box">
            <div style="font-weight:700;color:#101828;margin-bottom:6px;font-size:12.5px">Daftar Akun Demo (Password semua akun: <code>password</code>):</div>
            <div class="wf-demo-grid">
                <div><strong>1. Role Dosen (3 Jabatan Berbeda Menu):</strong></div>
                <div>&bull; Dosen Biasa (Tanpa Jabatan): <code>dosen@polban.ac.id</code></div>
                <div>&bull; Dosen Jabatan Kalab: <code>dosen.kalab@polban.ac.id</code> <small>(atau <code>kalab@polban.ac.id</code>)</small></div>
                <div>&bull; Dosen Jabatan Kajur: <code>dosen.kajur@polban.ac.id</code> <small>(atau <code>kajur@polban.ac.id</code>)</small></div>
                <div style="margin-top:4px"><strong>2. Role Lainnya:</strong></div>
                <div>&bull; Administrator: <code>admin@polban.ac.id</code></div>
                <div>&bull; PLP: <code>plp@polban.ac.id</code> &middot; Teknisi: <code>teknisi@polban.ac.id</code></div>
                <div>&bull; Mahasiswa (Individu): <code>231211001</code> (<small><code>mhs@polban.ac.id</code></small>) / <code>231211002</code></div>
            </div>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Sembunyikan' : 'Lihat';
    });
});
</script>
</body>
</html>
