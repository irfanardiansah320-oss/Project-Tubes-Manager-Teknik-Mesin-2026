<?php
require 'config.php'; guard('stok.php');
$title = 'Stok Barang'; $active = 'stok.php';
require 'partials/header.php';
?>
<div class="tablewrap"><table>
<tr><th>Kode</th><th>Nama barang</th><th>Spesifikasi</th><th>Rak</th><th>Satuan</th><th>Minimum</th><th>Stok</th><th>Status</th></tr>
<?php foreach (items() as $i): ?>
<tr>
    <td><?= e($i['kode']) ?></td><td><?= e($i['nama']) ?></td><td><?= e($i['spesifikasi']) ?></td>
    <td><?= e($i['rak']) ?></td><td><?= e($i['satuan']) ?></td><td><?= e($i['minimum']) ?></td><td><?= e($i['stok']) ?></td>
    <td><span class="chip <?= low($i) ? 'bad' : 'ok' ?>"><?= low($i) ? 'Di bawah minimum' : 'Aman' ?></span></td>
</tr>
<?php endforeach; ?>
</table></div>
<?php require 'partials/footer.php'; ?>
