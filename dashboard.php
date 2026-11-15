<?php
require 'config.php'; guard('dashboard.php');
$all = items();
$q = strtolower(trim($_GET['q'] ?? ''));
$rak = $_GET['rak'] ?? '';
$st = $_GET['status'] ?? '';
$raks = array_values(array_unique(array_column($all, 'rak'))); sort($raks);
$list = array_filter($all, function ($i) use ($q, $rak, $st) {
    if ($q !== '' && !str_contains(strtolower($i['nama'] . ' ' . $i['kode']), $q)) return false;
    if ($rak !== '' && $i['rak'] !== $rak) return false;
    if ($st === 'low' && !low($i)) return false;
    if ($st === 'ok' && low($i)) return false;
    return true;
});
$lowCount = count(array_filter($all, 'low'));
$title = 'Dashboard'; $active = 'dashboard.php';
require 'partials/header.php';
?>
<p class="sum"><?= count($all) ?> jenis barang, <strong><?= $lowCount ?></strong> di bawah minimum.</p>
<form class="filter" method="get">
    <div><label for="q">Cari barang</label><input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Nama atau kode"></div>
    <div><label for="rak">Rak</label>
        <select id="rak" name="rak"><option value="">Semua rak</option>
        <?php foreach ($raks as $r): ?><option value="<?= e($r) ?>"<?= $r === $rak ? ' selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
        </select></div>
    <div><label for="status">Status stok</label>
        <select id="status" name="status">
            <option value="">Semua</option>
            <option value="low"<?= $st === 'low' ? ' selected' : '' ?>>Di bawah minimum</option>
            <option value="ok"<?= $st === 'ok' ? ' selected' : '' ?>>Aman</option>
        </select></div>
    <button class="btn" type="submit">Terapkan</button>
    <a class="reset" href="dashboard.php">Reset</a>
</form>
<?php if (!$list): ?><p class="empty">Tidak ada barang yang cocok. Ubah atau reset filter.</p><?php endif; ?>
<?php foreach ($list as $i): $isLow = low($i); $foto = 'assets/img/' . $i['foto']; ?>
<article class="item<?= $isLow ? ' low' : '' ?>">
    <?php if ($i['foto'] && file_exists(__DIR__ . '/' . $foto)): ?>
        <img class="foto" src="<?= e($foto) ?>" alt="Foto <?= e($i['nama']) ?>">
    <?php else: ?><div class="foto">Foto belum ada</div><?php endif; ?>
    <dl class="info">
        <div class="wide"><dt>Nama barang</dt><dd class="nm"><?= e($i['nama']) ?></dd></div>
        <div><dt>Kode</dt><dd><?= e($i['kode']) ?></dd></div>
        <div class="wide"><dt>Spesifikasi</dt><dd><?= e($i['spesifikasi']) ?></dd></div>
        <div><dt>Rak</dt><dd><?= e($i['rak']) ?></dd></div>
        <div><dt>Satuan</dt><dd><?= e($i['satuan']) ?></dd></div>
        <div><dt>Minimum</dt><dd><?= e($i['minimum']) ?></dd></div>
    </dl>
    <div class="stok">
        <span>Stok</span><strong><?= e($i['stok']) ?></strong><span><?= e($i['satuan']) ?></span>
        <div class="bar" aria-hidden="true"><i style="width:<?= stok_pct($i) ?>%"></i></div>
        <span class="chip <?= $isLow ? 'bad' : 'ok' ?>"><?= $isLow ? 'Di bawah minimum' : 'Aman' ?></span>
    </div>
</article>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
