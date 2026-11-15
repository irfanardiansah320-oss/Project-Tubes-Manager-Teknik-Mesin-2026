<?php
require 'config.php'; guard('pengajuan.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
    $mk = trim($_POST['mata_kuliah'] ?? ''); $mhs = post_int('mahasiswa'); $per = post_int('per_mhs'); $tol = post_int('tol');
    if ($n === null || $mk === '' || $mhs < 1 || $per < 1 || $tol < 0 || $tol > 100) go('pengajuan.php', 'Lengkapi data. Toleransi harus 0 sampai 100.', 'bad');
    $b = $d['barang'][$n];
    [$total, $pesan] = kebutuhan($mhs, $per, $tol, $b['stok']);
    $d['pengajuan'][] = ['id' => count($d['pengajuan']) + 1, 'tanggal' => date('Y-m-d'), 'oleh' => $_SESSION['user']['nama'], 'oleh_email' => $_SESSION['user']['email'],
        'kode' => $b['kode'], 'nama' => $b['nama'], 'satuan' => $b['satuan'], 'jumlah' => $total, 'stok' => $b['stok'], 'pesan' => $pesan,
        'ket' => "$mk, $mhs mahasiswa × $per {$b['satuan']}, toleransi $tol%, stok saat ini {$b['stok']}, perlu dipesan $pesan", 'status' => 'menunggu', 'catatan' => ''];
    save($d);
    go('pengajuan.php', "Pengajuan dikirim. Kebutuhan $total {$b['satuan']}, perlu dipesan $pesan.");
}
$title = 'Pengajuan Kebutuhan'; $active = 'pengajuan.php'; $d = db();
$milik = array_reverse(array_filter($d['pengajuan'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email']));
require 'partials/header.php';
?>
<form class="box form" method="post">
    <?= field_csrf() ?>
    <label for="mata_kuliah">Mata kuliah</label><input id="mata_kuliah" name="mata_kuliah" required>
    <label for="kode">Barang</label>
    <select id="kode" name="kode" required><option value="">Pilih barang</option>
    <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>" data-stok="<?= e($b['stok']) ?>" data-satuan="<?= e($b['satuan']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . ')') ?></option><?php endforeach; ?>
    </select>
    <div class="grid3">
        <div><label for="mahasiswa">Jumlah mahasiswa</label><input id="mahasiswa" name="mahasiswa" type="number" min="1" required></div>
        <div><label for="per_mhs">Kebutuhan per mahasiswa</label><input id="per_mhs" name="per_mhs" type="number" min="1" required></div>
        <div><label for="tol">Toleransi gagal (%)</label><input id="tol" name="tol" type="number" min="0" max="100" value="10"></div>
    </div>
    <p id="hasil" class="calc" aria-live="polite"></p>
    <button class="btn" type="submit">Kirim pengajuan</button>
</form>
<h2>Pengajuan saya</h2>
<?php req_table($milik); ?>
<script>
// Pratinjau saja. Perhitungan resmi dilakukan server saat pengajuan dikirim.
const f = id => document.getElementById(id);
function hitung() {
    const o = f('kode').selectedOptions[0], m = +f('mahasiswa').value, p = +f('per_mhs').value, t = +f('tol').value || 0;
    if (!o || !o.value || m < 1 || p < 1) { f('hasil').textContent = 'Isi barang, jumlah mahasiswa, dan kebutuhan per mahasiswa untuk melihat perhitungan.'; return; }
    const stok = +o.dataset.stok, total = Math.ceil(Math.round(m * p * (1 + t / 100) * 1e6) / 1e6), pesan = Math.max(0, total - stok);
    f('hasil').textContent = 'Kebutuhan ' + total + ' ' + o.dataset.satuan + ' (toleransi ' + t + '%). Stok ' + stok + '. Perlu dipesan ' + pesan + '.';
}
['kode', 'mahasiswa', 'per_mhs', 'tol'].forEach(i => f(i).addEventListener('input', hitung));
hitung();
</script>
<?php require 'partials/footer.php'; ?>
