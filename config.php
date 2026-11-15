<?php
session_start();
const APP_NAME = 'Inventaris Barang';
const DB_FILE = __DIR__ . '/storage/db.json'; // penyimpanan sementara (JSON), ganti ke MySQL nanti

const ROLES = [
    'kalab'     => 'Kepala Lab',
    'kajur'     => 'Ketua Jurusan',
    'teknisi'   => 'Teknisi',
    'plp'       => 'Pranata Laboratorium Pendidikan',
    'dosen'     => 'Dosen',
    'mhs'       => 'Mahasiswa',
    'mahasiswa' => 'Mahasiswa',
];

const ROLE_FILES = [
    'kalab'     => 'kalab.php',
    'kajur'     => 'kajur.php',
    'teknisi'   => 'teknisi.php',
    'plp'       => 'plp.php',
    'dosen'     => 'dosen.php',
    'mhs'       => 'mhs.php',
    'mahasiswa' => 'mhs.php',
];

// Menu per role (fitur => label menu)
const ROLE_MENUS = [
    'kalab' => [
        'peringatan' => 'Peringatan Stok',
        'pengajuan'  => 'Pengajuan Kebutuhan',
    ],
    'kajur' => [
        'dashboard'  => 'Dashboard',
        'stok'       => 'Stok Barang',
        'rak'        => 'Rak',
        'rekap'      => 'Rekap',
    ],
    'teknisi' => [
        'dashboard'  => 'Dashboard',
        'stok'       => 'Stok Barang',
        'rak'        => 'Rak',
        'peringatan' => 'Peringatan Stok',
        'masuk'      => 'Barang Masuk',
        'keluar'     => 'Pengeluaran Bahan',
        'tinjau'     => 'Konfirmasi Praktikum Mhs',
    ],
    'plp' => [
        'dashboard'  => 'Dashboard',
        'stok'       => 'Stok Barang',
        'rak'        => 'Rak',
        'peringatan' => 'Peringatan Stok',
        'tinjau'     => 'Tinjau Pengajuan',
        'pembelian'  => 'Permintaan Pembelian',
        'rekap'      => 'Rekap',
    ],
    'dosen' => [
        'pengajuan_dosen' => 'Pengajuan Bahan Semester',
    ],
    'mhs' => [
        'permintaan' => 'Konfirmasi & Pengambilan Praktikum',
    ],
];

// Pemetaan file fitur langsung (kompatibilitas akses langsung)
const PAGES = [
    'dashboard.php'  => ['Dashboard', ['teknisi', 'kajur', 'plp']],
    'stok.php'       => ['Stok Barang', ['teknisi', 'kajur', 'plp']],
    'rak.php'        => ['Rak', ['teknisi', 'kajur', 'plp']],
    'peringatan.php' => ['Peringatan Stok', ['teknisi', 'kalab', 'plp']],
    'masuk.php'      => ['Barang Masuk', ['teknisi']],
    'keluar.php'     => ['Pengeluaran Bahan', ['teknisi']],
    'pengajuan.php'  => ['Pengajuan Kebutuhan', ['kalab', 'dosen']],
    'tinjau.php'     => ['Tinjau Pengajuan', ['plp', 'teknisi']],
    'pembelian.php'  => ['Permintaan Pembelian', ['plp']],
    'rekap.php'      => ['Rekap', ['kajur', 'plp']],
    'permintaan.php' => ['Konfirmasi & Pengambilan Praktikum', ['mhs', 'mahasiswa']],
];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function norm_role($r) {
    return $r === 'mahasiswa' ? 'mhs' : $r;
}

