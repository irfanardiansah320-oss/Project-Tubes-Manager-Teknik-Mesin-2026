<?php
session_start();
const APP_NAME = 'Inventaris Barang';
const DB_FILE = __DIR__ . '/storage/db.json'; // penyimpanan sementara (JSON), ganti ke MySQL nanti
const ROLES = ['teknisi' => 'Teknisi', 'kalab' => 'Kepala Lab', 'kajur' => 'Kepala Jurusan', 'plp' => 'Pranata Laboratorium Pendidikan', 'mahasiswa' => 'Mahasiswa'];
// file => [judul menu, role yang boleh membuka] (mengikuti use case diagram)
const PAGES = [
    'dashboard.php'  => ['Dashboard', ['teknisi', 'kajur', 'plp']],
    'stok.php'       => ['Stok Barang', ['teknisi', 'kajur', 'plp']],
    'rak.php'        => ['Rak', ['teknisi', 'kajur', 'plp']],
    'peringatan.php' => ['Peringatan Stok', ['teknisi', 'kalab', 'plp']],
    'masuk.php'      => ['Barang Masuk', ['teknisi']],
    'keluar.php'     => ['Pengeluaran Bahan', ['teknisi']],
    'pengajuan.php'  => ['Pengajuan Kebutuhan', ['kalab']],
    'tinjau.php'     => ['Tinjau Pengajuan', ['plp']],
    'pembelian.php'  => ['Permintaan Pembelian', ['plp']],
    'rekap.php'      => ['Rekap', ['kajur', 'plp']],
    'permintaan.php' => ['Permintaan Barang', ['mahasiswa']],
];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function seed() {
    $h = password_hash('password', PASSWORD_DEFAULT);
    $users = [];
    foreach ([['teknisi', 'Teknisi Lab'], ['kalab', 'Kepala Lab'], ['kajur', 'Kepala Jurusan'], ['plp', 'PLP'], ['mahasiswa', 'Mahasiswa Contoh']] as [$r, $n]) {
        $users[] = ['email' => "$r@polban.ac.id", 'nama' => $n, 'role' => $r, 'hash' => $h];
    }
    save([
        'users' => $users,
        'barang' => require __DIR__ . '/data.php',
        'pengajuan' => [['id' => 1, 'tanggal' => date('Y-m-d'), 'oleh' => 'Kepala Lab', 'oleh_email' => 'kalab@polban.ac.id', 'kode' => 'MPK-0012', 'nama' => 'Baja pejal', 'satuan' => 'cm', 'jumlah' => 660, 'stok' => 10, 'pesan' => 650, 'ket' => 'Praktik Pemesinan, 30 mahasiswa × 20 cm, toleransi 10%, stok saat ini 10, perlu dipesan 650', 'status' => 'menunggu', 'catatan' => '']],
        'permintaan' => [['id' => 1, 'tanggal' => date('Y-m-d'), 'oleh' => 'Mahasiswa Contoh', 'oleh_email' => 'mahasiswa@polban.ac.id', 'kode' => 'MPK-0018', 'nama' => 'Kuningan batangan', 'satuan' => 'cm', 'jumlah' => 40, 'ket' => 'Tugas praktik fabrikasi', 'status' => 'menunggu', 'catatan' => '']],
        'pembelian' => [], 'log' => [],
    ]);
}
function db() { if (!is_file(DB_FILE)) seed(); return json_decode(file_get_contents(DB_FILE), true); }
function save($d) {
    if (!is_dir(dirname(DB_FILE))) mkdir(dirname(DB_FILE), 0777, true);
    file_put_contents(DB_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
function items() { return db()['barang']; }
function low($i) { return $i['stok'] < $i['minimum']; }
function stok_pct($i) { return $i['minimum'] > 0 ? min(100, round($i['stok'] / $i['minimum'] * 100)) : 100; }
function find_idx($list, $key, $val) { foreach ($list as $n => $r) { if ($r[$key] == $val) return $n; } return null; }
function post_int($k) { return filter_var($_POST[$k] ?? '', FILTER_VALIDATE_INT) ?: 0; }
function kebutuhan($mhs, $per, $tol, $stok) {
    $total = (int)ceil(round($mhs * $per * (1 + $tol / 100), 6));
    return [$total, max(0, $total - $stok)];
}
function add_log(&$d, $jenis, $kode, $jumlah, $catatan) {
    $d['log'][] = ['waktu' => date('Y-m-d H:i'), 'oleh' => $_SESSION['user']['nama'], 'jenis' => $jenis, 'kode' => $kode, 'jumlah' => $jumlah, 'catatan' => $catatan];
}

// Akses: dijaga di server, bukan hanya dengan menyembunyikan menu
function guard($file) {
    if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
    if (!in_array($_SESSION['user']['role'], PAGES[$file][1], true)) {
        http_response_code(403);
        exit('403 - Anda tidak berwenang membuka halaman ini.');
    }
}
function home_for($role) { foreach (PAGES as $f => $p) { if (in_array($role, $p[1], true)) return $f; } return 'login.php'; }
function go($file, $msg = null, $type = 'ok') {
    if ($msg !== null) $_SESSION['flash'] = [$msg, $type];
    header("Location: $file"); exit;
}
function flash() { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function csrf() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function field_csrf() { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function check_csrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Sesi tidak valid. Muat ulang halaman.'); }
}
function chip($s) {
    $c = ['menunggu' => 'warn', 'ditolak' => 'bad'][$s] ?? 'ok';
    return '<span class="chip ' . $c . '">' . e(ucfirst($s)) . '</span>';
}
function req_table($rows, $tipe = '', $aksi = false) {
    if (!$rows) { echo '<p class="empty">Belum ada data.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Pemohon</th><th>Barang</th><th>Jumlah</th><th>Keterangan</th><th>Status</th>' . ($aksi ? '<th>Tinjau</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['nama']) . '</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td>' . e($r['ket']) . ($r['catatan'] !== '' ? '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>' : '') . '</td><td>' . chip($r['status']) . '</td>';
        if ($aksi) {
            echo '<td>';
            if ($r['status'] === 'menunggu') {
                echo '<form class="inline" method="post">' . field_csrf() . '<input type="hidden" name="tipe" value="' . e($tipe) . '"><input type="hidden" name="id" value="' . e($r['id']) . '"><input name="catatan" placeholder="Catatan" aria-label="Catatan"><button class="btn" name="aksi" value="setujui">Setujui</button><button class="btn sec" name="aksi" value="tolak">Tolak</button></form>';
            } else { echo '-'; }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
}
