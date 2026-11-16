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
    $baru = migrasi_lokasi($d);
    foreach (['ruangan', 'lemari', 'rak_unit', 'penempatan'] as $k) { if (!isset($d[$k])) $d[$k] = []; }
    if ($baru) save($d);
    turunkan_lokasi($d);
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

/* ===== Lokasi penyimpanan: Ruangan > Lemari > Rak. Stok per lokasi disimpan di 'penempatan'. ===== */
function idx($list) { $o = []; foreach ($list as $r) $o[$r['id']] = $r; return $o; }
function next_id($list) { $m = 0; foreach ($list as $r) $m = max($m, (int)$r['id']); return $m + 1; }
function nama_valid($s) { $s = trim(preg_replace('/\s+/', ' ', (string)$s)); return (mb_strlen($s) >= 1 && mb_strlen($s) <= 40) ? $s : ''; }

// Dijalankan sekali: ubah data lama (kolom 'rak' teks per barang) menjadi struktur ruangan/lemari/rak.
function migrasi_lokasi(&$d) {
    if (isset($d['ruangan'])) return false;
    $d['ruangan'] = [['id' => 1, 'nama' => 'Ruang Utama']];
    $d['lemari'] = [['id' => 1, 'ruangan_id' => 1, 'nama' => 'Lemari A']];
    $d['rak_unit'] = []; $d['penempatan'] = []; $map = [];
    foreach (($d['rak'] ?? []) as $r) {
        $nm = trim($r['kode'] ?? ''); if ($nm === '' || isset($map[$nm])) continue;
        $id = next_id($d['rak_unit']); $d['rak_unit'][] = ['id' => $id, 'lemari_id' => 1, 'nama' => $nm]; $map[$nm] = $id;
    }
    foreach ($d['barang'] as $b) {
        $nm = trim($b['rak'] ?? ''); if ($nm === '') continue;
        if (!isset($map[$nm])) { $id = next_id($d['rak_unit']); $d['rak_unit'][] = ['id' => $id, 'lemari_id' => 1, 'nama' => $nm]; $map[$nm] = $id; }
        if ((int)$b['stok'] > 0) $d['penempatan'][] = ['kode' => $b['kode'], 'rak_id' => $map[$nm], 'jumlah' => (int)$b['stok']];
    }
    unset($d['rak']);
    return true;
}

function label_rak($d, $rid) {
    $r = idx($d['rak_unit'])[$rid] ?? null; if (!$r) return '?';
    $l = idx($d['lemari'])[$r['lemari_id']] ?? ['nama' => '?', 'ruangan_id' => 0];
    $u = idx($d['ruangan'])[$l['ruangan_id']] ?? ['nama' => '?'];
    return $u['nama'] . ' > ' . $l['nama'] . ' > ' . $r['nama'];
}

// Isi bidang turunan di tiap barang: 'lokasi' (daftar label rak), 'belum' (stok yang belum punya rak), 'rak' (teks ringkas)
function turunkan_lokasi(&$d) {
    $ada = [];
    foreach ($d['barang'] as $n => $b) {
        $lok = []; $sum = 0;
        foreach ($d['penempatan'] as $p) {
            if ($p['kode'] !== $b['kode']) continue;
            $sum += (int)$p['jumlah']; $lok[] = label_rak($d, $p['rak_id']);
        }
        $belum = max(0, (int)$b['stok'] - $sum);
        $d['barang'][$n]['lokasi'] = $lok;
        $d['barang'][$n]['belum'] = $belum;
        $d['barang'][$n]['rak'] = $lok ? implode('; ', $lok) . ($belum > 0 ? '; + belum ditempatkan' : '') : 'Belum ditempatkan';
    }
}

function jumlah_di($d, $kode, $rid) {
    foreach ($d['penempatan'] as $p) { if ($p['kode'] === $kode && (int)$p['rak_id'] === (int)$rid) return (int)$p['jumlah']; }
    return 0;
}

function atur_penempatan(&$d, $kode, $rid, $delta) {
    foreach ($d['penempatan'] as $n => $p) {
        if ($p['kode'] === $kode && (int)$p['rak_id'] === (int)$rid) {
            $d['penempatan'][$n]['jumlah'] += $delta;
            if ($d['penempatan'][$n]['jumlah'] <= 0) { unset($d['penempatan'][$n]); $d['penempatan'] = array_values($d['penempatan']); }
            return;
        }
    }
    if ($delta > 0) $d['penempatan'][] = ['kode' => $kode, 'rak_id' => (int)$rid, 'jumlah' => (int)$delta];
}

function opsi_lokasi($d, $dipilih = 0, $placeholder = '', $kecuali = 0) {
    $list = [];
    foreach ($d['rak_unit'] as $r) { if ((int)$r['id'] !== (int)$kecuali) $list[label_rak($d, $r['id'])] = $r['id']; }
    ksort($list);
    $h = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($list as $label => $id) $h .= '<option value="' . e($id) . '"' . ((int)$id === (int)$dipilih ? ' selected' : '') . '>' . e($label) . '</option>';
    return $h;
}

function url_rak($r = 0, $l = 0, $k = 0) {
    $u = role_url('rak');
    if ($r > 0) $u .= '&ruangan=' . (int)$r;
    if ($l > 0) $u .= '&lemari=' . (int)$l;
    if ($k > 0) $u .= '&rak=' . (int)$k;
    return $u;
}

function form_hidden($aksi, $r, $l, $k = 0) {
    return field_csrf() . '<input type="hidden" name="aksi" value="' . e($aksi) . '"><input type="hidden" name="r" value="' . (int)$r . '"><input type="hidden" name="l" value="' . (int)$l . '"><input type="hidden" name="k" value="' . (int)$k . '">';
}

