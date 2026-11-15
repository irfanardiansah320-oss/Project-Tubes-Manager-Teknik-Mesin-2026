<?php
require 'config.php'; guard('rekap.php');
$title = 'Rekap Kebutuhan dan Pembelian'; $active = 'rekap.php'; $d = db();
$st = $_GET['status'] ?? '';
$rows = array_filter($d['pengajuan'], fn($r) => $st === '' || $r['status'] === $st);
$menunggu = count(array_filter($d['pengajuan'], fn($r) => $r['status'] === 'menunggu'));
require 'partials/header.php';
?>
<div class="cards">
    <div><strong><?= count($d['pengajuan']) ?></strong><span>Total pengajuan</span></div>
    <div><strong><?= $menunggu ?></strong><span>Menunggu tinjauan</span></div>
    <div><strong><?= count($d['pembelian']) ?></strong><span>Permintaan pembelian</span></div>
    <div><strong><?= count(array_filter($d['barang'], 'low')) ?></strong><span>Barang di bawah minimum</span></div>
</div>
<form class="filter" method="get">
    <div><label for="status">Status pengajuan</label>
    <select id="status" name="status"><option value="">Semua</option>
    <?php foreach (['menunggu', 'disetujui', 'ditolak', 'dipesan'] as $s): ?><option value="<?= $s ?>"<?= $st === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
    </select></div>
    <button class="btn" type="submit">Terapkan</button><a class="reset" href="rekap.php">Reset</a>
</form>
<h2>Pengajuan kebutuhan</h2>
<?php req_table(array_reverse($rows)); ?>
<h2>Permintaan pembelian</h2>
<?php if (!$d['pembelian']): ?><p class="empty">Belum ada permintaan pembelian.</p><?php else: ?>
<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Status</th></tr>
<?php foreach (array_reverse($d['pembelian']) as $b): ?>
<tr><td><?= e($b['id']) ?></td><td><?= e($b['tanggal']) ?></td><td><?= e($b['nama']) ?></td><td><?= e($b['jumlah'] . ' ' . $b['satuan']) ?></td><td><?= chip($b['status']) ?></td></tr>
<?php endforeach; ?></table></div><?php endif; ?>
<?php require 'partials/footer.php'; ?>
