<?php
require 'config.php'; guard('permintaan.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah'); $perlu = trim($_POST['keperluan'] ?? '');
    if ($n === null || $jml < 1 || $perlu === '') go('permintaan.php', 'Pilih barang, isi jumlah, dan keperluan.', 'bad');
    $b = $d['barang'][$n];
    $d['permintaan'][] = ['id' => count($d['permintaan']) + 1, 'tanggal' => date('Y-m-d'), 'oleh' => $_SESSION['user']['nama'], 'oleh_email' => $_SESSION['user']['email'],
        'kode' => $b['kode'], 'nama' => $b['nama'], 'satuan' => $b['satuan'], 'jumlah' => $jml, 'ket' => $perlu, 'status' => 'menunggu', 'catatan' => ''];
    save($d);
    go('permintaan.php', 'Permintaan barang dikirim. Pantau statusnya di bawah.');
}
$title = 'Permintaan Barang'; $active = 'permintaan.php'; $d = db();
$milik = array_reverse(array_filter($d['permintaan'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email']));
require 'partials/header.php';
?>
<form class="box form" method="post">
    <?= field_csrf() ?>
    <label for="kode">Barang</label>
    <select id="kode" name="kode" required><option value="">Pilih barang</option>
    <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' - ' . $b['spesifikasi']) ?></option><?php endforeach; ?>
    </select>
    <label for="jumlah">Jumlah</label><input id="jumlah" name="jumlah" type="number" min="1" required>
    <label for="keperluan">Keperluan (mata kuliah atau tugas)</label><input id="keperluan" name="keperluan" required>
    <button class="btn" type="submit">Kirim permintaan</button>
</form>
<h2>Status permintaan saya</h2>
<?php req_table($milik); ?>
<?php require 'partials/footer.php'; ?>
