<?php
require 'config.php'; guard('keluar.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah');
    $pengaju = trim($_POST['pengaju'] ?? ''); $perlu = trim($_POST['keperluan'] ?? '');
    if ($n === null || $jml < 1 || $pengaju === '' || $perlu === '') go('keluar.php', 'Lengkapi barang, jumlah, pengaju, dan keperluan.', 'bad');
    $b = &$d['barang'][$n];
    if ($jml > $b['stok']) go('keluar.php', "Stok {$b['nama']} tidak cukup. Tersedia {$b['stok']} {$b['satuan']}.", 'bad');
    $b['stok'] -= $jml;
    $habis = low($b); $nama = $b['nama']; unset($b);
    add_log($d, 'keluar', $d['barang'][$n]['kode'], $jml, "$pengaju, $perlu");
    save($d);
    if ($habis) go('keluar.php', "Pengeluaran dicatat. Peringatan: stok $nama kini di bawah minimum.", 'warn');
    go('keluar.php', 'Pengeluaran bahan dicatat.');
}
$title = 'Pengeluaran Bahan'; $active = 'keluar.php'; $d = db();
$riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'keluar')), 0, 10);
require 'partials/header.php';
?>
<form class="box form" method="post">
    <?= field_csrf() ?>
    <label for="kode">Barang</label>
    <select id="kode" name="kode" required><option value="">Pilih barang</option>
    <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . '), stok ' . $b['stok'] . ' ' . $b['satuan']) ?></option><?php endforeach; ?>
    </select>
    <label for="jumlah">Jumlah keluar</label><input id="jumlah" name="jumlah" type="number" min="1" required>
    <label for="pengaju">Pengaju</label><input id="pengaju" name="pengaju" placeholder="Nama dosen atau mahasiswa" required>
    <label for="keperluan">Mata kuliah atau keperluan</label><input id="keperluan" name="keperluan" required>
    <button class="btn" type="submit">Catat pengeluaran</button>
</form>
<h2>Riwayat terbaru</h2>
<?php include 'partials/log.php'; ?>
<?php require 'partials/footer.php'; ?>
