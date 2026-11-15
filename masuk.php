<?php
require 'config.php'; guard('masuk.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah');
    if ($n === null || $jml < 1) go('masuk.php', 'Pilih barang dan isi jumlah lebih dari 0.', 'bad');
    $d['barang'][$n]['stok'] += $jml;
    add_log($d, 'masuk', $d['barang'][$n]['kode'], $jml, trim($_POST['catatan'] ?? ''));
    save($d);
    go('masuk.php', "Stok {$d['barang'][$n]['nama']} bertambah $jml {$d['barang'][$n]['satuan']}.");
}
$title = 'Barang Masuk'; $active = 'masuk.php'; $d = db();
$riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'masuk')), 0, 10);
require 'partials/header.php';
?>
<form class="box form" method="post">
    <?= field_csrf() ?>
    <label for="kode">Barang</label>
    <select id="kode" name="kode" required><option value="">Pilih barang</option>
    <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . '), stok ' . $b['stok'] . ' ' . $b['satuan']) ?></option><?php endforeach; ?>
    </select>
    <label for="jumlah">Jumlah masuk</label><input id="jumlah" name="jumlah" type="number" min="1" required>
    <label for="catatan">Catatan (asal barang, nomor pembelian)</label><textarea id="catatan" name="catatan" rows="2"></textarea>
    <button class="btn" type="submit">Catat barang masuk</button>
</form>
<h2>Riwayat terbaru</h2>
<?php include 'partials/log.php'; ?>
<?php require 'partials/footer.php'; ?>