function fitur_dashboard() {
    $all = items();
    $q = strtolower(trim($_GET['q'] ?? ''));
    $rak = $_GET['rak'] ?? '';
    $st = $_GET['status'] ?? '';
    $raks = []; foreach ($all as $i) { foreach ($i['lokasi'] as $x) $raks[$x] = 1; } $raks = array_keys($raks); sort($raks);
    $list = array_filter($all, function ($i) use ($q, $rak, $st) {
        if ($q !== '' && !str_contains(strtolower($i['nama'] . ' ' . $i['kode']), $q)) return false;
        if ($rak !== '' && !in_array($rak, $i['lokasi'], true)) return false;
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
        <div><label for="rak">Lokasi</label>
            <select id="rak" name="rak"><option value="">Semua lokasi</option>
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
            <div class="wide"><dt>Lokasi</dt><dd><?= e($i['rak']) ?></dd></div>
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
    <tr><th>Kode</th><th>Nama barang</th><th>Spesifikasi</th><th>Lokasi</th><th>Satuan</th><th>Minimum</th><th>Stok</th><th>Status</th></tr>
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

// Semua role boleh melihat. Hanya PLP yang boleh mengatur ruangan, lemari, rak, dan penempatan stok.
function fitur_rak() {
    $bisaAtur = norm_role($_SESSION['user']['role'] ?? '') === 'plp';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$bisaAtur) { http_response_code(403); exit('403 - Hanya PLP yang boleh mengatur lokasi penyimpanan.'); }
        check_csrf();
        $r = post_int('r'); $l = post_int('l'); $k = post_int('k'); $back = url_rak($r, $l, $k);
        $d = db(); $aksi = $_POST['aksi'] ?? '';
        $tabel = ['ruangan' => ['ruangan', null, 'Ruangan'], 'lemari' => ['lemari', 'ruangan_id', 'Lemari'], 'rak' => ['rak_unit', 'lemari_id', 'Rak']];

        if (in_array($aksi, ['tambah_ruangan', 'tambah_lemari', 'tambah_rak'], true)) {
            $tipe = substr($aksi, 7); [$key, $parentKey, $label] = $tabel[$tipe];
            $nama = nama_valid($_POST['nama'] ?? '');
            if ($nama === '') go($back, "Nama $label wajib diisi (maksimal 40 karakter).", 'bad');
            $parent = 0;
            if ($parentKey) {
                $parent = post_int($parentKey);
                $parentList = $tipe === 'lemari' ? $d['ruangan'] : $d['lemari'];
                if (find_idx($parentList, 'id', $parent) === null) go($back, 'Induk lokasi tidak ditemukan.', 'bad');
            }
            foreach ($d[$key] as $x) {
                if (mb_strtolower($x['nama']) === mb_strtolower($nama) && (!$parentKey || (int)$x[$parentKey] === $parent)) go($back, "$label \"$nama\" sudah ada di sini.", 'bad');
            }
            $baru = ['id' => next_id($d[$key]), 'nama' => $nama];
            if ($parentKey) $baru[$parentKey] = $parent;
            $d[$key][] = $baru;
            save($d);
            go($back, "$label $nama ditambahkan.");
        }

        if ($aksi === 'ubah_nama' || $aksi === 'hapus') {
            $tipe = $_POST['tipe'] ?? '';
            if (!isset($tabel[$tipe])) go($back, 'Permintaan tidak valid.', 'bad');
            [$key, $parentKey, $label] = $tabel[$tipe];
            $n = find_idx($d[$key], 'id', post_int('id'));
            if ($n === null) go($back, "$label tidak ditemukan.", 'bad');
            $id = (int)$d[$key][$n]['id'];
            if ($aksi === 'ubah_nama') {
                $nama = nama_valid($_POST['nama'] ?? '');
                if ($nama === '') go($back, "Nama $label wajib diisi (maksimal 40 karakter).", 'bad');
                foreach ($d[$key] as $x) {
                    if ((int)$x['id'] !== $id && mb_strtolower($x['nama']) === mb_strtolower($nama) && (!$parentKey || $x[$parentKey] == $d[$key][$n][$parentKey])) go($back, "$label \"$nama\" sudah ada di sini.", 'bad');
                }
                $d[$key][$n]['nama'] = $nama;
                save($d);
                go($back, "Nama $label diubah menjadi $nama.");
            }
            $punyaIsi = false;
            if ($tipe === 'ruangan') foreach ($d['lemari'] as $x) { if ((int)$x['ruangan_id'] === $id) $punyaIsi = true; }
            if ($tipe === 'lemari') foreach ($d['rak_unit'] as $x) { if ((int)$x['lemari_id'] === $id) $punyaIsi = true; }
            if ($tipe === 'rak') foreach ($d['penempatan'] as $x) { if ((int)$x['rak_id'] === $id) $punyaIsi = true; }
            if ($punyaIsi) go($back, "$label masih ada isinya. Kosongkan atau pindahkan isinya dulu.", 'bad');
            array_splice($d[$key], $n, 1);
            save($d);
            go($tipe === 'rak' ? url_rak($r, $l) : $back, "$label dihapus.");
        }

        if ($aksi === 'tempatkan') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $rid = post_int('rak_id'); $jml = post_int('jumlah');
            if ($n === null || find_idx($d['rak_unit'], 'id', $rid) === null) go($back, 'Pilih barang dan rak tujuan.', 'bad');
            $b = $d['barang'][$n];
            if ($jml < 1 || $jml > $b['belum']) go($back, "Jumlah harus 1 sampai {$b['belum']} {$b['satuan']} (stok yang belum ditempatkan).", 'bad');
            atur_penempatan($d, $b['kode'], $rid, $jml);
            add_log($d, 'rak', $b['kode'], $jml, 'Ditempatkan di ' . label_rak($d, $rid));
            save($d);
            go($back, "$jml {$b['satuan']} {$b['nama']} ditempatkan di " . label_rak($d, $rid) . '.');
        }

        if ($aksi === 'pindah') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $dari = post_int('dari'); $ke = post_int('ke'); $jml = post_int('jumlah');
            if ($n === null || find_idx($d['rak_unit'], 'id', $ke) === null || $dari === $ke) go($back, 'Pilih rak tujuan yang berbeda.', 'bad');
            $b = $d['barang'][$n]; $ada = jumlah_di($d, $b['kode'], $dari);
            if ($jml < 1 || $jml > $ada) go($back, "Jumlah harus 1 sampai $ada {$b['satuan']}.", 'bad');
            atur_penempatan($d, $b['kode'], $dari, -$jml);
            atur_penempatan($d, $b['kode'], $ke, $jml);
            add_log($d, 'rak', $b['kode'], $jml, 'Dari ' . label_rak($d, $dari) . ' ke ' . label_rak($d, $ke));
            save($d);
            go($back, "$jml {$b['satuan']} {$b['nama']} dipindahkan ke " . label_rak($d, $ke) . '.');
        }
        if ($aksi === 'kurangi') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $rid = post_int('rak_id'); $jml = post_int('jumlah'); $perlu = trim($_POST['keperluan'] ?? '');
            if ($n === null || find_idx($d['rak_unit'], 'id', $rid) === null) go($back, 'Barang atau rak tidak ditemukan.', 'bad');
            if ($perlu === '') go($back, 'Isi keperluan pengurangan (misalnya nama praktikum).', 'bad');
            $b = $d['barang'][$n]; $ada = jumlah_di($d, $b['kode'], $rid);
            if ($jml < 1 || $jml > $ada || $jml > $b['stok']) go($back, "Jumlah harus 1 sampai $ada {$b['satuan']} (isi rak ini).", 'bad');
            $d['barang'][$n]['stok'] -= $jml;
            atur_penempatan($d, $b['kode'], $rid, -$jml);
            add_log($d, 'keluar', $b['kode'], $jml, "$perlu [Dari: " . label_rak($d, $rid) . ', dicatat PLP]');
            $habis = low($d['barang'][$n]);
            save($d);
            if ($habis) go($back, "Stok {$b['nama']} dikurangi $jml {$b['satuan']}. Peringatan: stok kini di bawah minimum.", 'warn');
            go($back, "Stok {$b['nama']} dikurangi $jml {$b['satuan']}.");
        }

        if ($aksi === 'tambah_stok') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $rid = post_int('rak_id'); $jml = post_int('jumlah');
            if ($n === null || find_idx($d['rak_unit'], 'id', $rid) === null) go($back, 'Barang atau rak tidak ditemukan.', 'bad');
            if ($jml < 1) go($back, 'Jumlah harus lebih dari 0.', 'bad');
            $b = $d['barang'][$n];
            $d['barang'][$n]['stok'] += $jml;
            atur_penempatan($d, $b['kode'], $rid, $jml);
            add_log($d, 'masuk', $b['kode'], $jml, trim(($_POST['catatan'] ?? '') . ' [Lokasi: ' . label_rak($d, $rid) . ', dicatat PLP]'));
            save($d);
            go($back, "Stok {$b['nama']} ditambah $jml {$b['satuan']}.");
        }

        if ($aksi === 'tambah_ke_rak') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $ke = post_int('rak_id'); $sumber = post_int('sumber'); $jml = post_int('jumlah');
            if ($n === null || find_idx($d['rak_unit'], 'id', $ke) === null || $sumber === $ke) go($back, 'Pilih barang dan sumbernya.', 'bad');
            if ($sumber > 0 && find_idx($d['rak_unit'], 'id', $sumber) === null) go($back, 'Lokasi asal tidak valid.', 'bad');
            $b = $d['barang'][$n];
            $ada = $sumber > 0 ? jumlah_di($d, $b['kode'], $sumber) : (int)$b['belum'];
            if ($jml < 1 || $jml > $ada) go($back, "Jumlah harus 1 sampai $ada {$b['satuan']} (stok yang tersedia dari sumber itu).", 'bad');
            if ($sumber > 0) atur_penempatan($d, $b['kode'], $sumber, -$jml);
            atur_penempatan($d, $b['kode'], $ke, $jml);
            add_log($d, 'rak', $b['kode'], $jml, ($sumber > 0 ? 'Dari ' . label_rak($d, $sumber) : 'Dari belum ditempatkan') . ' ke ' . label_rak($d, $ke));
            save($d);
            go($back, "{$b['nama']} ($jml {$b['satuan']}) ditambahkan ke " . label_rak($d, $ke) . '.');
        }

        go($back, 'Aksi tidak dikenal.', 'bad');
    }

    // ---------- Tampilan ----------
    $d = db(); $ru = idx($d['ruangan']); $le = idx($d['lemari']); $rk = idx($d['rak_unit']);
    $bar = []; foreach ($d['barang'] as $b) $bar[$b['kode']] = $b;
    $rid = (int)($_GET['ruangan'] ?? 0); $lid = (int)($_GET['lemari'] ?? 0);
    if (!isset($ru[$rid])) { $rid = 0; $lid = 0; }
    if ($lid && (!isset($le[$lid]) || (int)$le[$lid]['ruangan_id'] !== $rid)) $lid = 0;
    $kid = (int)($_GET['rak'] ?? 0);
    if (!$lid || !isset($rk[$kid]) || (int)$rk[$kid]['lemari_id'] !== $lid) $kid = 0;

    // Filter dan urutan
    $q = trim($_GET['q'] ?? ''); $urut = $_GET['urut'] ?? 'nama_asc';
    $labelUrut = ['nama_asc' => 'Nama A ke Z', 'nama_desc' => 'Nama Z ke A', 'jml_asc' => 'Jumlah kecil ke besar', 'jml_desc' => 'Jumlah besar ke kecil'];
    if (!isset($labelUrut[$urut])) $urut = 'nama_asc';
    $cocok = function ($teks) use ($q) { return $q === '' || mb_stripos($teks, $q) !== false; };
    $urutkan = function ($rows, $fNama, $fJml) use ($urut) {
        usort($rows, function ($a, $b) use ($urut, $fNama, $fJml) {
            switch ($urut) {
                case 'nama_desc': return strnatcasecmp($fNama($b), $fNama($a));
                case 'jml_asc': return ($fJml($a) <=> $fJml($b)) ?: strnatcasecmp($fNama($a), $fNama($b));
                case 'jml_desc': return ($fJml($b) <=> $fJml($a)) ?: strnatcasecmp($fNama($a), $fNama($b));
                default: return strnatcasecmp($fNama($a), $fNama($b));
            }
        });
        return $rows;
    };

    $lemariDi = []; foreach ($d['lemari'] as $x) $lemariDi[$x['ruangan_id']][] = $x;
    $rakDi = []; foreach ($d['rak_unit'] as $x) $rakDi[$x['lemari_id']][] = $x;
    $jenisRak = []; $perRak = []; $cocokLemari = []; $cocokRuangan = []; $kodeLemari = []; $kodeRuangan = [];
    foreach ($d['penempatan'] as $p) {
        $x = $rk[$p['rak_id']] ?? null; $lm = $x ? ($le[$x['lemari_id']] ?? null) : null; $b = $bar[$p['kode']] ?? null;
        if (!$lm || !$b) continue;
        $jenisRak[$x['id']] = ($jenisRak[$x['id']] ?? 0) + 1;
        $kodeLemari[$lm['id']][$p['kode']] = 1; $kodeRuangan[$lm['ruangan_id']][$p['kode']] = 1;
        if ($cocok($b['nama'] . ' ' . $b['kode'] . ' ' . label_rak($d, $x['id']))) {
            $perRak[$x['id']][] = $p; $cocokLemari[$lm['id']] = 1; $cocokRuangan[$lm['ruangan_id']] = 1;
        }
    }
    $belumSemua = array_values(array_filter($d['barang'], fn($b) => $b['belum'] > 0));
    $belum = array_values(array_filter($belumSemua, fn($b) => $cocok($b['nama'] . ' ' . $b['kode'])));
    $riwayat = array_slice(array_reverse(array_filter($d['log'], fn($x) => $x['jenis'] === 'rak')), 0, 10);

    // Sumber stok untuk "Tambah barang ke rak"
    $sumberBarang = [];
    foreach ($d['barang'] as $b) {
        if ((int)$b['stok'] < 1) continue;
        $s = [];
        if ($b['belum'] > 0) $s[] = ['val' => 0, 'label' => 'Belum ditempatkan (' . $b['belum'] . ' ' . $b['satuan'] . ')'];
        foreach ($d['penempatan'] as $pp) { if ($pp['kode'] === $b['kode']) $s[] = ['val' => (int)$pp['rak_id'], 'label' => label_rak($d, $pp['rak_id']) . ' (' . $pp['jumlah'] . ' ' . $b['satuan'] . ')']; }
        if ($s) $sumberBarang[$b['kode']] = $s;
    }

    $title = $kid ? 'Rak ' . $rk[$kid]['nama'] : ($lid ? 'Lemari ' . $le[$lid]['nama'] : ($rid ? 'Ruangan ' . $ru[$rid]['nama'] : 'Rak dan Lokasi Barang'));
    $active = 'rak';
    require __DIR__ . '/partials/header.php';

    $kontrol = function ($tipe, $id, $nama) use ($bisaAtur, $rid, $lid, $kid) {
        if (!$bisaAtur) return;
        echo '<div class="mini"><form class="inline" method="post" onsubmit="return confirm(\'Hapus ' . e($nama) . '?\')">' . form_hidden('hapus', $rid, $lid, $kid) . '<input type="hidden" name="tipe" value="' . e($tipe) . '"><input type="hidden" name="id" value="' . (int)$id . '"><button class="btn sec" type="submit">Hapus</button></form></div>';
    };
    $namaKlik = function ($tipe, $id, $nama, $url = '') use ($bisaAtur) {
        if (!$bisaAtur) return $url !== '' ? '<a href="' . e($url) . '">' . e($nama) . '</a>' : e($nama);
        return '<button type="button" class="editnama" data-tipe="' . e($tipe) . '" data-id="' . (int)$id . '" data-nama="' . e($nama) . '" title="Klik untuk ubah nama">' . e($nama) . '</button>';
    };
    $formTambah = function ($aksi, $label, $placeholder, $parentKey = '', $parentId = 0) use ($bisaAtur, $rid, $lid, $kid) {
        if (!$bisaAtur) return;
        echo '<form class="box form" method="post">' . form_hidden($aksi, $rid, $lid, $kid) . ($parentKey ? '<input type="hidden" name="' . e($parentKey) . '" value="' . (int)$parentId . '">' : '')
           . '<label for="' . e($aksi) . '">' . e($label) . '</label><div class="inline"><input id="' . e($aksi) . '" name="nama" placeholder="' . e($placeholder) . '" maxlength="40" style="width:260px;margin:0" required><button class="btn" type="submit">Tambah</button></div></form>';
    };
    $formFilter = function () use ($rid, $lid, $kid, $q, $urut, $labelUrut) {
        echo '<form class="filter" method="get"><input type="hidden" name="fitur" value="rak">'
           . ($rid ? '<input type="hidden" name="ruangan" value="' . (int)$rid . '">' : '') . ($lid ? '<input type="hidden" name="lemari" value="' . (int)$lid . '">' : '') . ($kid ? '<input type="hidden" name="rak" value="' . (int)$kid . '">' : '')
           . '<div><label for="q">Cari barang atau rak</label><input id="q" name="q" value="' . e($q) . '" placeholder="Nama barang, kode, atau nama rak"></div>'
           . '<div><label for="urut">Urutkan</label><select id="urut" name="urut">';
        foreach ($labelUrut as $k => $v) echo '<option value="' . e($k) . '"' . ($k === $urut ? ' selected' : '') . '>' . e($v) . '</option>';
        echo '</select></div><button class="btn" type="submit">Terapkan</button><a class="reset" href="' . e(url_rak($rid, $lid, $kid)) . '">Reset</a></form>';
    };
    ?>
    <nav class="crumb" aria-label="Lokasi">
        <?php if ($rid): ?><a href="<?= e(url_rak()) ?>">Semua ruangan</a><?php else: ?><strong>Semua ruangan</strong><?php endif; ?>
        <?php if ($rid): ?><span>&gt;</span><?php if ($lid): ?><a href="<?= e(url_rak($rid)) ?>"><?= e($ru[$rid]['nama']) ?></a><?php else: ?><strong><?= e($ru[$rid]['nama']) ?></strong><?php endif; endif; ?>
        <?php if ($lid): ?><span>&gt;</span><?php if ($kid): ?><a href="<?= e(url_rak($rid, $lid)) ?>"><?= e($le[$lid]['nama']) ?></a><?php else: ?><strong><?= e($le[$lid]['nama']) ?></strong><?php endif; endif; ?>
        <?php if ($kid): ?><span>&gt;</span><strong>Rak <?= e($rk[$kid]['nama']) ?></strong><?php endif; ?>
    </nav>

    <?php /* ===== TOMBOL KEMBALI ===== */
    if ($kid)      { $urlKembali = url_rak($rid, $lid); $teksKembali = 'Kembali ke daftar rak'; }
    elseif ($lid)  { $urlKembali = url_rak($rid);       $teksKembali = 'Kembali ke daftar lemari'; }
    elseif ($rid)  { $urlKembali = url_rak();           $teksKembali = 'Kembali ke daftar ruangan'; }
    else           { $urlKembali = '';                  $teksKembali = ''; }
    if ($urlKembali !== ''): ?>
        <a class="btn" style="text-decoration:none;display:inline-block;margin-bottom:14px;background:var(--orange);color:#fff" href="<?= e($urlKembali) ?>"><?= e($teksKembali) ?></a>
    <?php endif; ?>

    <?php if ($bisaAtur): ?>
    <form id="formUbahNama" method="post" hidden><?= form_hidden('ubah_nama', $rid, $lid, $kid) ?><input type="hidden" name="tipe" id="un-tipe"><input type="hidden" name="id" id="un-id"><input type="hidden" name="nama" id="un-nama"></form>
    <script>
    document.addEventListener('click', function (ev) {
        const tombol = ev.target.closest('.editnama');
        if (!tombol) return;
        const input = document.createElement('input');
        input.type = 'text'; input.value = tombol.dataset.nama; input.maxLength = 40; input.className = 'editnama-input';
        input.setAttribute('aria-label', 'Nama baru');
        let selesai = false;
        function simpan() {
            if (selesai) return; selesai = true;
            const baru = input.value.trim();
            if (baru === '' || baru === tombol.dataset.nama) { input.replaceWith(tombol); return; }
            document.getElementById('un-tipe').value = tombol.dataset.tipe;
            document.getElementById('un-id').value = tombol.dataset.id;
            document.getElementById('un-nama').value = baru;
            document.getElementById('formUbahNama').submit();
        }
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); simpan(); }
            if (e.key === 'Escape') { selesai = true; input.replaceWith(tombol); }
        });
        input.addEventListener('blur', simpan);
        tombol.replaceWith(input); input.focus(); input.select();
    });
    </script>
    <?php endif; ?>

    <?php if (!$rid): /* ===== Tingkat 1: daftar ruangan ===== */ ?>
    <p class="sum"><?= count($d['ruangan']) ?> ruangan, <?= count($d['lemari']) ?> lemari, <?= count($d['rak_unit']) ?> rak. Klik ruangan untuk melihat isinya.</p>
    <?php $formTambah('tambah_ruangan', 'Tambah ruangan baru', 'Contoh: Lab Bubut'); $formFilter(); ?>
    <?php
    $tampil = array_filter($d['ruangan'], fn($x) => $q === '' || isset($cocokRuangan[$x['id']]) || $cocok($x['nama']));
    $tampil = $urutkan($tampil, fn($x) => $x['nama'], fn($x) => count($kodeRuangan[$x['id']] ?? []));
    if (!$tampil): ?><p class="empty"><?= $q !== '' ? 'Tidak ada ruangan yang cocok dengan pencarian.' : 'Belum ada ruangan.' ?></p><?php endif; ?>
    <div class="rakgrid">
    <?php foreach ($tampil as $x): $jl = count($lemariDi[$x['id']] ?? []); $jr = 0; foreach ($lemariDi[$x['id']] ?? [] as $lm) $jr += count($rakDi[$lm['id']] ?? []); ?>
        <section class="rak">
            <h2><?= $namaKlik('ruangan', $x['id'], $x['nama'], url_rak($x['id'])) ?></h2>
            <p class="stats"><?= $jl ?> lemari, <?= $jr ?> rak, <?= count($kodeRuangan[$x['id']] ?? []) ?> jenis barang</p>
            <a class="btn" style="text-decoration:none;display:inline-block" href="<?= e(url_rak($x['id'])) ?>">Buka ruangan</a>
            <?php $kontrol('ruangan', $x['id'], $x['nama']); ?>
        </section>
    <?php endforeach; ?>
    </div>

    <h2>Stok yang belum ditempatkan di rak</h2>
    <?php $belum = $urutkan($belum, fn($b) => $b['nama'], fn($b) => $b['belum']); ?>
    <?php if (!$belum): ?><p class="empty"><?= $belumSemua ? 'Tidak ada yang cocok dengan pencarian.' : 'Semua stok sudah punya lokasi.' ?></p><?php endif; ?>
    <?php foreach ($belum as $b): ?>
        <div class="rak"><div class="rakrow" style="border-top:0;padding:0">
            <div><strong><?= e($b['nama']) ?></strong> <span class="muted">(<?= e($b['kode']) ?>)</span><br><small class="muted">Belum ditempatkan: <?= e($b['belum'] . ' ' . $b['satuan']) ?> dari total <?= e($b['stok']) ?></small></div>
            <?php if ($bisaAtur && $d['rak_unit']): ?>
            <form class="inline" method="post"><?= form_hidden('tempatkan', $rid, $lid) ?><input type="hidden" name="kode" value="<?= e($b['kode']) ?>">
                <select name="rak_id" aria-label="Rak tujuan <?= e($b['nama']) ?>" required><?= opsi_lokasi($d, 0, 'Pilih rak') ?></select>
                <input type="number" name="jumlah" min="1" max="<?= e($b['belum']) ?>" value="<?= e($b['belum']) ?>" aria-label="Jumlah" required>
                <button class="btn" type="submit">Tempatkan</button></form>
            <?php endif; ?>
        </div></div>
    <?php endforeach; ?>
    <?php if ($bisaAtur): ?><h2>Riwayat penempatan terbaru</h2><?php include __DIR__ . '/partials/log.php'; endif; ?>

    <?php elseif (!$lid): /* ===== Tingkat 2: isi ruangan ===== */ ?>
    <p class="sum"><?= count($lemariDi[$rid] ?? []) ?> lemari, <?= count($kodeRuangan[$rid] ?? []) ?> jenis barang di ruangan ini. Klik lemari untuk melihat rak-raknya.</p>
    <?php $formTambah('tambah_lemari', 'Tambah lemari baru', 'Contoh: Lemari B', 'ruangan_id', $rid); $formFilter(); ?>
    <?php
    $tampil = array_filter($lemariDi[$rid] ?? [], fn($x) => $q === '' || isset($cocokLemari[$x['id']]) || $cocok($ru[$rid]['nama'] . ' ' . $x['nama']));
    $tampil = $urutkan($tampil, fn($x) => $x['nama'], fn($x) => count($kodeLemari[$x['id']] ?? []));
    if (!$tampil): ?><p class="empty"><?= $q !== '' ? 'Tidak ada lemari yang cocok dengan pencarian.' : 'Belum ada lemari di ruangan ini.' ?></p><?php endif; ?>
    <div class="rakgrid">
    <?php foreach ($tampil as $x): ?>
        <section class="rak">
            <h2><?= $namaKlik('lemari', $x['id'], $x['nama'], url_rak($rid, $x['id'])) ?></h2>
            <p class="stats"><?= count($rakDi[$x['id']] ?? []) ?> rak, <?= count($kodeLemari[$x['id']] ?? []) ?> jenis barang</p>
            <a class="btn" style="text-decoration:none;display:inline-block" href="<?= e(url_rak($rid, $x['id'])) ?>">Buka lemari</a>
            <?php $kontrol('lemari', $x['id'], $x['nama']); ?>
        </section>
    <?php endforeach; ?>
    </div>

    <h2>Daftar barang di ruangan ini</h2>
    <?php
    $baris = [];
    foreach ($lemariDi[$rid] ?? [] as $lm) foreach ($rakDi[$lm['id']] ?? [] as $x) foreach ($perRak[$x['id']] ?? [] as $p) { if (isset($bar[$p['kode']])) $baris[] = ['b' => $bar[$p['kode']], 'lm' => $lm, 'x' => $x, 'jml' => (int)$p['jumlah']]; }
    $baris = $urutkan($baris, fn($r) => $r['b']['nama'], fn($r) => $r['jml']);
    if (!$baris): ?><p class="empty"><?= $q !== '' ? 'Tidak ada barang yang cocok dengan pencarian.' : 'Belum ada barang di ruangan ini.' ?></p><?php else: ?>
    <div class="tablewrap"><table><tr><th>Kode</th><th>Barang</th><th>Lemari</th><th>Rak</th><th>Jumlah</th></tr>
    <?php foreach ($baris as $r): ?>
        <tr><td><?= e($r['b']['kode']) ?></td><td><?= e($r['b']['nama']) ?></td><td><a href="<?= e(url_rak($rid, $r['lm']['id'])) ?>"><?= e($r['lm']['nama']) ?></a></td><td><?= e($r['x']['nama']) ?></td><td><?= e($r['jml'] . ' ' . $r['b']['satuan']) ?></td></tr>
    <?php endforeach; ?></table></div>
    <?php endif; ?>

    <?php elseif (!$kid): /* ===== Tingkat 3: daftar rak (ringkas) ===== */ ?>
    <p class="sum"><?= count($rakDi[$lid] ?? []) ?> rak di lemari ini. Satu rak bisa diisi banyak jenis barang, tanpa batas. Klik "Lihat detail" untuk mengurangi, menambah, atau memindahkan barang.</p>
    <?php $formTambah('tambah_rak', 'Tambah rak baru', 'Contoh: Rak 3', 'lemari_id', $lid); $formFilter(); ?>
    <?php
    $tampil = array_filter($rakDi[$lid] ?? [], fn($x) => $q === '' || isset($perRak[$x['id']]) || $cocok(label_rak($d, $x['id'])));
    $tampil = $urutkan($tampil, fn($x) => $x['nama'], fn($x) => $jenisRak[$x['id']] ?? 0);
    if (!$tampil): ?><p class="empty"><?= $q !== '' ? 'Tidak ada rak atau barang yang cocok dengan pencarian.' : 'Belum ada rak di lemari ini.' ?></p><?php endif; ?>
    <div class="rakgrid">
    <?php foreach ($tampil as $x): ?>
        <section class="rak">
            <h2>Rak <?= $namaKlik('rak', $x['id'], $x['nama']) ?></h2>
            <p class="stats"><?= (int)($jenisRak[$x['id']] ?? 0) ?> jenis barang</p>
            <a class="btn" style="text-decoration:none;display:inline-block" href="<?= e(url_rak($rid, $lid, $x['id'])) ?>">Lihat detail</a>
        </section>
    <?php endforeach; ?>
    </div>

    <?php else: /* ===== Tingkat 4: detail satu rak ===== */
    $isi = $urutkan($perRak[$kid] ?? [], fn($p) => $bar[$p['kode']]['nama'] ?? '', fn($p) => (int)$p['jumlah']); ?>
    <p class="sum">Rak <?= $namaKlik('rak', $kid, $rk[$kid]['nama']) ?> berisi <?= (int)($jenisRak[$kid] ?? 0) ?> jenis barang.<?= $bisaAtur ? ' Pakai tombol Kurangi, Tambah, atau Pindah di tiap barang.' : '' ?></p>

    <?php if ($bisaAtur && $sumberBarang): ?>
    <div class="box"><details class="tempat" style="margin-top:0"><summary>+ Tambah barang lain ke rak ini</summary>
        <form class="mini tambahrak" method="post" data-rak="<?= e($kid) ?>"><?= form_hidden('tambah_ke_rak', $rid, $lid, $kid) ?><input type="hidden" name="rak_id" value="<?= e($kid) ?>">
            <select name="kode" class="tr-kode" aria-label="Barang" required><option value="">Pilih barang</option>
            <?php foreach ($d['barang'] as $b): if (!isset($sumberBarang[$b['kode']])) continue; ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . ')') ?></option><?php endforeach; ?>
            </select>
            <select name="sumber" class="tr-sumber" aria-label="Ambil dari" required><option value="">Pilih barang dulu</option></select>
            <input type="number" name="jumlah" min="1" placeholder="Jumlah" aria-label="Jumlah" style="width:90px" required>
            <button class="btn" type="submit">Tambah</button></form>
    </details></div>
    <?php endif; ?>

    <?php $formFilter(); ?>
    <?php if (!$isi): ?><p class="empty"><?= $q !== '' ? 'Tidak ada barang yang cocok dengan pencarian.' : 'Rak masih kosong.' ?></p><?php endif; ?>
    <?php foreach ($isi as $p): $b = $bar[$p['kode']] ?? null; if (!$b) continue; ?>
    <div class="rak"><div class="rakrow<?= low($b) ? ' low' : '' ?>" style="border-top:0;padding-top:0;padding-bottom:0">
        <div><strong><?= e($b['nama']) ?></strong> <span class="muted">(<?= e($b['kode']) ?>)</span><br>
            <small class="muted">Di rak ini: <strong><?= e($p['jumlah'] . ' ' . $b['satuan']) ?></strong> | total stok <?= e($b['stok']) ?> | minimum <?= e($b['minimum']) ?></small>
            <span class="chip <?= low($b) ? 'bad' : 'ok' ?>"><?= low($b) ? 'Di bawah minimum' : 'Aman' ?></span></div>
        <?php if ($bisaAtur): ?>
        <div class="aksi">
            <details class="tempat"><summary>Kurangi</summary>
                <form class="mini" method="post"><?= form_hidden('kurangi', $rid, $lid, $kid) ?><input type="hidden" name="kode" value="<?= e($b['kode']) ?>"><input type="hidden" name="rak_id" value="<?= e($kid) ?>">
                    <input type="number" name="jumlah" min="1" max="<?= e($p['jumlah']) ?>" value="1" aria-label="Jumlah dikurangi" style="width:90px" required>
                    <input name="keperluan" placeholder="Keperluan, mis. Praktikum Bubut 2A" aria-label="Keperluan" style="min-width:230px" required>
                    <button class="btn" type="submit">Kurangi stok</button></form></details>
            <details class="tempat"><summary>Tambah</summary>
                <form class="mini" method="post"><?= form_hidden('tambah_stok', $rid, $lid, $kid) ?><input type="hidden" name="kode" value="<?= e($b['kode']) ?>"><input type="hidden" name="rak_id" value="<?= e($kid) ?>">
                    <input type="number" name="jumlah" min="1" value="1" aria-label="Jumlah ditambah" style="width:90px" required>
                    <input name="catatan" placeholder="Catatan (opsional)" aria-label="Catatan" style="min-width:230px">
                    <button class="btn" type="submit">Tambah stok</button></form></details>
            <?php if (count($d['rak_unit']) > 1): ?>
            <details class="tempat"><summary>Pindah</summary>
                <form class="mini" method="post"><?= form_hidden('pindah', $rid, $lid, $kid) ?><input type="hidden" name="kode" value="<?= e($b['kode']) ?>"><input type="hidden" name="dari" value="<?= e($kid) ?>">
                    <select name="ke" aria-label="Pindahkan ke" required><?= opsi_lokasi($d, 0, 'Pindah ke...', $kid) ?></select>
                    <input type="number" name="jumlah" min="1" max="<?= e($p['jumlah']) ?>" value="<?= e($p['jumlah']) ?>" aria-label="Jumlah dipindah" style="width:90px" required>
                    <button class="btn sec" type="submit">Pindah</button></form></details>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div></div>
    <?php endforeach; ?>

    <?php if ($bisaAtur): ?><div class="box"><strong>Kelola rak ini</strong><?php $kontrol('rak', $kid, $rk[$kid]['nama']); ?></div><?php endif; ?>
    <script>
    const SUM = <?= json_encode($sumberBarang, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    document.querySelectorAll('form.tambahrak').forEach(f => {
        const kb = f.querySelector('.tr-kode'), ks = f.querySelector('.tr-sumber'), rak = f.dataset.rak;
        kb.addEventListener('change', () => {
            ks.innerHTML = '';
            const ada = (SUM[kb.value] || []).filter(o => String(o.val) !== rak);
            const ph = document.createElement('option'); ph.value = '';
            ph.textContent = !kb.value ? 'Pilih barang dulu' : (ada.length ? 'Ambil dari...' : 'Tidak ada stok dari rak lain');
            ks.appendChild(ph);
            ada.forEach(o => { const op = document.createElement('option'); op.value = o.val; op.textContent = o.label; ks.appendChild(op); });
            if (ada.length === 1) ks.value = String(ada[0].val);
        });
    });
    </script>
    <?php endif;
    require __DIR__ . '/partials/footer.php';
}

