<?php
$u = $_SESSION['user'];
$roleKey = norm_role($u['role']);
$roleFile = ROLE_FILES[$roleKey] ?? 'index.php';
$menus = ROLE_MENUS[$roleKey] ?? [];
$activeKey = str_replace('.php', '', $active ?? '');
$flash = flash();
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> - <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app">
<header class="topbar">
    <strong>INVENTARIS BARANG</strong>
    <form class="who" action="logout.php" method="post"><?= e($u['nama']) ?><?= !empty($u['nim']) ? ' (' . e($u['nim']) . ')' : '' ?> <small>(<?= e(ROLES[$u['role']] ?? $u['role']) ?>)</small> <button class="linkbtn">Keluar</button></form>
</header>
<div class="shell">
<nav class="menu" aria-label="Menu utama">
<?php foreach ($menus as $fitur => $label): ?>
    <a href="<?= e("$roleFile?fitur=$fitur") ?>"<?= $fitur === $activeKey ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<main>
<h1><?= e($title) ?></h1>
<?php if ($flash): ?><p class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></p><?php endif; ?>
