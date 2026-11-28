<?php
$u = $_SESSION['user'];
$roleKey = norm_role($u['role']);
$jabKey = current_jabatan();
$roleFile = ROLE_FILES[$roleKey] ?? 'index.php';
$menus = get_user_menus();
$activeKey = str_replace('.php', '', $active ?? '');
$flash = flash();

$themeInfo = role_theme_meta($roleKey, $activeKey, $jabKey);
$badges = sidebar_badges($roleKey, $jabKey);
$menuGroups = role_menu_groups($roleKey, $menus, $jabKey);
$labName = $u['lab'] ?? 'Lab Permesinan';
$headerCta = page_header_cta($roleKey, $activeKey, $roleFile, $jabKey);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> - <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app role-<?= e($roleKey) ?> jab-<?= e($jabKey) ?>" style="--theme:<?= e($themeInfo['theme']) ?>;--theme-box:<?= e($themeInfo['box']) ?>;--theme-soft:<?= e($themeInfo['soft']) ?>;--theme-border:<?= e($themeInfo['border']) ?>">

<!-- Overlay backdrop untuk mobile sidebar -->
<div class="sb-overlay" id="sbOverlay" aria-hidden="true"></div>

<div class="shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
        <!-- Tombol tutup sidebar di mobile -->
        <button class="sb-close" id="sbClose" aria-label="Tutup menu">&#10005;</button>

        <div class="sb-brand">Inventaris Lab</div>

        <!-- Info user di atas sidebar (mobile-style seperti referensi) -->
        <div class="sb-user-top">
            <div class="sb-avatar"><?= e(mb_substr($u['nama'], 0, 1)) ?></div>
            <div class="sb-user-top-info">
                <div class="sb-user-name"><?= e($u['nama']) ?><?= !empty($u['nim']) ? '<br><small>' . e($u['nim']) . '</small>' : '' ?></div>
                <div class="sb-user-role"><?= e($themeInfo['role_short']) ?> &middot; <?= e($labName) ?></div>
            </div>
        </div>

        <nav class="sb-nav" aria-label="Menu utama">
            <?php foreach ($menuGroups as $groupTitle => $groupItems): ?>
            <div class="sb-group">
                <div class="sb-group-title"><?= e($groupTitle) ?></div>
                <?php foreach ($groupItems as $fitur => $label):
                    $isOn = ($fitur === $activeKey);
                    $bCount = $badges[$fitur] ?? 0;
                ?>
                <a href="<?= e("$roleFile?fitur=$fitur") ?>"<?= $isOn ? ' class="on" aria-current="page"' : '' ?>>
                    <span class="sb-link-text"><?= e($label) ?></span>
                    <?php if ($bCount > 0): ?>
                        <span class="sb-badge" title="<?= e($bCount) ?> tugas menunggu"><?= e($bCount) ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </nav>

        <!-- Tombol Keluar di bawah sidebar (seperti referensi gambar) -->
        <div class="sb-footer">
            <form action="logout.php" method="post" class="sb-logout-form">
                <button type="submit" class="sb-logout-btn">&#8594; Keluar</button>
            </form>
        </div>
    </aside>

    <main>
        <div class="page-head">
            <!-- Hamburger button (muncul hanya di mobile) -->
            <button class="sb-toggle" id="sbToggle" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="sidebar">
                <span class="sb-hamburger"></span>
                <span class="sb-hamburger"></span>
                <span class="sb-hamburger"></span>
            </button>
            <div class="page-head-left">
                <h1><?= e($title) ?></h1>
                <span class="mod-pill"><?= e($themeInfo['modul']) ?></span>
            </div>
            <div class="page-head-right">
                <span class="context-label"><?= e($themeInfo['role_short']) ?><?= !empty($u['nim']) ? ' · ' . e($u['nama']) . ' · ' . e($u['nim']) : ' · ' . e($labName) ?></span>
                <?php if ($headerCta): ?>
                    <a class="btn head-cta" href="<?= e($headerCta['url']) ?>"><?= e($headerCta['label']) ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($flash): ?><p class="flash <?= e($flash[1]) ?>" role="status"><?= e($flash[0]) ?></p><?php endif; ?>
<script>
(function(){
    var toggle  = document.getElementById('sbToggle');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sbOverlay');
    var closeBtn= document.getElementById('sbClose');
    function openSidebar(){
        sidebar.classList.add('open');
        overlay.classList.add('show');
        toggle.setAttribute('aria-expanded','true');
        document.body.classList.add('sb-open');
    }
    function closeSidebar(){
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        toggle.setAttribute('aria-expanded','false');
        document.body.classList.remove('sb-open');
    }
    if(toggle)  toggle.addEventListener('click', openSidebar);
    if(overlay) overlay.addEventListener('click', closeSidebar);
    if(closeBtn)closeBtn.addEventListener('click', closeSidebar);
    // Tutup sidebar saat link menu diklik (mobile UX)
    var links = sidebar ? sidebar.querySelectorAll('a') : [];
    links.forEach(function(a){ a.addEventListener('click', closeSidebar); });
})();
</script>
