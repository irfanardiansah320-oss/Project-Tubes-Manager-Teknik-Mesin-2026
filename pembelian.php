<?php
require 'config.php'; guard('pembelian.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $d = db(); $n = find_idx($d['pengajuan'], 'id', post_int('id'));
    if ($n === null || $d['pengajuan'][$n]['status'] !== 'disetujui' || $d['pengajuan'][$n]['pesan'] < 1) {
        go('pembelian.php', 'Pengajuan belum disetujui atau stok sudah mencukupi.', 'bad');
    }
    $p = $d['pengajuan'][$n];
    $d['pembelian'][] = ['id' => count($d['pembelian']) + 1, 'tanggal' => date('Y-m-d'), 'oleh' => $_SESSION['user']['nama'], 'pengajuan_id' => $p['id'],
        'kode' => $p['kode'], 'nama' => $p['nama'], 'satuan' => $p['satuan'], 'jumlah' => $p['pesan'], 'status' => 'diajukan'];
    $d['pengajuan'][$n]['status'] = 'dipesan';
    save($d);
    go('pembelian.php', "Permintaan pembelian {$p['nama']} sebanyak {$p['pesan']} {$p['satuan']} dibuat.");
}
$title = 'Permintaan Pembelian'; $active = 'pembelian.php'; $d = db();
$siap = array_filter($d['pengajuan'], fn($r) => $r['status'] === 'disetujui');
require 'partials/header.php';
?>
<h2>Pengajuan disetujui, siap dibuatkan pembelian</h2>
<?php if (!$siap): ?><p class="empty">Belum ada pengajuan yang disetujui. Tinjau pengajuan lebih dulu.</p><?php else: ?>
<div class="tablewrap"><table>
<tr><th>No</th><th>Barang</th><th>Kebutuhan</th><th>Perlu dipesan</th><th>Aksi</th></tr>
<?php foreach ($siap as $r): ?>
<tr><td><?= e($r['id']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['jumlah'] . ' ' . $r['satuan']) ?></td><td><?= e($r['pesan'] . ' ' . $r['satuan']) ?></td>
<td><?php if ($r['pesan'] > 0): ?><form class="inline" method="post"><?= field_csrf() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>"><button class="btn">Buat permintaan pembelian</button></form><?php else: ?>Stok sudah cukup<?php endif; ?></td></tr>
<?php endforeach; ?>
</table></div><?php endif; ?>
<h2>Daftar permintaan pembelian</h2>
<?php if (!$d['pembelian']): ?><p class="empty">Belum ada permintaan pembelian.</p><?php else: ?>
<div class="tablewrap"><table>
<tr><th>No</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Dari pengajuan</th><th>Dibuat oleh</th><th>Status</th></tr>
<?php foreach (array_reverse($d['pembelian']) as $b): ?>
<tr><td><?= e($b['id']) ?></td><td><?= e($b['tanggal']) ?></td><td><?= e($b['nama']) ?></td><td><?= e($b['jumlah'] . ' ' . $b['satuan']) ?></td><td>#<?= e($b['pengajuan_id']) ?></td><td><?= e($b['oleh']) ?></td><td><?= chip($b['status']) ?></td></tr>
<?php endforeach; ?>
</table></div><?php endif; ?>
<?php require 'partials/footer.php'; ?>
