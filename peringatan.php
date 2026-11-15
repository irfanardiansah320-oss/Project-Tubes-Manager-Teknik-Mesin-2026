<?php
require 'config.php'; guard('peringatan.php');
$title = 'Peringatan Stok'; $active = 'peringatan.php';
$rows = array_filter(items(), 'low');
require 'partials/header.php';
?>
<?php if (!$rows): ?><p class="empty">Semua stok masih di atas batas minimum.</p><?php else: ?>
<div class="tablewrap"><table>
<tr><th>Kode</th><th>Barang</th><th>Rak</th><th>Stok</th><th>Minimum</th><th>Kekurangan</th></tr>
<?php foreach ($rows as $i): ?>
<tr><td><?= e($i['kode']) ?></td><td><?= e($i['nama']) ?></td><td><?= e($i['rak']) ?></td><td><?= e($i['stok'] . ' ' . $i['satuan']) ?></td><td><?= e($i['minimum']) ?></td><td><span class="chip bad"><?= e($i['minimum'] - $i['stok']) ?> <?= e($i['satuan']) ?></span></td></tr>
<?php endforeach; ?>
</table></div><?php endif; ?>
<?php require 'partials/footer.php'; ?>
