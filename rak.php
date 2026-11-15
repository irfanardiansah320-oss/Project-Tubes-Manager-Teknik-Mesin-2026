<?php
require 'config.php'; guard('rak.php');
$byRak = [];
foreach (items() as $i) { $byRak[$i['rak']][] = $i; }
ksort($byRak);
$title = 'Rak'; $active = 'rak.php';
require 'partials/header.php';
?>
<?php foreach ($byRak as $rak => $rows): ?>
<section class="rak">
    <h2>Rak <?= e($rak) ?> <small><?= count($rows) ?> jenis barang</small></h2>
    <ul><?php foreach ($rows as $i): ?><li><?= e($i['nama']) ?> <span class="muted">(<?= e($i['kode']) ?>)</span></li><?php endforeach; ?></ul>
</section>
<?php endforeach; ?>
<?php require 'partials/footer.php'; ?>
