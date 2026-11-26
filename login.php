<?php
require 'config.php';
if (!empty($_SESSION['user'])) go(home_for($_SESSION['user']['role']));
$error = '';
$jalur = $_POST['jalur'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $lab = trim($_POST['laboratorium'] ?? 'Lab Permesinan');

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
            $namaMatch = strcasecmp($u['nama'] ?? '', $identitas) === 0;

            if ($emailMatch || $shortMatch || $nimMatch || $namaMatch) {
                $matchedUser = $u;
                break;
            }
        }

        if ($matchedUser && password_verify($pass, $matchedUser['hash'])) {
            $uRole = norm_role($matchedUser['role']);
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'email' => $matchedUser['email'],
                'nama' => $matchedUser['nama'],
                'role' => $matchedUser['role'],
                'nim' => $matchedUser['nim'] ?? '',
                'lab' => $lab !== '' ? $lab : 'Lab Permesinan',
            ];
            go(home_for($uRole));
        }
        $error = 'Username / NIM / NIP atau password salah.';
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

<div class="wf-login-stage">
    <?php if ($error): ?>
        <div class="wf-login-alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="wf-login-split">
        <!-- Jalur 1: Staf Laboratorium -->
        <form class="wf-login-col wf-login-staf" method="post" action="login.php">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="jalur" value="staf">
            <h2>Inventaris Lab</h2>
            <p class="wf-login-sub">Masuk sebagai staf laboratorium</p>
            <div><span class="wf-pill wf-pill-staf">Staf</span></div>

            <label for="staf_user">Username</label>
            <input id="staf_user" name="username" type="text" placeholder="plp@polban.ac.id / teknisi / kalab / kajur" value="<?= $jalur === 'staf' ? e($_POST['username'] ?? '') : '' ?>" autocomplete="username" required>

            <label for="staf_pw">Password</label>
            <div class="pw">
                <input id="staf_pw" name="password" type="password" placeholder="••••••" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" data-target="staf_pw">Lihat</button>
            </div>

            <label for="laboratorium">Laboratorium</label>
            <select id="laboratorium" name="laboratorium">
                <option value="Lab Permesinan">Lab Permesinan</option>
                <option value="Lab Kerja Bangku">Lab Kerja Bangku</option>
                <option value="Lab Pengelasan &amp; Fabrikasi">Lab Pengelasan &amp; Fabrikasi</option>
                <option value="Lab CNC &amp; Otomasi">Lab CNC &amp; Otomasi</option>
            </select>

            <div>
                <button class="btn wf-btn-staf" type="submit">Masuk</button>
            </div>

            <p class="wf-login-note">Teknisi, PLP, Kalab, dan Kajur. Menu mengikuti peran akun.<br>
            <small>Demo: <code>teknisi@polban.ac.id</code>, <code>plp@polban.ac.id</code>, <code>kalab@polban.ac.id</code>, <code>kajur@polban.ac.id</code> (Password: <code>password</code>)</small></p>
        </form>

        <!-- Jalur 2: Pengaju / Pemakai Bahan (Mahasiswa & Dosen) -->
        <form class="wf-login-col wf-login-peminjam" method="post" action="login.php">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="jalur" value="peminjam">
            <h2>Pengaju Praktikum</h2>
            <p class="wf-login-sub">Mahasiswa dan dosen</p>
            <div><span class="wf-pill wf-pill-peminjam">Mahasiswa / Dosen</span></div>

            <label for="mhs_user">NIM / NIP / Email</label>
            <input id="mhs_user" name="username" type="text" placeholder="231211001 atau dosen@polban.ac.id" value="<?= $jalur === 'peminjam' ? e($_POST['username'] ?? '') : '' ?>" autocomplete="username" required>

            <label for="mhs_pw">Password / PIN</label>
            <div class="pw">
                <input id="mhs_pw" name="password" type="password" placeholder="••••••" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" data-target="mhs_pw">Lihat</button>
            </div>

            <div>
                <button class="btn wf-btn-peminjam" type="submit">Masuk sebagai pengaju</button>
            </div>

            <p class="wf-login-note">Dapat dibuka dari komputer bersama di lab. Pengajuan bahan 2 semester (Dosen) dan konfirmasi pengambilan bahan praktikum (Mahasiswa) tetap diverifikasi PLP/Teknisi.<br>
            <small>Demo: <code>dosen@polban.ac.id</code> atau NIM <code>231211001</code> / <code>mhs@polban.ac.id</code> (Password: <code>password</code>)</small></p>
        </form>
    </div>
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
