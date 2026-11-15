<?php
require 'config.php'; guard('tinjau.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $tipe = $_POST['tipe'] ?? ''; $aksi = $_POST['aksi'] ?? '';
    if (!in_array($tipe, ['pengajuan', 'permintaan'], true) || !in_array($aksi, ['setujui', 'tolak'], true)) go('tinjau.php', 'Permintaan tidak valid.', 'bad');
    $d = db(); $n = find_idx($d[$tipe], 'id', post_int('id'));
    if ($n === null || $d[$tipe][$n]['status'] !== 'menunggu') go('tinjau.php', 'Data tidak ditemukan atau sudah ditinjau.', 'bad');
    $d[$tipe][$n]['status'] = $aksi === 'setujui' ? 'disetujui' : 'ditolak';
    $d[$tipe][$n]['catatan'] = trim($_POST['catatan'] ?? '');
    save($d);
    go('tinjau.php', 'Hasil tinjauan disimpan.');
}
$title = 'Tinjau Pengajuan'; $active = 'tinjau.php'; $d = db();
require 'partials/header.php';
?>
<h2>Pengajuan kebutuhan bahan</h2>
<?php req_table(array_reverse($d['pengajuan']), 'pengajuan', true); ?>
<h2>Permintaan barang mahasiswa</h2>
<?php req_table(array_reverse($d['permintaan']), 'permintaan', true); ?>
<?php require 'partials/footer.php'; ?>