function seed() {
    $h = password_hash('password', PASSWORD_DEFAULT);
    $users = [];
    foreach ([
        ['kalab', 'Kepala Lab'],
        ['kajur', 'Ketua Jurusan'],
        ['teknisi', 'Teknisi Lab'],
        ['plp', 'PLP'],
        ['dosen', 'Dosen Pengampu'],
        ['mhs', 'Mahasiswa Contoh'],
        ['mahasiswa', 'Mahasiswa Contoh'],
    ] as [$r, $n]) {
        $users[] = ['email' => "$r@polban.ac.id", 'nama' => $n, 'role' => norm_role($r), 'hash' => $h];
    }
    save([
        'users' => $users,
        'barang' => require __DIR__ . '/data.php',
        'pengajuan' => [
            [
                'id' => 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'Kepala Lab',
                'oleh_email' => 'kalab@polban.ac.id',
                'kode' => 'MPK-0012',
                'nama' => 'Baja pejal',
                'satuan' => 'cm',
                'jumlah' => 660,
                'stok' => 10,
                'pesan' => 650,
                'ket' => 'Praktik Pemesinan, 30 mahasiswa × 20 cm, toleransi 10%, stok saat ini 10, perlu dipesan 650',
                'status' => 'menunggu',
                'catatan' => '',
            ],
        ],
        'pengajuan_dosen' => [
            [
                'id' => 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'Dosen Pengampu',
                'oleh_email' => 'dosen@polban.ac.id',
                'kode' => 'MPK-0018',
                'nama' => 'Kuningan batangan',
                'satuan' => 'cm',
                'jumlah' => 240,
                'kelas' => '2A - D3 Teknik Mesin',
                'sub_praktikum' => 'Bubut Bertingkat',
                'ket' => 'Kelas: 2A - D3 Teknik Mesin | Sub Praktikum: Bubut Bertingkat (Kebutuhan 1 Semester)',
                'status' => 'disetujui',
                'catatan' => 'Stok tersedia untuk 1 semester',
            ],
        ],
        'permintaan' => [
            [
                'id' => 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'Mahasiswa Contoh',
                'oleh_email' => 'mhs@polban.ac.id',
                'pengajuan_dosen_id' => 1,
                'kode' => 'MPK-0018',
                'nama' => 'Kuningan batangan',
                'satuan' => 'cm',
                'jumlah' => 20,
                'kelas' => '2A - D3 Teknik Mesin',
                'sub_praktikum' => 'Bubut Bertingkat',
                'ket' => 'Konfirmasi Praktikum: Bubut Bertingkat | Kelas: 2A - D3 Teknik Mesin',
                'status' => 'menunggu',
                'disetujui_oleh' => '',
                'catatan' => '',
            ],
        ],
        'pembelian' => [],
        'log' => [],
    ]);
}

function db() {
    if (!is_file(DB_FILE)) seed();
    $d = json_decode(file_get_contents(DB_FILE), true);
    if (!isset($d['pengajuan_dosen'])) $d['pengajuan_dosen'] = [];
    if (!isset($d['permintaan'])) $d['permintaan'] = [];
    if (!isset($d['pengajuan'])) $d['pengajuan'] = [];
    if (!isset($d['pembelian'])) $d['pembelian'] = [];
    if (!isset($d['log'])) $d['log'] = [];
    return $d;
}

function save($d) {
    if (!is_dir(dirname(DB_FILE))) mkdir(dirname(DB_FILE), 0777, true);
    file_put_contents(DB_FILE, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function items() { return db()['barang']; }
function low($i) { return $i['stok'] < $i['minimum']; }
function stok_pct($i) { return $i['minimum'] > 0 ? min(100, round($i['stok'] / $i['minimum'] * 100)) : 100; }
function find_idx($list, $key, $val) { foreach ($list as $n => $r) { if (($r[$key] ?? null) == $val) return $n; } return null; }
function post_int($k) { return filter_var($_POST[$k] ?? '', FILTER_VALIDATE_INT) ?: 0; }
function kebutuhan($mhs, $per, $tol, $stok) {
    $total = (int)ceil(round($mhs * $per * (1 + $tol / 100), 6));
    return [$total, max(0, $total - $stok)];
}
function add_log(&$d, $jenis, $kode, $jumlah, $catatan) {
    $d['log'][] = ['waktu' => date('Y-m-d H:i'), 'oleh' => $_SESSION['user']['nama'], 'jenis' => $jenis, 'kode' => $kode, 'jumlah' => $jumlah, 'catatan' => $catatan];
}

// Guard berdasarkan file fitur atau role
function guard($file) {
    if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
    $role = norm_role($_SESSION['user']['role']);
    $allowed = array_map('norm_role', PAGES[$file][1] ?? []);
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('403 - Anda tidak berwenang membuka halaman ini.');
    }
}

function guard_role($expectedRole) {
    if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
    $userRole = norm_role($_SESSION['user']['role']);
    $expected = norm_role($expectedRole);
    if ($userRole !== $expected) {
        http_response_code(403);
        exit('403 - Anda tidak berwenang membuka halaman role ini.');
    }
}

function home_for($role) {
    $r = norm_role($role);
    return ROLE_FILES[$r] ?? 'login.php';
}

function role_url($fitur = '') {
    $role = norm_role($_SESSION['user']['role'] ?? '');
    $file = ROLE_FILES[$role] ?? 'index.php';
    return $fitur !== '' ? "$file?fitur=" . urlencode($fitur) : $file;
}

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
        $extra = '';
        if (!empty($r['disetujui_oleh'])) {
            $extra .= '<br><small class="muted">Ditinjau oleh: ' . e($r['disetujui_oleh']) . '</small>';
        }
        if (($r['catatan'] ?? '') !== '') {
            $extra .= '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>';
        }
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['nama']) . '</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td>' . e($r['ket']) . $extra . '</td><td>' . chip($r['status']) . '</td>';
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