function fitur_peringatan() {
    $title = 'Peringatan Stok'; $active = 'peringatan';
    $rows = array_filter(items(), 'low');
    require __DIR__ . '/partials/header.php';
    if (!$rows): ?><p class="empty">Semua stok masih di atas batas minimum.</p><?php else: ?>
    <div class="tablewrap"><table>
    <tr><th>Kode</th><th>Barang</th><th>Lokasi</th><th>Stok</th><th>Minimum</th><th>Kekurangan</th></tr>
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
        $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah'); $rid = post_int('rak_id');
        if ($n === null || $jml < 1) go($redirect, 'Pilih barang dan isi jumlah lebih dari 0.', 'bad');
        if ($rid > 0 && find_idx($d['rak_unit'], 'id', $rid) === null) go($redirect, 'Lokasi penyimpanan tidak valid.', 'bad');
        $kode = $d['barang'][$n]['kode'];
        $d['barang'][$n]['stok'] += $jml;
        if ($rid > 0) atur_penempatan($d, $kode, $rid, $jml);
        $lok = $rid > 0 ? label_rak($d, $rid) : 'belum ditempatkan';
        add_log($d, 'masuk', $kode, $jml, trim(($_POST['catatan'] ?? '') . " [Lokasi: $lok]"));
        save($d);
        go($redirect, "Stok {$d['barang'][$n]['nama']} bertambah $jml {$d['barang'][$n]['satuan']} ($lok).");
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
        <label for="rak_id">Disimpan di (ruangan > lemari > rak)</label>
        <select id="rak_id" name="rak_id"><?= opsi_lokasi($d, 0, 'Belum ditempatkan (PLP yang mengatur nanti)') ?></select>
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
        $d = db(); $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? ''); $jml = post_int('jumlah'); $rid = post_int('rak_id');
        $pengaju = trim($_POST['pengaju'] ?? ''); $perlu = trim($_POST['keperluan'] ?? '');
        if ($n === null || $jml < 1 || $pengaju === '' || $perlu === '') go($redirect, 'Lengkapi barang, jumlah, pengaju, dan keperluan.', 'bad');
        $kode = $d['barang'][$n]['kode'];
        $tersedia = $rid > 0 ? jumlah_di($d, $kode, $rid) : (int)$d['barang'][$n]['belum'];
        $lok = $rid > 0 ? label_rak($d, $rid) : 'belum ditempatkan';
        if ($jml > $tersedia) go($redirect, "Stok di lokasi itu ($lok) hanya $tersedia {$d['barang'][$n]['satuan']}.", 'bad');
        $b = &$d['barang'][$n];
        if ($jml > $b['stok']) go($redirect, "Stok {$b['nama']} tidak cukup. Tersedia {$b['stok']} {$b['satuan']}.", 'bad');
        $b['stok'] -= $jml;
        $habis = low($b); $nama = $b['nama']; unset($b);
        if ($rid > 0) atur_penempatan($d, $kode, $rid, -$jml);
        add_log($d, 'keluar', $kode, $jml, "$pengaju, $perlu [Dari: $lok]");
        save($d);
        if ($habis) go($redirect, "Pengeluaran dicatat. Peringatan: stok $nama kini di bawah minimum.", 'warn');
        go($redirect, 'Pengeluaran bahan dicatat.');
    }
    $title = 'Pengeluaran Bahan'; $active = 'keluar'; $d = db();
    $riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'keluar')), 0, 10);
    $opsiLok = [];
    foreach ($d['barang'] as $b) {
        foreach ($d['penempatan'] as $p) {
            if ($p['kode'] === $b['kode']) $opsiLok[] = ['kode' => $b['kode'], 'val' => (int)$p['rak_id'], 'label' => label_rak($d, $p['rak_id']) . ' (tersedia ' . $p['jumlah'] . ' ' . $b['satuan'] . ')'];
        }
        if ($b['belum'] > 0) $opsiLok[] = ['kode' => $b['kode'], 'val' => 0, 'label' => 'Belum ditempatkan (tersedia ' . $b['belum'] . ' ' . $b['satuan'] . ')'];
    }
    require __DIR__ . '/partials/header.php';
    ?>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="kode">Barang</label>
        <select id="kode" name="kode" required><option value="">Pilih barang</option>
        <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . '), stok ' . $b['stok'] . ' ' . $b['satuan']) ?></option><?php endforeach; ?>
        </select>
        <label for="rak_id">Diambil dari</label>
        <select id="rak_id" name="rak_id" required><option value="">Pilih barang lebih dulu</option></select>
        <label for="jumlah">Jumlah keluar</label><input id="jumlah" name="jumlah" type="number" min="1" required>
        <label for="pengaju">Pengaju</label><input id="pengaju" name="pengaju" placeholder="Nama dosen atau mahasiswa" required>
        <label for="keperluan">Mata kuliah atau keperluan</label><input id="keperluan" name="keperluan" required>
        <button class="btn" type="submit">Catat pengeluaran</button>
    </form>
    <script>
    const LOK = <?= json_encode($opsiLok, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    const selB = document.getElementById('kode'), selL = document.getElementById('rak_id');
    function isiLokasi() {
        selL.innerHTML = '';
        const ada = LOK.filter(o => o.kode === selB.value);
        if (!selB.value) ada.length = 0;
        const ph = document.createElement('option'); ph.value = '';
        ph.textContent = !selB.value ? 'Pilih barang lebih dulu' : (ada.length ? 'Pilih lokasi' : 'Tidak ada stok di lokasi mana pun');
        selL.appendChild(ph);
        ada.forEach(o => { const op = document.createElement('option'); op.value = o.val; op.textContent = o.label; selL.appendChild(op); });
        if (ada.length === 1) selL.value = String(ada[0].val);
    }
    selB.addEventListener('change', isiLokasi);
    </script>
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