function dosen_table($rows, $aksi = false) {
    if (!$rows) { echo '<p class="empty">Belum ada pengajuan bahan semester dari dosen.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Dosen</th><th>Nama Bahan</th><th>Jumlah (1 Smt)</th><th>Kelas</th><th>Sub Praktikum</th><th>Status</th>' . ($aksi ? '<th>Tinjau (PLP)</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        $cat = ($r['catatan'] ?? '') !== '' ? '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>' : '';
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['nama']) . ' (' . e($r['kode']) . ')</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td>' . e($r['kelas'] ?? '-') . '</td><td>' . e($r['sub_praktikum'] ?? '-') . $cat . '</td><td>' . chip($r['status']) . '</td>';
        if ($aksi) {
            echo '<td>';
            if ($r['status'] === 'menunggu') {
                echo '<form class="inline" method="post">' . field_csrf() . '<input type="hidden" name="tipe" value="pengajuan_dosen"><input type="hidden" name="id" value="' . e($r['id']) . '"><input name="catatan" placeholder="Catatan PLP" aria-label="Catatan"><button class="btn" name="aksi" value="setujui">Setujui</button><button class="btn sec" name="aksi" value="tolak">Tolak</button></form>';
            } else { echo '-'; }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
}

function mhs_table($rows, $aksi = false) {
    if (!$rows) { echo '<p class="empty">Belum ada konfirmasi praktikum & pengambilan bahan dari mahasiswa.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tgl Praktikum</th><th>Mahasiswa</th><th>Kelas</th><th>Sub Praktikum</th><th>Bahan Diambil</th><th>Jml Pengambilan</th><th>Status</th>' . ($aksi ? '<th>Tinjau (Teknisi/PLP)</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        $extra = '';
        if (!empty($r['disetujui_oleh'])) {
            $extra .= '<br><small class="muted">Oleh: ' . e($r['disetujui_oleh']) . '</small>';
        }
        if (($r['catatan'] ?? '') !== '') {
            $extra .= '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>';
        }
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['kelas'] ?? '-') . '</td><td>' . e($r['sub_praktikum'] ?? $r['ket'] ?? '-') . $extra . '</td><td>' . e($r['nama']) . '</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td>' . chip($r['status']) . '</td>';
        if ($aksi) {
            echo '<td>';
            if ($r['status'] === 'menunggu') {
                echo '<form class="inline" method="post">' . field_csrf() . '<input type="hidden" name="tipe" value="permintaan"><input type="hidden" name="id" value="' . e($r['id']) . '"><input name="catatan" placeholder="Catatan" aria-label="Catatan"><button class="btn" name="aksi" value="setujui">Setujui</button><button class="btn sec" name="aksi" value="tolak">Tolak</button></form>';
            } else { echo '-'; }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
}

function render_rekap_kehadiran_praktikum($permintaan) {
    if (!$permintaan) return;
    $rekap = [];
    foreach ($permintaan as $r) {
        if ($r['status'] === 'ditolak') continue;
        $key = ($r['tanggal'] ?? '') . '|' . ($r['kelas'] ?? '-') . '|' . ($r['sub_praktikum'] ?? '-');
        if (!isset($rekap[$key])) {
            $rekap[$key] = [
                'tanggal' => $r['tanggal'] ?? '-',
                'kelas' => $r['kelas'] ?? '-',
                'sub_praktikum' => $r['sub_praktikum'] ?? ($r['ket'] ?? '-'),
                'bahan' => $r['nama'] ?? '-',
                'total_mhs' => 0,
                'disetujui_mhs' => 0,
                'total_ambil' => 0,
                'satuan' => $r['satuan'] ?? '',
            ];
        }
        $rekap[$key]['total_mhs'] += 1;
        if ($r['status'] === 'disetujui') {
            $rekap[$key]['disetujui_mhs'] += 1;
        }
        $rekap[$key]['total_ambil'] += (int)($r['jumlah'] ?? 0);
    }
    if (!$rekap) return;
    echo '<h2>Rekap Jumlah Mahasiswa Praktikum per Hari</h2>';
    echo '<div class="tablewrap"><table><tr><th>Tanggal Praktikum</th><th>Kelas</th><th>Sub Praktikum</th><th>Bahan</th><th>Total Pengambilan</th><th>Mahasiswa Mengonfirmasi</th><th>Mahasiswa Disetujui</th></tr>';
    foreach (array_reverse($rekap) as $rk) {
        echo '<tr><td>' . e($rk['tanggal']) . '</td><td>' . e($rk['kelas']) . '</td><td>' . e($rk['sub_praktikum']) . '</td><td>' . e($rk['bahan']) . '</td><td>' . e($rk['total_ambil'] . ' ' . $rk['satuan']) . '</td><td><strong>' . e($rk['total_mhs']) . ' mahasiswa</strong></td><td><span class="chip ok">' . e($rk['disetujui_mhs']) . ' mahasiswa</span></td></tr>';
    }
    echo '</table></div>';
}

/* ==========================================================================
   FUNGSI-FUNGSI FITUR UTAMA (Dipanggil oleh masing-masing file role .php)
   ========================================================================== */

function fitur_dashboard() {
    $all = items();
    $q = strtolower(trim($_GET['q'] ?? ''));
    $rak = $_GET['rak'] ?? '';
    $st = $_GET['status'] ?? '';
    $raks = array_values(array_unique(array_column($all, 'rak'))); sort($raks);
    $list = array_filter($all, function ($i) use ($q, $rak, $st) {
        if ($q !== '' && !str_contains(strtolower($i['nama'] . ' ' . $i['kode']), $q)) return false;
        if ($rak !== '' && $i['rak'] !== $rak) return false;
        if ($st === 'low' && !low($i)) return false;
        if ($st === 'ok' && low($i)) return false;
        return true;
    });
    $lowCount = count(array_filter($all, 'low'));
    $title = 'Dashboard'; $active = 'dashboard';
    $resetUrl = role_url('dashboard');
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum"><?= count($all) ?> jenis barang, <strong><?= $lowCount ?></strong> di bawah minimum.</p>
    <form class="filter" method="get">
        <input type="hidden" name="fitur" value="dashboard">
        <div><label for="q">Cari barang</label><input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Nama atau kode"></div>
        <div><label for="rak">Rak</label>
            <select id="rak" name="rak"><option value="">Semua rak</option>
            <?php foreach ($raks as $r): ?><option value="<?= e($r) ?>"<?= $r === $rak ? ' selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
            </select></div>
        <div><label for="status">Status stok</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <option value="low"<?= $st === 'low' ? ' selected' : '' ?>>Di bawah minimum</option>
                <option value="ok"<?= $st === 'ok' ? ' selected' : '' ?>>Aman</option>
            </select></div>
        <button class="btn" type="submit">Terapkan</button>
        <a class="reset" href="<?= e($resetUrl) ?>">Reset</a>
    </form>
    <?php if (!$list): ?><p class="empty">Tidak ada barang yang cocok. Ubah atau reset filter.</p><?php endif; ?>
    <?php foreach ($list as $i): $isLow = low($i); $foto = 'assets/img/' . $i['foto']; ?>
    <article class="item<?= $isLow ? ' low' : '' ?>">
        <?php if ($i['foto'] && file_exists(__DIR__ . '/' . $foto)): ?>
            <img class="foto" src="<?= e($foto) ?>" alt="Foto <?= e($i['nama']) ?>">
        <?php else: ?><div class="foto">Foto belum ada</div><?php endif; ?>
        <dl class="info">
            <div class="wide"><dt>Nama barang</dt><dd class="nm"><?= e($i['nama']) ?></dd></div>
            <div><dt>Kode</dt><dd><?= e($i['kode']) ?></dd></div>
            <div class="wide"><dt>Spesifikasi</dt><dd><?= e($i['spesifikasi']) ?></dd></div>
            <div><dt>Rak</dt><dd><?= e($i['rak']) ?></dd></div>
            <div><dt>Satuan</dt><dd><?= e($i['satuan']) ?></dd></div>
            <div><dt>Minimum</dt><dd><?= e($i['minimum']) ?></dd></div>
        </dl>
        <div class="stok">
            <span>Stok</span><strong><?= e($i['stok']) ?></strong><span><?= e($i['satuan']) ?></span>
            <div class="bar" aria-hidden="true"><i style="width:<?= stok_pct($i) ?>%"></i></div>
            <span class="chip <?= $isLow ? 'bad' : 'ok' ?>"><?= $isLow ? 'Di bawah minimum' : 'Aman' ?></span>
        </div>
    </article>
    <?php endforeach;
    require __DIR__ . '/partials/footer.php';
}

function fitur_stok() {
    $title = 'Stok Barang'; $active = 'stok';
    require __DIR__ . '/partials/header.php';
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
    <?php
    require __DIR__ . '/partials/footer.php';
}

function fitur_rak() {
    $byRak = [];
    foreach (items() as $i) { $byRak[$i['rak']][] = $i; }
    ksort($byRak);
    $title = 'Rak'; $active = 'rak';
    require __DIR__ . '/partials/header.php';
    foreach ($byRak as $rak => $rows): ?>
    <section class="rak">
        <h2>Rak <?= e($rak) ?> <small><?= count($rows) ?> jenis barang</small></h2>
        <ul><?php foreach ($rows as $i): ?><li><?= e($i['nama']) ?> <span class="muted">(<?= e($i['kode']) ?>)</span></li><?php endforeach; ?></ul>
    </section>
    <?php endforeach;
    require __DIR__ . '/partials/footer.php';
}

function fitur_peringatan() {
    $title = 'Peringatan Stok'; $active = 'peringatan';
    $rows = array_filter(items(), 'low');
    require __DIR__ . '/partials/header.php';
    if (!$rows): ?><p class="empty">Semua stok masih di atas batas minimum.</p><?php else: ?>
    <div class="tablewrap"><table>
    <tr><th>Kode</th><th>Barang</th><th>Rak</th><th>Stok</th><th>Minimum</th><th>Kekurangan</th></tr>
    <?php foreach ($rows as $i): ?>
    <tr><td><?= e($i['kode']) ?></td><td><?= e($i['nama']) ?></td><td><?= e($i['rak']) ?></td><td><?= e($i['stok'] . ' ' . $i['satuan']) ?></td><td><?= e($i['minimum']) ?></td><td><span class="chip bad"><?= e($i['minimum'] - $i['stok']) ?> <?= e($i['satuan']) ?></span></td></tr>
    <?php endforeach; ?>
    </table></div><?php endif;
    require __DIR__ . '/partials/footer.php';
}

function fitur_masuk() {
    $redirect = role_url('masuk');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah');
        if ($n === null || $jml < 1) go($redirect, 'Pilih barang dan isi jumlah lebih dari 0.', 'bad');
        $d['barang'][$n]['stok'] += $jml;
        add_log($d, 'masuk', $d['barang'][$n]['kode'], $jml, trim($_POST['catatan'] ?? ''));
        save($d);
        go($redirect, "Stok {$d['barang'][$n]['nama']} bertambah $jml {$d['barang'][$n]['satuan']}.");
    }
    $title = 'Barang Masuk'; $active = 'masuk'; $d = db();
    $riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'masuk')), 0, 10);
    require __DIR__ . '/partials/header.php';
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
    <?php include __DIR__ . '/partials/log.php';
    require __DIR__ . '/partials/footer.php';
}

function fitur_keluar() {
    $redirect = role_url('keluar');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah');
        $pengaju = trim($_POST['pengaju'] ?? ''); $perlu = trim($_POST['keperluan'] ?? '');
        if ($n === null || $jml < 1 || $pengaju === '' || $perlu === '') go($redirect, 'Lengkapi barang, jumlah, pengaju, dan keperluan.', 'bad');
        $b = &$d['barang'][$n];
        if ($jml > $b['stok']) go($redirect, "Stok {$b['nama']} tidak cukup. Tersedia {$b['stok']} {$b['satuan']}.", 'bad');
        $b['stok'] -= $jml;
        $habis = low($b); $nama = $b['nama']; unset($b);
        add_log($d, 'keluar', $d['barang'][$n]['kode'], $jml, "$pengaju, $perlu");
        save($d);
        if ($habis) go($redirect, "Pengeluaran dicatat. Peringatan: stok $nama kini di bawah minimum.", 'warn');
        go($redirect, 'Pengeluaran bahan dicatat.');
    }
    $title = 'Pengeluaran Bahan'; $active = 'keluar'; $d = db();
    $riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'keluar')), 0, 10);
    require __DIR__ . '/partials/header.php';
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
    <?php include __DIR__ . '/partials/log.php';
    require __DIR__ . '/partials/footer.php';
}

function fitur_pengajuan() {
    $redirect = role_url('pengajuan');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $mk = trim($_POST['mata_kuliah'] ?? ''); $mhs = post_int('mahasiswa'); $per = post_int('per_mhs'); $tol = post_int('tol');
        if ($n === null || $mk === '' || $mhs < 1 || $per < 1 || $tol < 0 || $tol > 100) go($redirect, 'Lengkapi data. Toleransi harus 0 sampai 100.', 'bad');
        $b = $d['barang'][$n];
        [$total, $pesan] = kebutuhan($mhs, $per, $tol, $b['stok']);
        $d['pengajuan'][] = [
            'id' => count($d['pengajuan']) + 1,
            'tanggal' => date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'jumlah' => $total,
            'stok' => $b['stok'],
            'pesan' => $pesan,
            'ket' => "$mk, $mhs mahasiswa × $per {$b['satuan']}, toleransi $tol%, stok saat ini {$b['stok']}, perlu dipesan $pesan",
            'status' => 'menunggu',
            'catatan' => '',
        ];
        save($d);
        go($redirect, "Pengajuan dikirim. Kebutuhan $total {$b['satuan']}, perlu dipesan $pesan.");
    }
    $title = 'Pengajuan Kebutuhan'; $active = 'pengajuan'; $d = db();
    $milik = array_reverse(array_filter($d['pengajuan'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email']));
    require __DIR__ . '/partials/header.php';
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
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Role Dosen: Mengajukan bahan praktikum untuk 1 semester (Nama bahan, Jumlah, Kelas, Sub Praktikum) -> Disetujui PLP
function fitur_pengajuan_dosen() {
    $redirect = role_url('pengajuan_dosen');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $jml = post_int('jumlah');
        $kelas = trim($_POST['kelas'] ?? '');
        $sub = trim($_POST['sub_praktikum'] ?? '');
        if ($n === null || $jml < 1 || $kelas === '' || $sub === '') {
            go($redirect, 'Pilih nama bahan, isi jumlah kebutuhan 1 semester, kelas, dan nama/sub praktikum.', 'bad');
        }
        $b = $d['barang'][$n];
        $pesan = max(0, $jml - $b['stok']);
        $d['pengajuan_dosen'][] = [
            'id' => count($d['pengajuan_dosen']) + 1,
            'tanggal' => date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'jumlah' => $jml,
            'stok' => $b['stok'],
            'pesan' => $pesan,
            'kelas' => $kelas,
            'sub_praktikum' => $sub,
            'ket' => "Kelas: $kelas | Sub Praktikum: $sub (Kebutuhan 1 Semester)",
            'status' => 'menunggu',
            'catatan' => '',
        ];
        save($d);
        go($redirect, 'Pengajuan bahan praktikum 1 semester berhasil dikirim untuk ditinjau PLP.');
    }
    $title = 'Pengajuan Bahan Praktikum (1 Semester)'; $active = 'pengajuan_dosen'; $d = db();
    $milik = array_reverse(array_filter($d['pengajuan_dosen'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email']));
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">Ajukan kebutuhan bahan praktikum dalam 1 semester. Pengajuan ini akan ditinjau dan disetujui oleh PLP.</p>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="kode">Nama Bahan</label>
        <select id="kode" name="kode" required><option value="">Pilih bahan praktikum</option>
        <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' - ' . $b['spesifikasi'] . ' (' . $b['satuan'] . ')') ?></option><?php endforeach; ?>
        </select>
        <div class="grid3">
            <div><label for="jumlah">Jumlah (Kebutuhan 1 Semester)</label><input id="jumlah" name="jumlah" type="number" min="1" required></div>
            <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" placeholder="Contoh: 2A - Teknik Mesin" required></div>
            <div><label for="sub_praktikum">Nama / Sub Praktikum</label><input id="sub_praktikum" name="sub_praktikum" placeholder="Contoh: Bubut Bertingkat" required></div>
        </div>
        <button class="btn" type="submit">Ajukan Bahan Semester</button>
    </form>
    <h2>Status Pengajuan Bahan Semester Saya</h2>
    <?php dosen_table($milik);
    require __DIR__ . '/partials/footer.php';
}

// Role Mahasiswa: Mengonfirmasi pelaksanaan praktikum & mengajukan pengambilan bahan -> Disetujui Teknisi atau PLP
function fitur_konfirmasi_mhs() {
    $redirect = role_url('permintaan');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $pdId = post_int('pengajuan_dosen_id');
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $jml = post_int('jumlah');
        $kelas = trim($_POST['kelas'] ?? '');
        $sub = trim($_POST['sub_praktikum'] ?? '');
        $tgl = trim($_POST['tanggal_praktikum'] ?? date('Y-m-d'));

        if ($pdId > 0) {
            $idxPd = find_idx($d['pengajuan_dosen'], 'id', $pdId);
            if ($idxPd !== null) {
                $pd = $d['pengajuan_dosen'][$idxPd];
                $n = find_idx($d['barang'], 'kode', $pd['kode']);
                if ($kelas === '') $kelas = $pd['kelas'];
                if ($sub === '') $sub = $pd['sub_praktikum'];
            }
        }

        if ($n === null || $jml < 1 || $kelas === '' || $sub === '') {
            go($redirect, 'Lengkapi bahan praktikum, kelas, sub praktikum, dan jumlah pengambilan bahan.', 'bad');
        }
        $b = $d['barang'][$n];
        $d['permintaan'][] = [
            'id' => count($d['permintaan']) + 1,
            'tanggal' => $tgl ?: date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'pengajuan_dosen_id' => $pdId,
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'jumlah' => $jml,
            'kelas' => $kelas,
            'sub_praktikum' => $sub,
            'ket' => "Konfirmasi Praktikum: $sub | Kelas: $kelas",
            'status' => 'menunggu',
            'disetujui_oleh' => '',
            'catatan' => '',
        ];
        save($d);
        go($redirect, 'Konfirmasi praktikum & pengajuan pengambilan bahan dikirim. Menunggu persetujuan Teknisi atau PLP.');
    }
    $title = 'Konfirmasi Praktikum & Pengambilan Bahan'; $active = 'permintaan'; $d = db();
    $jadwalDosen = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui');
    if (!$jadwalDosen) $jadwalDosen = $d['pengajuan_dosen'];
    $milik = array_reverse(array_filter($d['permintaan'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email'] || norm_role($_SESSION['user']['role']) === 'mhs'));
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">Konfirmasi pelaksanaan praktikum dan ajukan pengambilan bahan praktikum (disetujui oleh Teknisi atau PLP).</p>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <?php if ($jadwalDosen): ?>
        <label for="pengajuan_dosen_id">Pilih Praktikum (dari Pengajuan Dosen)</label>
        <select id="pengajuan_dosen_id" name="pengajuan_dosen_id">
            <option value="">-- Pilih praktikum dosen atau isi manual di bawah --</option>
            <?php foreach ($jadwalDosen as $jd): ?>
            <option value="<?= e($jd['id']) ?>" data-kode="<?= e($jd['kode']) ?>" data-kelas="<?= e($jd['kelas']) ?>" data-sub="<?= e($jd['sub_praktikum']) ?>">
                <?= e($jd['sub_praktikum'] . ' | Kelas ' . $jd['kelas'] . ' | Bahan: ' . $jd['nama']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label for="kode">Nama Bahan yang Diambil</label>
        <select id="kode" name="kode" required><option value="">Pilih bahan</option>
        <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' - ' . $b['spesifikasi'] . ' (' . $b['satuan'] . ')') ?></option><?php endforeach; ?>
        </select>
        <div class="grid2">
            <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" placeholder="Contoh: 2A - Teknik Mesin" required></div>
            <div><label for="sub_praktikum">Nama / Sub Praktikum</label><input id="sub_praktikum" name="sub_praktikum" placeholder="Contoh: Bubut Bertingkat" required></div>
        </div>
        <div class="grid2">
            <div><label for="tanggal_praktikum">Tanggal Praktikum</label><input id="tanggal_praktikum" name="tanggal_praktikum" type="date" value="<?= date('Y-m-d') ?>" required></div>
            <div><label for="jumlah">Jumlah Bahan yang Diambil</label><input id="jumlah" name="jumlah" type="number" min="1" required></div>
        </div>
        <button class="btn" type="submit">Kirim Konfirmasi & Pengajuan Pengambilan</button>
    </form>
    <h2>Status Konfirmasi & Pengambilan Bahan Saya</h2>
    <?php mhs_table($milik); ?>
    <script>
    const selPd = document.getElementById('pengajuan_dosen_id');
    if (selPd) {
        selPd.addEventListener('change', () => {
            const opt = selPd.selectedOptions[0];
            if (opt && opt.value) {
                document.getElementById('kode').value = opt.dataset.kode || '';
                document.getElementById('kelas').value = opt.dataset.kelas || '';
                document.getElementById('sub_praktikum').value = opt.dataset.sub || '';
            }
        });
    }
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Role PLP & Teknisi: Meninjau pengajuan (PLP: pengajuan Kalab, pengajuan Dosen, konfirmasi Mhs; Teknisi: konfirmasi & pengambilan Mhs)
function fitur_tinjau() {
    $role = norm_role($_SESSION['user']['role']);
    $redirect = role_url('tinjau');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $tipe = $_POST['tipe'] ?? ''; $aksi = $_POST['aksi'] ?? '';
        $allowedTipe = $role === 'plp' ? ['pengajuan', 'pengajuan_dosen', 'permintaan'] : ['permintaan'];
        if (!in_array($tipe, $allowedTipe, true) || !in_array($aksi, ['setujui', 'tolak'], true)) {
            go($redirect, 'Permintaan tidak valid atau di luar kewenangan.', 'bad');
        }
        $d = db(); $n = find_idx($d[$tipe], 'id', post_int('id'));
        if ($n === null || $d[$tipe][$n]['status'] !== 'menunggu') {
            go($redirect, 'Data tidak ditemukan atau sudah ditinjau.', 'bad');
        }
        $d[$tipe][$n]['status'] = $aksi === 'setujui' ? 'disetujui' : 'ditolak';
        $d[$tipe][$n]['catatan'] = trim($_POST['catatan'] ?? '');
        $d[$tipe][$n]['disetujui_oleh'] = $_SESSION['user']['nama'] . ' (' . (ROLES[$role] ?? $role) . ')';
        save($d);
        go($redirect, 'Hasil tinjauan berhasil disimpan.');
    }
    $title = $role === 'teknisi' ? 'Tinjau Konfirmasi & Pengambilan Praktikum Mahasiswa' : 'Tinjau Pengajuan';
    $active = 'tinjau';
    $d = db();
    require __DIR__ . '/partials/header.php';

    if ($role === 'plp'): ?>
        <h2>Pengajuan Bahan Praktikum 1 Semester (Dosen)</h2>
        <?php dosen_table(array_reverse($d['pengajuan_dosen']), true); ?>
        <h2>Pengajuan Kebutuhan Bahan (Kepala Lab)</h2>
        <?php req_table(array_reverse($d['pengajuan']), 'pengajuan', true); ?>
    <?php endif; ?>

    <?php render_rekap_kehadiran_praktikum($d['permintaan']); ?>
    <h2>Konfirmasi Praktikum & Pengambilan Bahan (Mahasiswa)</h2>
    <?php mhs_table(array_reverse($d['permintaan']), true);
    require __DIR__ . '/partials/footer.php';
}

function fitur_pembelian() {
    $redirect = role_url('pembelian');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $sumber = $_POST['sumber'] ?? 'pengajuan';
        if (!in_array($sumber, ['pengajuan', 'pengajuan_dosen'], true)) $sumber = 'pengajuan';
        $n = find_idx($d[$sumber], 'id', post_int('id'));
        if ($n === null || $d[$sumber][$n]['status'] !== 'disetujui' || ($d[$sumber][$n]['pesan'] ?? $d[$sumber][$n]['jumlah']) < 1) {
            go($redirect, 'Pengajuan belum disetujui atau stok sudah mencukupi.', 'bad');
        }
        $p = $d[$sumber][$n];
        $jmlPesan = $p['pesan'] ?? $p['jumlah'];
        $d['pembelian'][] = [
            'id' => count($d['pembelian']) + 1,
            'tanggal' => date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'pengajuan_id' => $p['id'],
            'kode' => $p['kode'],
            'nama' => $p['nama'],
            'satuan' => $p['satuan'],
            'jumlah' => $jmlPesan,
            'status' => 'diajukan',
        ];
        $d[$sumber][$n]['status'] = 'dipesan';
        save($d);
        go($redirect, "Permintaan pembelian {$p['nama']} sebanyak {$jmlPesan} {$p['satuan']} dibuat.");
    }
    $title = 'Permintaan Pembelian'; $active = 'pembelian'; $d = db();
    $siap = array_filter($d['pengajuan'], fn($r) => $r['status'] === 'disetujui');
    require __DIR__ . '/partials/header.php';
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
    </table></div><?php endif;
    require __DIR__ . '/partials/footer.php';
}

function fitur_rekap() {
    $title = 'Rekap Kebutuhan dan Pembelian'; $active = 'rekap'; $d = db();
    $st = $_GET['status'] ?? '';
    $rows = array_filter($d['pengajuan'], fn($r) => $st === '' || $r['status'] === $st);
    $rowsDosen = array_filter($d['pengajuan_dosen'], fn($r) => $st === '' || $r['status'] === $st);
    $menunggu = count(array_filter($d['pengajuan'], fn($r) => $r['status'] === 'menunggu'))
              + count(array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'menunggu'));
    $resetUrl = role_url('rekap');
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="cards">
        <div><strong><?= count($d['pengajuan']) + count($d['pengajuan_dosen']) ?></strong><span>Total pengajuan</span></div>
        <div><strong><?= $menunggu ?></strong><span>Menunggu tinjauan</span></div>
        <div><strong><?= count($d['pembelian']) ?></strong><span>Permintaan pembelian</span></div>
        <div><strong><?= count(array_filter($d['barang'], 'low')) ?></strong><span>Barang di bawah minimum</span></div>
    </div>
    <form class="filter" method="get">
        <input type="hidden" name="fitur" value="rekap">
        <div><label for="status">Status pengajuan</label>
        <select id="status" name="status"><option value="">Semua</option>
        <?php foreach (['menunggu', 'disetujui', 'ditolak', 'dipesan'] as $s): ?><option value="<?= $s ?>"<?= $st === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select></div>
        <button class="btn" type="submit">Terapkan</button><a class="reset" href="<?= e($resetUrl) ?>">Reset</a>
    </form>
    <h2>Pengajuan Bahan Semester (Dosen)</h2>
    <?php dosen_table(array_reverse($rowsDosen)); ?>
    <h2>Pengajuan Kebutuhan (Kepala Lab)</h2>
    <?php req_table(array_reverse($rows)); ?>
    <?php render_rekap_kehadiran_praktikum($d['permintaan']); ?>
    <h2>Permintaan pembelian</h2>
    <?php if (!$d['pembelian']): ?><p class="empty">Belum ada permintaan pembelian.</p><?php else: ?>
    <div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Status</th></tr>
    <?php foreach (array_reverse($d['pembelian']) as $b): ?>
    <tr><td><?= e($b['id']) ?></td><td><?= e($b['tanggal']) ?></td><td><?= e($b['nama']) ?></td><td><?= e($b['jumlah'] . ' ' . $b['satuan']) ?></td><td><?= chip($b['status']) ?></td></tr>
    <?php endforeach; ?></table></div><?php endif;
    require __DIR__ . '/partials/footer.php';
}
