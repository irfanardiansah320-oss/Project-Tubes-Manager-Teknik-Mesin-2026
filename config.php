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
        'approval_kalab'  => 'Approval Pengajuan PLP',
        'semua_pengajuan' => 'Keseluruhan Pengajuan Bahan',
        'peringatan'      => 'Peringatan Stok',
    ],
    'kajur' => [
        'dashboard'  => 'Dashboard',
        'stok'       => 'Stok Barang',
        'rak'        => 'Rak & Kapasitas',
        'rekap'      => 'Rekap',
    ],
    'teknisi' => [
        'dashboard'   => 'Dashboard',
        'stok'        => 'Stok Barang',
        'rak'         => 'Rak & Kapasitas',
        'peringatan'  => 'Peringatan Stok',
        'masuk'       => 'Barang Masuk',
        'keluar'      => 'Pengeluaran Bahan',
        'tinjau'      => 'Konfirmasi Praktikum Mhs',
        'stockopname' => 'Stock Opname',
    ],
    'plp' => [
        'dashboard'  => 'Dashboard',
        'stok'       => 'Stok Barang',
        'rak'        => 'Rak & Kapasitas',
        'peringatan' => 'Peringatan Stok',
        'tinjau'     => 'Tinjau Pengajuan Dosen & Mhs',
        'pengajuan'  => 'Pengajuan Kebutuhan ke Kalab',
        'cek_barang' => 'Cek Barang Real-Time',
        'pembelian'  => 'Permintaan Pembelian',
        'rekap'      => 'Rekap',
    ],
    'dosen' => [
        'pengajuan_dosen' => 'Pengajuan Bahan Praktikum',
    ],
    'mhs' => [
        'permintaan' => 'Konfirmasi & Pengambilan Praktikum',
    ],
];

// Pemetaan file fitur langsung (kompatibilitas akses langsung)
const PAGES = [
    'dashboard.php'  => ['Dashboard', ['teknisi', 'kajur', 'plp']],
    'stok.php'       => ['Stok Barang', ['teknisi', 'kajur', 'plp']],
    'rak.php'        => ['Rak & Kapasitas', ['teknisi', 'kajur', 'plp']],
    'peringatan.php' => ['Peringatan Stok', ['teknisi', 'kalab', 'plp']],
    'masuk.php'      => ['Barang Masuk', ['teknisi']],
    'keluar.php'     => ['Pengeluaran Bahan', ['teknisi']],
    'pengajuan.php'  => ['Pengajuan Kebutuhan', ['plp', 'kalab', 'dosen']],
    'tinjau.php'     => ['Tinjau Pengajuan', ['plp', 'teknisi', 'kalab']],
    'pembelian.php'  => ['Permintaan Pembelian', ['plp']],
    'rekap.php'      => ['Rekap', ['kajur', 'plp', 'kalab']],
    'permintaan.php' => ['Konfirmasi & Pengambilan Praktikum', ['mhs', 'mahasiswa']],
];

const DEFAULT_RAK_KAPASITAS = [
    'A-01' => 50,
    'A-02' => 500,
    'A-03' => 500,
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
        'rak_kapasitas' => DEFAULT_RAK_KAPASITAS,
        'pengajuan_dosen' => [
            [
                'id' => 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'Dosen Pengampu',
                'oleh_email' => 'dosen@polban.ac.id',
                'kode' => 'MPK-0018',
                'nama' => 'Kuningan batangan',
                'satuan' => 'cm',
                'ukuran_pcs' => 100,
                'ukuran_per_mhs' => 10,
                'jml_mhs' => 24,
                'jumlah' => 240,
                'jml_pcs' => 3,
                'stok' => 300,
                'pesan' => 0,
                'kelas' => '2B - D3 Teknik Mesin',
                'sub_praktikum' => 'Bubut Bertingkat',
                'ket' => 'Kelas: 2B - D3 Teknik Mesin | Sub: Bubut Bertingkat | 24 mhs × 10 cm (1 pcs = 100 cm, butuh 3 pcs)',
                'status' => 'disetujui',
                'diajukan_ke_kalab' => false,
                'catatan' => 'Disetujui PLP untuk 1 semester',
            ],
            [
                'id' => 2,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'Dosen Pengampu',
                'oleh_email' => 'dosen@polban.ac.id',
                'kode' => 'MPK-0012',
                'nama' => 'Baja pejal',
                'satuan' => 'cm',
                'ukuran_pcs' => 100,
                'ukuran_per_mhs' => 20,
                'jml_mhs' => 30,
                'jumlah' => 600,
                'jml_pcs' => 6,
                'stok' => 10,
                'pesan' => 590,
                'kelas' => '2B - D3 Teknik Mesin',
                'sub_praktikum' => 'Praktik Pemesinan Bubut',
                'ket' => 'Kelas: 2B - D3 Teknik Mesin | Sub: Praktik Pemesinan Bubut | 30 mhs × 20 cm (1 pcs = 100 cm, butuh 6 pcs)',
                'status' => 'disetujui',
                'diajukan_ke_kalab' => true,
                'catatan' => 'Disetujui PLP, diteruskan ke Kepala Lab',
            ],
        ],
        'pengajuan' => [
            [
                'id' => 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'PLP',
                'oleh_email' => 'plp@polban.ac.id',
                'pengajuan_dosen_id' => 2,
                'kode' => 'MPK-0012',
                'nama' => 'Baja pejal',
                'satuan' => 'cm',
                'ukuran_pcs' => 100,
                'ukuran_per_mhs' => 20,
                'jml_mhs' => 30,
                'jml_pcs' => 7,
                'minimum_baru' => 120,
                'jumlah' => 660,
                'stok' => 10,
                'pesan' => 650,
                'ket' => 'Dari Pengajuan Dosen #2 (Praktik Pemesinan Bubut - 2B): 30 mhs × 20 cm (+tol 10% = 660 cm / 7 pcs @100 cm), stok 10 cm, perlu dipesan 650 cm, stok min: 120 cm',
                'status' => 'menunggu',
                'disetujui_oleh' => '',
                'catatan' => '',
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
                'tipe_ambil' => 'sebagian',
                'total_jatah' => 10,
                'jumlah' => 5,
                'sisa_ambil' => 5,
                'kelas' => '2B - D3 Teknik Mesin',
                'sub_praktikum' => 'Bubut Bertingkat',
                'ket' => 'Konfirmasi Praktikum: Bubut Bertingkat | Kelas: 2B - D3 Teknik Mesin | Pengambilan: Sebagian (5/10 cm, sisa 5 cm)',
                'status' => 'menunggu',
                'disetujui_oleh' => '',
                'catatan' => '',
            ],
        ],
        'stockopname' => [
            [
                'id' => 1,
                'waktu' => date('Y-m-d H:i'),
                'oleh' => 'Teknisi Lab',
                'kode' => 'MPK-0012',
                'nama' => 'Baja pejal',
                'satuan' => 'cm',
                'rak' => 'A-02',
                'stok_sistem' => 10,
                'stok_fisik' => 10,
                'selisih' => 0,
                'kondisi' => 'Sesuai / Baik',
                'catatan' => 'Pengecekan rutin rak A-02',
                'status' => 'menunggu_cek_plp',
                'dicek_oleh' => '',
                'catatan_plp' => '',
            ],
        ],
        'pembelian' => [],
        'log' => [],
    ]);
}

function db() {
    if (!is_file(DB_FILE)) seed();
    $d = json_decode(file_get_contents(DB_FILE), true);
    if (!isset($d['rak_kapasitas'])) $d['rak_kapasitas'] = DEFAULT_RAK_KAPASITAS;
    if (!isset($d['pengajuan_dosen'])) $d['pengajuan_dosen'] = [];
    if (!isset($d['permintaan'])) $d['permintaan'] = [];
    if (!isset($d['pengajuan'])) $d['pengajuan'] = [];
    if (!isset($d['stockopname'])) $d['stockopname'] = [];
    if (!isset($d['pembelian'])) $d['pembelian'] = [];
    if (!isset($d['log'])) $d['log'] = [];
    foreach ($d['barang'] as &$b) {
        if (!isset($b['ukuran_pcs']) || $b['ukuran_pcs'] < 1) {
            $b['ukuran_pcs'] = $b['satuan'] === 'cm' ? 100 : 1;
        }
        if (!isset($d['rak_kapasitas'][$b['rak']])) {
            $d['rak_kapasitas'][$b['rak']] = 500;
        }
    }
    unset($b);
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
function add_log(&$d, $jenis, $kode, $jumlah, $catatan, $verified = false) {
    $d['log'][] = [
        'id' => count($d['log']) + 1,
        'waktu' => date('Y-m-d H:i'),
        'oleh' => $_SESSION['user']['nama'],
        'jenis' => $jenis,
        'kode' => $kode,
        'jumlah' => $jumlah,
        'catatan' => $catatan,
        'dicek_plp' => $verified,
        'dicek_oleh' => $verified ? $_SESSION['user']['nama'] : '',
    ];
}

// Logika penjumlahan bahan/alat di dalam rak dan status kapasitas rak
function rak_terpakai($barangList, $rak) {
    $sum = 0;
    foreach ($barangList as $b) {
        if (($b['rak'] ?? '') === $rak) {
            $sum += (int)$b['stok'];
        }
    }
    return $sum;
}

function rak_info($d, $rak) {
    $kapasitas = (int)($d['rak_kapasitas'][$rak] ?? 500);
    $terpakai = rak_terpakai($d['barang'], $rak);
    $sisa = max(0, $kapasitas - $terpakai);
    $pct = $kapasitas > 0 ? min(100, (int)round($terpakai / $kapasitas * 100)) : 0;
    if ($terpakai >= $kapasitas) {
        $status = 'Penuh';
        $chipClass = 'bad';
    } elseif ($pct >= 80) {
        $status = 'Hampir Penuh';
        $chipClass = 'warn';
    } else {
        $status = 'Tersedia';
        $chipClass = 'ok';
    }
    return [
        'rak' => $rak,
        'kapasitas' => $kapasitas,
        'terpakai' => $terpakai,
        'sisa' => $sisa,
        'pct' => $pct,
        'status' => $status,
        'chip' => $chipClass,
    ];
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
    $c = [
        'menunggu' => 'warn',
        'menunggu_cek_plp' => 'warn',
        'sebagian' => 'warn',
        'ditolak' => 'bad',
    ][$s] ?? 'ok';
    $label = $s === 'menunggu_cek_plp' ? 'Menunggu Cek PLP' : ucfirst($s);
    return '<span class="chip ' . $c . '">' . e($label) . '</span>';
}

function req_table($rows, $tipe = '', $aksi = false, $approverLabel = 'Tinjau') {
    if (!$rows) { echo '<p class="empty">Belum ada data pengajuan.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Pengaju</th><th>Barang</th><th>Ukuran / Mhs & Pcs</th><th>Total Kebutuhan</th><th>Perlu Dipesan</th><th>Stok Min</th><th>Keterangan</th><th>Status</th>' . ($aksi ? '<th>' . e($approverLabel) . '</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        $extra = '';
        if (!empty($r['disetujui_oleh'])) {
            $extra .= '<br><small class="muted">Ditinjau oleh: ' . e($r['disetujui_oleh']) . '</small>';
        }
        if (($r['catatan'] ?? '') !== '') {
            $extra .= '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>';
        }
        $ukMhs = isset($r['ukuran_per_mhs']) && $r['ukuran_per_mhs'] > 0
            ? e($r['ukuran_per_mhs'] . ' ' . $r['satuan'] . '/mhs') . '<br><small class="muted">1 pcs = ' . e(($r['ukuran_pcs'] ?? 1) . ' ' . $r['satuan']) . ' (' . e($r['jml_pcs'] ?? 1) . ' pcs)</small>'
            : '-';
        $pesanTxt = isset($r['pesan']) ? e($r['pesan'] . ' ' . $r['satuan']) : '-';
        $minTxt = isset($r['minimum_baru']) ? e($r['minimum_baru'] . ' ' . $r['satuan']) : '-';
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['nama']) . ' (' . e($r['kode']) . ')</td><td>' . $ukMhs . '</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td><strong>' . $pesanTxt . '</strong></td><td>' . $minTxt . '</td><td>' . e($r['ket']) . $extra . '</td><td>' . chip($r['status']) . '</td>';
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
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Dosen</th><th>Nama Bahan</th><th>Ukuran/Mhs & Pcs</th><th>Total (1 Smt)</th><th>Kelas</th><th>Sub Praktikum</th><th>Status</th>' . ($aksi ? '<th>Tinjau & Set Stok Min (PLP)</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        $cat = ($r['catatan'] ?? '') !== '' ? '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>' : '';
        $ukInfo = isset($r['ukuran_per_mhs']) && $r['ukuran_per_mhs'] > 0
            ? e($r['ukuran_per_mhs'] . ' ' . $r['satuan'] . ' × ' . ($r['jml_mhs'] ?? 1) . ' mhs') . '<br><small class="muted">1 pcs = ' . e(($r['ukuran_pcs'] ?? 1) . ' ' . $r['satuan']) . ' (≈ ' . e($r['jml_pcs'] ?? 1) . ' pcs)</small>'
            : '-';
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['nama']) . ' (' . e($r['kode']) . ')</td><td>' . $ukInfo . '</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td>' . e($r['kelas'] ?? '-') . '</td><td>' . e($r['sub_praktikum'] ?? '-') . $cat . '</td><td>' . chip($r['status']) . '</td>';
        if ($aksi) {
            echo '<td>';
            if ($r['status'] === 'menunggu') {
                echo '<form class="inline" method="post">' . field_csrf()
                    . '<input type="hidden" name="tipe" value="pengajuan_dosen">'
                    . '<input type="hidden" name="id" value="' . e($r['id']) . '">'
                    . '<input type="number" name="minimum_baru" min="0" placeholder="Stok Min" title="Stok minimum barang" style="width:90px">'
                    . '<input name="catatan" placeholder="Catatan PLP" aria-label="Catatan">'
                    . '<button class="btn" name="aksi" value="setujui">Setujui</button>'
                    . '<button class="btn sec" name="aksi" value="tolak">Tolak</button>'
                    . '</form>';
            } else { echo '-'; }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
}

function mhs_table($rows, $aksi = false, $allowAmbilSisa = false) {
    if (!$rows) { echo '<p class="empty">Belum ada konfirmasi praktikum & pengambilan bahan dari mahasiswa.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tgl Otomatis</th><th>Mahasiswa</th><th>Kelas</th><th>Sub Praktikum</th><th>Bahan Diambil</th><th>Jenis Ambil</th><th>Diambil / Total Jatah</th><th>Sisa Belum Diambil</th><th>Status</th>' . ($aksi ? '<th>Tinjau</th>' : '') . ($allowAmbilSisa ? '<th>Ambil Sisa</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        $extra = '';
        if (!empty($r['disetujui_oleh'])) {
            $extra .= '<br><small class="muted">Oleh: ' . e($r['disetujui_oleh']) . '</small>';
        }
        if (($r['catatan'] ?? '') !== '') {
            $extra .= '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>';
        }
        $tipeAmbil = $r['tipe_ambil'] ?? 'penuh';
        $totalJatah = $r['total_jatah'] ?? $r['jumlah'];
        $sisaAmbil = (int)($r['sisa_ambil'] ?? 0);
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . e($r['kelas'] ?? '-') . '</td><td>' . e($r['sub_praktikum'] ?? $r['ket'] ?? '-') . $extra . '</td><td>' . e($r['nama']) . '</td><td>' . chip($tipeAmbil) . '</td><td><strong>' . e($r['jumlah']) . '</strong> / ' . e($totalJatah . ' ' . $r['satuan']) . '</td><td>' . ($sisaAmbil > 0 ? '<span class="chip warn">' . e($sisaAmbil . ' ' . $r['satuan']) . '</span>' : '<span class="chip ok">Lunas / Penuh</span>') . '</td><td>' . chip($r['status']) . '</td>';
        if ($aksi) {
            echo '<td>';
            if ($r['status'] === 'menunggu') {
                echo '<form class="inline" method="post">' . field_csrf() . '<input type="hidden" name="tipe" value="permintaan"><input type="hidden" name="id" value="' . e($r['id']) . '"><input name="catatan" placeholder="Catatan" aria-label="Catatan"><button class="btn" name="aksi" value="setujui">Setujui</button><button class="btn sec" name="aksi" value="tolak">Tolak</button></form>';
            } else { echo '-'; }
            echo '</td>';
        }
        if ($allowAmbilSisa) {
            echo '<td>';
            if ($sisaAmbil > 0 && $r['status'] === 'disetujui') {
                echo '<form class="inline" method="post">' . field_csrf()
                    . '<input type="hidden" name="aksi_mhs" value="ambil_sisa">'
                    . '<input type="hidden" name="parent_id" value="' . e($r['id']) . '">'
                    . '<input type="number" name="jumlah_sisa" min="1" max="' . e($sisaAmbil) . '" value="' . e($sisaAmbil) . '" style="width:75px" required>'
                    . '<button class="btn" type="submit">Ambil Sisa</button></form>';
            } else { echo '-'; }
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</table></div>';
}

function render_rekap_kehadiran_praktikum($permintaan, $allowBulkApprove = false) {
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
                'menunggu_mhs' => 0,
                'total_ambil' => 0,
                'satuan' => $r['satuan'] ?? '',
            ];
        }
        $rekap[$key]['total_mhs'] += 1;
        if ($r['status'] === 'disetujui') {
            $rekap[$key]['disetujui_mhs'] += 1;
        } elseif ($r['status'] === 'menunggu') {
            $rekap[$key]['menunggu_mhs'] += 1;
        }
        $rekap[$key]['total_ambil'] += (int)($r['jumlah'] ?? 0);
    }
    if (!$rekap) return;
    echo '<h2>Rekap Jumlah Mahasiswa Praktikum per Hari' . ($allowBulkApprove ? ' & Approval Sekaligus 1 Praktikum' : '') . '</h2>';
    echo '<div class="tablewrap"><table><tr><th>Tanggal Praktikum</th><th>Kelas</th><th>Sub Praktikum</th><th>Bahan</th><th>Total Pengambilan</th><th>Mahasiswa Mengonfirmasi</th><th>Mahasiswa Disetujui</th>' . ($allowBulkApprove ? '<th>Approval Sekaligus 1 Praktikum</th>' : '') . '</tr>';
    foreach (array_reverse($rekap) as $rk) {
        echo '<tr><td>' . e($rk['tanggal']) . '</td><td>' . e($rk['kelas']) . '</td><td>' . e($rk['sub_praktikum']) . '</td><td>' . e($rk['bahan']) . '</td><td>' . e($rk['total_ambil'] . ' ' . $rk['satuan']) . '</td><td><strong>' . e($rk['total_mhs']) . ' mahasiswa</strong></td><td><span class="chip ok">' . e($rk['disetujui_mhs']) . ' mahasiswa</span></td>';
        if ($allowBulkApprove) {
            echo '<td>';
            if ($rk['menunggu_mhs'] > 0) {
                echo '<form class="inline" method="post">' . field_csrf()
                    . '<input type="hidden" name="aksi_bulk" value="setujui_praktikum">'
                    . '<input type="hidden" name="tanggal" value="' . e($rk['tanggal']) . '">'
                    . '<input type="hidden" name="kelas" value="' . e($rk['kelas']) . '">'
                    . '<input type="hidden" name="sub_praktikum" value="' . e($rk['sub_praktikum']) . '">'
                    . '<input name="catatan" placeholder="Catatan praktikum" style="width:140px">'
                    . '<button class="btn" type="submit">Setujui Sekaligus (' . e($rk['menunggu_mhs']) . ' Mhs)</button>'
                    . '</form>';
            } else {
                echo '<span class="chip ok">Semua Disetujui</span>';
            }
            echo '</td>';
        }
        echo '</tr>';
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
            <div><dt>Satuan / Ukuran Pcs</dt><dd><?= e($i['satuan']) ?> (1 pcs = <?= e($i['ukuran_pcs'] ?? 1) ?> <?= e($i['satuan']) ?>)</dd></div>
            <div><dt>Minimum</dt><dd><?= e($i['minimum']) ?> <?= e($i['satuan']) ?></dd></div>
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
    $role = norm_role($_SESSION['user']['role']);
    $redirect = role_url('stok');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'plp') {
        check_csrf();
        $d = db();
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $minBaru = post_int('minimum');
        if ($n !== null && $minBaru >= 0) {
            $d['barang'][$n]['minimum'] = $minBaru;
            save($d);
            go($redirect, "Stok minimum {$d['barang'][$n]['nama']} diperbarui menjadi $minBaru {$d['barang'][$n]['satuan']}.");
        }
        go($redirect, 'Data stok minimum tidak valid.', 'bad');
    }
    $title = 'Stok Barang'; $active = 'stok';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="tablewrap"><table>
    <tr><th>Kode</th><th>Nama barang</th><th>Spesifikasi</th><th>Ukuran / Pcs</th><th>Rak</th><th>Satuan</th><th>Minimum</th><th>Stok</th><th>Status</th><?= $role === 'plp' ? '<th>Atur Stok Min (PLP)</th>' : '' ?></tr>
    <?php foreach (items() as $i): ?>
    <tr>
        <td><?= e($i['kode']) ?></td><td><?= e($i['nama']) ?></td><td><?= e($i['spesifikasi']) ?></td>
        <td>1 pcs = <?= e(($i['ukuran_pcs'] ?? 1) . ' ' . $i['satuan']) ?></td>
        <td><?= e($i['rak']) ?></td><td><?= e($i['satuan']) ?></td><td><?= e($i['minimum']) ?></td><td><?= e($i['stok']) ?></td>
        <td><span class="chip <?= low($i) ? 'bad' : 'ok' ?>"><?= low($i) ? 'Di bawah minimum' : 'Aman' ?></span></td>
        <?php if ($role === 'plp'): ?>
        <td>
            <form class="inline" method="post">
                <?= field_csrf() ?>
                <input type="hidden" name="kode" value="<?= e($i['kode']) ?>">
                <input type="number" name="minimum" min="0" value="<?= e($i['minimum']) ?>" style="width:80px" required>
                <button class="btn" type="submit">Simpan</button>
            </form>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table></div>
    <?php
    require __DIR__ . '/partials/footer.php';
}

function fitur_rak() {
    $d = db();
    $role = norm_role($_SESSION['user']['role']);
    $redirect = role_url('rak');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'teknisi') {
        check_csrf();
        $aksi = $_POST['aksi_rak'] ?? '';
        if ($aksi === 'kapasitas') {
            $rk = strtoupper(trim($_POST['nama_rak'] ?? ''));
            $kap = post_int('kapasitas');
            if ($rk === '' || $kap < 1) go($redirect, 'Nama rak dan kapasitas maksimum harus diisi (> 0).', 'bad');
            $d['rak_kapasitas'][$rk] = $kap;
            save($d);
            go($redirect, "Kapasitas Rak $rk berhasil diatur menjadi $kap.");
        } elseif ($aksi === 'pindah_rak') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
            $rakTujuan = strtoupper(trim($_POST['rak_tujuan'] ?? ''));
            if ($n === null || $rakTujuan === '') go($redirect, 'Pilih barang dan rak tujuan.', 'bad');
            if (!isset($d['rak_kapasitas'][$rakTujuan])) $d['rak_kapasitas'][$rakTujuan] = 500;
            $infoTujuan = rak_info($d, $rakTujuan);
            $stokBarang = (int)$d['barang'][$n]['stok'];
            if ($d['barang'][$n]['rak'] !== $rakTujuan && ($infoTujuan['terpakai'] + $stokBarang) > $infoTujuan['kapasitas']) {
                go($redirect, "Kapasitas Rak $rakTujuan tidak mencukupi! Sisa kapasitas: {$infoTujuan['sisa']}, stok barang: $stokBarang.", 'bad');
            }
            $d['barang'][$n]['rak'] = $rakTujuan;
            save($d);
            go($redirect, "Barang {$d['barang'][$n]['nama']} dipindahkan ke Rak $rakTujuan.");
        }
    }

    $byRak = [];
    foreach (array_keys($d['rak_kapasitas']) as $rName) {
        $byRak[$rName] = [];
    }
    foreach ($d['barang'] as $i) {
        $byRak[$i['rak']][] = $i;
    }
    ksort($byRak);
    $title = 'Rak & Status Kapasitas'; $active = 'rak';
    require __DIR__ . '/partials/header.php';

    if ($role === 'teknisi'): ?>
    <div class="grid2">
        <form class="box form" method="post">
            <?= field_csrf() ?>
            <input type="hidden" name="aksi_rak" value="kapasitas">
            <label for="nama_rak">Nama Rak (Tambah / Ubah Kapasitas Rak)</label>
            <input id="nama_rak" name="nama_rak" placeholder="Contoh: A-01" required>
            <label for="kapasitas">Kapasitas Maksimum Rak (Total Unit/Satuan)</label>
            <input id="kapasitas" name="kapasitas" type="number" min="1" value="500" required>
            <button class="btn" type="submit">Simpan Kapasitas Rak</button>
        </form>
        <form class="box form" method="post">
            <?= field_csrf() ?>
            <input type="hidden" name="aksi_rak" value="pindah_rak">
            <label for="kode">Penempatan Bahan/Alat ke Rak</label>
            <select id="kode" name="kode" required>
                <option value="">Pilih barang</option>
                <?php foreach ($d['barang'] as $b): ?>
                <option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . ') - Stok: ' . $b['stok'] . ' ' . $b['satuan'] . ' [Rak ' . $b['rak'] . ']') ?></option>
                <?php endforeach; ?>
            </select>
            <label for="rak_tujuan">Pilih Rak Tujuan</label>
            <select id="rak_tujuan" name="rak_tujuan" required>
                <?php foreach (array_keys($byRak) as $rkName): $inf = rak_info($d, $rkName); ?>
                <option value="<?= e($rkName) ?>">Rak <?= e($rkName) ?> (Terisi: <?= e($inf['terpakai'] . '/' . $inf['kapasitas']) ?>, Sisa: <?= e($inf['sisa']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Simpan Penempatan Rak</button>
        </form>
    </div>
    <?php endif; ?>

    <?php foreach ($byRak as $rak => $rows):
        $rkInfo = rak_info($d, $rak);
    ?>
    <section class="rak">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <h2>Rak <?= e($rak) ?> <small><?= count($rows) ?> jenis barang</small></h2>
            <div>
                <span class="muted" style="font-size:13px;margin-right:8px">
                    Total Isi Rak: <strong><?= e($rkInfo['terpakai']) ?></strong> / <?= e($rkInfo['kapasitas']) ?> (Sisa kapasitas: <strong><?= e($rkInfo['sisa']) ?></strong>)
                </span>
                <span class="chip <?= e($rkInfo['chip']) ?>">Status Kapasitas: <?= e($rkInfo['status']) ?> (<?= e($rkInfo['pct']) ?>%)</span>
            </div>
        </div>
        <div class="bar" aria-hidden="true"><i style="width:<?= e($rkInfo['pct']) ?>%;background:<?= $rkInfo['chip'] === 'bad' ? '#C0392B' : ($rkInfo['chip'] === 'warn' ? '#F7931E' : 'var(--blue)') ?>"></i></div>
        <?php if (!$rows): ?>
            <p class="muted" style="margin:6px 0 0;font-size:13px">Rak ini masih kosong.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($rows as $i): ?>
                <li>
                    <strong><?= e($i['nama']) ?></strong> <span class="muted">(<?= e($i['kode']) ?>)</span>
                    — Jumlah di rak: <strong><?= e($i['stok'] . ' ' . $i['satuan']) ?></strong>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
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
        $rakPilih = trim($_POST['rak'] ?? '');
        if ($n === null || $jml < 1) go($redirect, 'Pilih barang dan isi jumlah lebih dari 0.', 'bad');

        $targetRak = $rakPilih !== '' ? $rakPilih : $d['barang'][$n]['rak'];
        $rkInfo = rak_info($d, $targetRak);
        // Logika penjumlahan bahan/alat ke dalam rak dan validasi kapasitas rak
        if (($rkInfo['terpakai'] + $jml) > $rkInfo['kapasitas']) {
            go($redirect, "Gagal menambahkan ke Rak $targetRak! Kapasitas maksimum rak ({$rkInfo['kapasitas']}) terlampaui. Terisi saat ini: {$rkInfo['terpakai']}, sisa kapasitas: {$rkInfo['sisa']}.", 'bad');
        }

        $d['barang'][$n]['rak'] = $targetRak;
        $d['barang'][$n]['stok'] += $jml;
        $totalRakBaru = rak_terpakai($d['barang'], $targetRak);
        add_log($d, 'masuk', $d['barang'][$n]['kode'], $jml, "Rak $targetRak (Total isi rak: $totalRakBaru/{$rkInfo['kapasitas']}) - " . trim($_POST['catatan'] ?? ''));
        save($d);
        go($redirect, "Stok {$d['barang'][$n]['nama']} bertambah $jml {$d['barang'][$n]['satuan']} di Rak $targetRak (Kapasitas terpakai: $totalRakBaru/{$rkInfo['kapasitas']}).");
    }
    $title = 'Barang Masuk & Penjumlahan ke Rak'; $active = 'masuk'; $d = db();
    $riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'masuk')), 0, 10);
    require __DIR__ . '/partials/header.php';
    ?>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="kode">Barang</label>
        <select id="kode" name="kode" required><option value="">Pilih barang</option>
        <?php foreach ($d['barang'] as $b): $rk = rak_info($d, $b['rak']); ?>
            <option value="<?= e($b['kode']) ?>" data-rak="<?= e($b['rak']) ?>">
                <?= e($b['nama'] . ' (' . $b['kode'] . '), stok ' . $b['stok'] . ' ' . $b['satuan'] . ' — Rak ' . $b['rak'] . ' [Sisa kapasitas rak: ' . $rk['sisa'] . ']') ?>
            </option>
        <?php endforeach; ?>
        </select>
        <div class="grid2">
            <div>
                <label for="jumlah">Jumlah masuk (dijumlahkan ke dalam rak)</label>
                <input id="jumlah" name="jumlah" type="number" min="1" required>
            </div>
            <div>
                <label for="rak">Simpan ke Rak</label>
                <select id="rak" name="rak">
                    <?php foreach (array_keys($d['rak_kapasitas']) as $rName): $rk = rak_info($d, $rName); ?>
                    <option value="<?= e($rName) ?>">Rak <?= e($rName) ?> — Terpakai <?= e($rk['terpakai'] . '/' . $rk['kapasitas']) ?> (Status: <?= e($rk['status']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label for="catatan">Catatan (asal barang, nomor pembelian)</label><textarea id="catatan" name="catatan" rows="2"></textarea>
        <button class="btn" type="submit">Catat barang masuk & masukkan ke rak</button>
    </form>
    <h2>Riwayat terbaru</h2>
    <?php include __DIR__ . '/partials/log.php'; ?>
    <script>
    const selBarang = document.getElementById('kode'), selRak = document.getElementById('rak');
    if (selBarang && selRak) {
        selBarang.addEventListener('change', () => {
            const opt = selBarang.selectedOptions[0];
            if (opt && opt.dataset.rak) selRak.value = opt.dataset.rak;
        });
    }
    </script>
    <?php
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

// Teknisi Point 5: Fitur Stock Opname (Teknisi sebagai pendata stok barang fisik di gudang)
function fitur_stockopname() {
    $redirect = role_url('stockopname');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $stokFisik = filter_var($_POST['stok_fisik'] ?? '', FILTER_VALIDATE_INT);
        $kondisi = trim($_POST['kondisi'] ?? 'Sesuai / Baik');
        $catatan = trim($_POST['catatan'] ?? '');
        if ($n === null || $stokFisik === false || $stokFisik < 0) {
            go($redirect, 'Pilih barang dan isi jumlah stok fisik (>= 0).', 'bad');
        }
        $b = $d['barang'][$n];
        $selisih = $stokFisik - (int)$b['stok'];
        $d['stockopname'][] = [
            'id' => count($d['stockopname']) + 1,
            'waktu' => date('Y-m-d H:i'),
            'oleh' => $_SESSION['user']['nama'],
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'rak' => $b['rak'],
            'stok_sistem' => (int)$b['stok'],
            'stok_fisik' => (int)$stokFisik,
            'selisih' => $selisih,
            'kondisi' => $kondisi,
            'catatan' => $catatan,
            'status' => 'menunggu_cek_plp',
            'dicek_oleh' => '',
            'catatan_plp' => '',
        ];
        save($d);
        go($redirect, "Data Stock Opname {$b['nama']} berhasil dicatat dan dikirim ke PLP untuk dicek secara real-time.");
    }
    $title = 'Stock Opname (Pendataan Stok Fisik oleh Teknisi)'; $active = 'stockopname'; $d = db();
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">Teknisi mendata kondisi dan jumlah stok fisik barang secara langsung. Hasil pendataan akan dicek secara real-time oleh PLP.</p>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="kode">Pilih Barang yang Didata</label>
        <select id="kode" name="kode" required>
            <option value="">Pilih barang</option>
            <?php foreach ($d['barang'] as $b): ?>
            <option value="<?= e($b['kode']) ?>" data-stok="<?= e($b['stok']) ?>" data-satuan="<?= e($b['satuan']) ?>">
                <?= e($b['nama'] . ' (' . $b['kode'] . ') - Rak ' . $b['rak'] . ' | Stok Sistem: ' . $b['stok'] . ' ' . $b['satuan']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <div class="grid2">
            <div>
                <label for="stok_fisik">Stok Fisik Aktual (Hasil Hitung Teknisi)</label>
                <input id="stok_fisik" name="stok_fisik" type="number" min="0" required>
            </div>
            <div>
                <label for="kondisi">Kondisi Fisik Barang</label>
                <select id="kondisi" name="kondisi">
                    <option value="Sesuai / Baik">Sesuai / Baik</option>
                    <option value="Selisih Kurang">Selisih Kurang</option>
                    <option value="Selisih Lebih">Selisih Lebih</option>
                    <option value="Sebagian Rusak / Aus">Sebagian Rusak / Aus</option>
                </select>
            </div>
        </div>
        <label for="catatan">Catatan Pendataan Teknisi</label>
        <input id="catatan" name="catatan" placeholder="Contoh: Dihitung ulang di Rak A-02, kondisi baik">
        <button class="btn" type="submit">Simpan Data Stock Opname</button>
    </form>

    <h2>Riwayat Pendataan Stock Opname</h2>
    <?php if (!$d['stockopname']): ?><p class="empty">Belum ada catatan stock opname.</p><?php else: ?>
    <div class="tablewrap"><table>
        <tr><th>No</th><th>Waktu</th><th>Barang (Rak)</th><th>Stok Sistem</th><th>Stok Fisik (Teknisi)</th><th>Selisih</th><th>Kondisi</th><th>Catatan</th><th>Status Verifikasi PLP</th></tr>
        <?php foreach (array_reverse($d['stockopname']) as $so): ?>
        <tr>
            <td><?= e($so['id']) ?></td>
            <td><?= e($so['waktu']) ?></td>
            <td><?= e($so['nama'] . ' (' . $so['kode'] . ') - Rak ' . $so['rak']) ?></td>
            <td><?= e($so['stok_sistem'] . ' ' . $so['satuan']) ?></td>
            <td><strong><?= e($so['stok_fisik'] . ' ' . $so['satuan']) ?></strong></td>
            <td><span class="chip <?= $so['selisih'] == 0 ? 'ok' : 'warn' ?>"><?= ($so['selisih'] > 0 ? '+' : '') . e($so['selisih'] . ' ' . $so['satuan']) ?></span></td>
            <td><?= e($so['kondisi']) ?></td>
            <td><?= e($so['catatan']) ?><?= !empty($so['catatan_plp']) ? '<br><small class="muted">PLP: ' . e($so['catatan_plp']) . '</small>' : '' ?></td>
            <td><?= chip($so['status']) ?><?= !empty($so['dicek_oleh']) ? '<br><small class="muted">Oleh: ' . e($so['dicek_oleh']) . '</small>' : '' ?></td>
        </tr>
        <?php endforeach; ?>
    </table></div>
    <?php endif;
    require __DIR__ . '/partials/footer.php';
}

// PLP Point 3: Cek Barang Real-Time oleh PLP agar data yang dimasukkan Teknisi sesuai kondisi asli
function fitur_cek_barang() {
    $redirect = role_url('cek_barang');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $jenisCek = $_POST['jenis_cek'] ?? '';
        if ($jenisCek === 'stockopname') {
            $n = find_idx($d['stockopname'], 'id', post_int('id'));
            $aksi = $_POST['aksi'] ?? '';
            if ($n === null || $d['stockopname'][$n]['status'] !== 'menunggu_cek_plp') {
                go($redirect, 'Data stock opname tidak ditemukan atau sudah diverifikasi.', 'bad');
            }
            $so = &$d['stockopname'][$n];
            $so['catatan_plp'] = trim($_POST['catatan_plp'] ?? '');
            $so['dicek_oleh'] = $_SESSION['user']['nama'];
            if ($aksi === 'sesuaikan') {
                $idxB = find_idx($d['barang'], 'kode', $so['kode']);
                if ($idxB !== null) {
                    $d['barang'][$idxB]['stok'] = (int)$so['stok_fisik'];
                }
                $so['status'] = 'terverifikasi';
                add_log($d, 'opname', $so['kode'], $so['stok_fisik'], "Cek Real-Time PLP: Stok disesuaikan ke kondisi fisik asli ({$so['stok_fisik']} {$so['satuan']})", true);
                save($d);
                go($redirect, "Data Stock Opname diverifikasi PLP. Stok {$so['nama']} kini disesuaikan dengan kondisi asli ({$so['stok_fisik']} {$so['satuan']}).");
            } else {
                $so['status'] = 'ditolak';
                save($d);
                go($redirect, 'Data Stock Opname teknisi ditolak untuk dihitung ulang.', 'warn');
            }
        } elseif ($jenisCek === 'log_teknisi') {
            $idxL = find_idx($d['log'], 'id', post_int('log_id'));
            if ($idxL !== null) {
                $d['log'][$idxL]['dicek_plp'] = true;
                $d['log'][$idxL]['dicek_oleh'] = $_SESSION['user']['nama'];
                save($d);
                go($redirect, 'Data transaksi teknisi telah dicek dan dikonfirmasi sesuai kondisi asli oleh PLP.');
            }
        }
    }
    $title = 'Cek Barang Real-Time (Verifikasi Data Teknisi oleh PLP)'; $active = 'cek_barang'; $d = db();
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">PLP memverifikasi data stok fisik (Stock Opname) dan transaksi barang yang dimasukkan oleh Teknisi secara real-time agar sesuai dengan kondisi aslinya di lapangan.</p>

    <h2>1. Verifikasi Stock Opname Teknisi (Real-Time)</h2>
    <?php if (!$d['stockopname']): ?><p class="empty">Belum ada pendataan stock opname dari teknisi.</p><?php else: ?>
    <div class="tablewrap"><table>
        <tr><th>No</th><th>Waktu</th><th>Teknisi</th><th>Barang (Rak)</th><th>Stok Sistem</th><th>Stok Fisik (Teknisi)</th><th>Selisih</th><th>Kondisi</th><th>Status</th><th>Verifikasi Real-Time PLP</th></tr>
        <?php foreach (array_reverse($d['stockopname']) as $so): ?>
        <tr>
            <td><?= e($so['id']) ?></td>
            <td><?= e($so['waktu']) ?></td>
            <td><?= e($so['oleh']) ?></td>
            <td><?= e($so['nama'] . ' (' . $so['kode'] . ') - Rak ' . $so['rak']) ?></td>
            <td><?= e($so['stok_sistem'] . ' ' . $so['satuan']) ?></td>
            <td><strong><?= e($so['stok_fisik'] . ' ' . $so['satuan']) ?></strong></td>
            <td><span class="chip <?= $so['selisih'] == 0 ? 'ok' : 'warn' ?>"><?= ($so['selisih'] > 0 ? '+' : '') . e($so['selisih'] . ' ' . $so['satuan']) ?></span></td>
            <td><?= e($so['kondisi']) ?><?= $so['catatan'] !== '' ? '<br><small class="muted">' . e($so['catatan']) . '</small>' : '' ?></td>
            <td><?= chip($so['status']) ?></td>
            <td>
                <?php if ($so['status'] === 'menunggu_cek_plp'): ?>
                <form class="inline" method="post">
                    <?= field_csrf() ?>
                    <input type="hidden" name="jenis_cek" value="stockopname">
                    <input type="hidden" name="id" value="<?= e($so['id']) ?>">
                    <input name="catatan_plp" placeholder="Catatan cek PLP" style="width:120px">
                    <button class="btn" name="aksi" value="sesuaikan">Sesuai (Update Stok)</button>
                    <button class="btn sec" name="aksi" value="tolak">Hitung Ulang</button>
                </form>
                <?php else: ?>
                <small class="muted">Dicek oleh: <?= e($so['dicek_oleh'] ?: 'PLP') ?></small>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table></div>
    <?php endif; ?>

    <h2>2. Cek Real-Time Kondisi Barang & Rak Saat Ini</h2>
    <div class="tablewrap"><table>
        <tr><th>Kode</th><th>Nama Barang</th><th>Rak & Status Kapasitas</th><th>Stok Sistem Aktual</th><th>Stok Minimum</th><th>Kondisi Stok</th></tr>
        <?php foreach ($d['barang'] as $b): $rk = rak_info($d, $b['rak']); ?>
        <tr>
            <td><?= e($b['kode']) ?></td>
            <td><?= e($b['nama']) ?> <br><small class="muted"><?= e($b['spesifikasi']) ?></small></td>
            <td>Rak <?= e($b['rak']) ?> <span class="chip <?= e($rk['chip']) ?>"><?= e($rk['terpakai'] . '/' . $rk['kapasitas'] . ' (' . $rk['status'] . ')') ?></span></td>
            <td><strong><?= e($b['stok'] . ' ' . $b['satuan']) ?></strong> (≈ <?= ceil($b['stok'] / max(1, $b['ukuran_pcs'] ?? 1)) ?> pcs)</td>
            <td><?= e($b['minimum'] . ' ' . $b['satuan']) ?></td>
            <td><span class="chip <?= low($b) ? 'bad' : 'ok' ?>"><?= low($b) ? 'Di bawah minimum' : 'Sesuai / Aman' ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table></div>

    <h2>3. Verifikasi Input Barang Masuk/Keluar oleh Teknisi</h2>
    <?php if (!$d['log']): ?><p class="empty">Belum ada log input barang dari teknisi.</p><?php else: ?>
    <div class="tablewrap"><table>
        <tr><th>Waktu</th><th>Jenis</th><th>Kode Barang</th><th>Jumlah</th><th>Diinput Oleh</th><th>Catatan</th><th>Status Cek PLP</th></tr>
        <?php foreach (array_reverse($d['log']) as $idx => $l):
            $logId = $l['id'] ?? ($idx + 1);
            $checked = !empty($l['dicek_plp']);
        ?>
        <tr>
            <td><?= e($l['waktu']) ?></td>
            <td><span class="chip <?= $l['jenis'] === 'keluar' ? 'warn' : 'ok' ?>"><?= e(ucfirst($l['jenis'])) ?></span></td>
            <td><?= e($l['kode']) ?></td>
            <td><?= e($l['jumlah']) ?></td>
            <td><?= e($l['oleh']) ?></td>
            <td><?= e($l['catatan']) ?></td>
            <td>
                <?php if ($checked): ?>
                    <span class="chip ok">Terverifikasi (<?= e($l['dicek_oleh'] ?? 'PLP') ?>)</span>
                <?php else: ?>
                    <form class="inline" method="post">
                        <?= field_csrf() ?>
                        <input type="hidden" name="jenis_cek" value="log_teknisi">
                        <input type="hidden" name="log_id" value="<?= e($logId) ?>">
                        <button class="btn" type="submit">Konfirmasi Sesuai</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table></div>
    <?php endif;
    require __DIR__ . '/partials/footer.php';
}

// PLP Point 1, 2 & Kalab Point 1, 3:
// PLP mengajukan kebutuhan bahan ke Kepala Lab (mengambil data dari pengajuan yang sudah disetujui PLP,
// menampilkan per pcs berapa ukuran per mahasiswa, dan bisa input stok minimum pada setiap barang yang diajukan)
function fitur_pengajuan() {
    $redirect = role_url('pengajuan');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $pdId = post_int('pengajuan_dosen_id');
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $mk = trim($_POST['mata_kuliah'] ?? '');
        $mhs = post_int('mahasiswa');
        $per = post_int('per_mhs');
        $ukuranPcs = max(1, post_int('ukuran_pcs'));
        $minBaru = filter_var($_POST['minimum_baru'] ?? '', FILTER_VALIDATE_INT);
        $tol = post_int('tol');

        if ($pdId > 0) {
            $idxPd = find_idx($d['pengajuan_dosen'], 'id', $pdId);
            if ($idxPd !== null) {
                $pd = $d['pengajuan_dosen'][$idxPd];
                $n = find_idx($d['barang'], 'kode', $pd['kode']);
                if ($mk === '') $mk = $pd['sub_praktikum'] . ' (' . $pd['kelas'] . ')';
                if ($mhs < 1) $mhs = (int)($pd['jml_mhs'] ?? 1);
                if ($per < 1) $per = (int)($pd['ukuran_per_mhs'] ?? $pd['jumlah']);
                $d['pengajuan_dosen'][$idxPd]['diajukan_ke_kalab'] = true;
            }
        }

        if ($n === null || $mk === '' || $mhs < 1 || $per < 1 || $tol < 0 || $tol > 100 || $minBaru === false || $minBaru < 0) {
            go($redirect, 'Lengkapi data pengajuan, ukuran per mahasiswa, ukuran per pcs, stok minimum, dan toleransi (0-100%).', 'bad');
        }

        // Update stok minimum & ukuran per pcs pada barang yang diajukan oleh PLP
        $d['barang'][$n]['minimum'] = (int)$minBaru;
        $d['barang'][$n]['ukuran_pcs'] = $ukuranPcs;
        $b = $d['barang'][$n];

        [$total, $pesan] = kebutuhan($mhs, $per, $tol, $b['stok']);
        $jmlPcs = (int)ceil($total / $ukuranPcs);
        $pesanPcs = (int)ceil($pesan / $ukuranPcs);

        $d['pengajuan'][] = [
            'id' => count($d['pengajuan']) + 1,
            'tanggal' => date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'pengajuan_dosen_id' => $pdId,
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'ukuran_pcs' => $ukuranPcs,
            'ukuran_per_mhs' => $per,
            'jml_mhs' => $mhs,
            'jml_pcs' => $jmlPcs,
            'minimum_baru' => (int)$minBaru,
            'jumlah' => $total,
            'stok' => $b['stok'],
            'pesan' => $pesan,
            'ket' => "$mk: $mhs mhs × $per {$b['satuan']} (+tol $tol% = $total {$b['satuan']} / $jmlPcs pcs @{$ukuranPcs} {$b['satuan']}), stok {$b['stok']}, perlu dipesan $pesan {$b['satuan']} ($pesanPcs pcs), stok min: $minBaru {$b['satuan']}",
            'status' => 'menunggu',
            'disetujui_oleh' => '',
            'catatan' => '',
        ];
        save($d);
        go($redirect, "Pengajuan kebutuhan berhasil dikirim oleh PLP ke Kepala Lab. Kebutuhan: $total {$b['satuan']} ($jmlPcs pcs), perlu dipesan: $pesan {$b['satuan']}, stok minimum diatur ke $minBaru {$b['satuan']}.");
    }

    $title = 'Pengajuan Kebutuhan Bahan (PLP ke Kepala Lab)'; $active = 'pengajuan'; $d = db();
    $disetujuiPlp = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui');
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">PLP menyusun pengajuan kebutuhan bahan berdasarkan pengajuan yang telah disetujui PLP (lengkap dengan detail ukuran per mahasiswa per pcs & penetapan stok minimum barang) untuk di-<em>approve</em> oleh <strong>Kepala Lab</strong>.</p>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="pengajuan_dosen_id">Ambil Data dari Pengajuan Dosen yang Sudah Disetujui PLP</label>
        <select id="pengajuan_dosen_id" name="pengajuan_dosen_id">
            <option value="">-- Pilih Pengajuan Dosen yang Disetujui PLP --</option>
            <?php foreach ($disetujuiPlp as $pd):
                $idxB = find_idx($d['barang'], 'kode', $pd['kode']);
                $minSkrg = $idxB !== null ? $d['barang'][$idxB]['minimum'] : 100;
                $ukPcsSkrg = $pd['ukuran_pcs'] ?? ($idxB !== null ? ($d['barang'][$idxB]['ukuran_pcs'] ?? 100) : 100);
            ?>
            <option value="<?= e($pd['id']) ?>"
                data-kode="<?= e($pd['kode']) ?>"
                data-mk="<?= e($pd['sub_praktikum'] . ' - ' . $pd['kelas']) ?>"
                data-mhs="<?= e($pd['jml_mhs'] ?? 1) ?>"
                data-per="<?= e($pd['ukuran_per_mhs'] ?? $pd['jumlah']) ?>"
                data-pcs="<?= e($ukPcsSkrg) ?>"
                data-min="<?= e($minSkrg) ?>">
                #<?= e($pd['id']) ?> - <?= e($pd['sub_praktikum']) ?> (<?= e($pd['kelas']) ?>) — <?= e($pd['nama']) ?>: <?= e(($pd['jml_mhs'] ?? 1) . ' mhs × ' . ($pd['ukuran_per_mhs'] ?? $pd['jumlah']) . ' ' . $pd['satuan']) ?> <?= !empty($pd['diajukan_ke_kalab']) ? '[Sudah Diajukan ke Kalab]' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>

        <div class="grid2">
            <div>
                <label for="mata_kuliah">Praktikum & Kelas</label>
                <input id="mata_kuliah" name="mata_kuliah" placeholder="Otomatis terisi saat pilih pengajuan di atas" required>
            </div>
            <div>
                <label for="kode">Barang</label>
                <select id="kode" name="kode" required>
                    <option value="">Pilih barang</option>
                    <?php foreach ($d['barang'] as $b): ?>
                    <option value="<?= e($b['kode']) ?>" data-stok="<?= e($b['stok']) ?>" data-satuan="<?= e($b['satuan']) ?>" data-pcs="<?= e($b['ukuran_pcs'] ?? 1) ?>" data-min="<?= e($b['minimum']) ?>">
                        <?= e($b['nama'] . ' (' . $b['kode'] . ') - Stok: ' . $b['stok'] . ' ' . $b['satuan'] . ', 1 pcs = ' . ($b['ukuran_pcs'] ?? 1) . ' ' . $b['satuan']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid3">
            <div><label for="mahasiswa">Jumlah Mahasiswa</label><input id="mahasiswa" name="mahasiswa" type="number" min="1" required></div>
            <div><label for="per_mhs">Ukuran / Kebutuhan per Mahasiswa</label><input id="per_mhs" name="per_mhs" type="number" min="1" required></div>
            <div><label for="ukuran_pcs">Ukuran per 1 Pcs / Batang</label><input id="ukuran_pcs" name="ukuran_pcs" type="number" min="1" value="100" required></div>
        </div>
        <div class="grid2">
            <div><label for="minimum_baru">Stok Minimum Barang yang Diajukan</label><input id="minimum_baru" name="minimum_baru" type="number" min="0" required></div>
            <div><label for="tol">Toleransi Gagal (%)</label><input id="tol" name="tol" type="number" min="0" max="100" value="10"></div>
        </div>
        <p id="hasil" class="calc" aria-live="polite"></p>
        <button class="btn" type="submit">Kirim Pengajuan ke Kepala Lab</button>
    </form>

    <h2>Daftar Pengajuan PLP ke Kepala Lab</h2>
    <?php req_table(array_reverse($d['pengajuan'])); ?>
    <script>
    const f = id => document.getElementById(id);
    function hitung() {
        const o = f('kode').selectedOptions[0], m = +f('mahasiswa').value, p = +f('per_mhs').value, t = +f('tol').value || 0, uPcs = Math.max(1, +f('ukuran_pcs').value || 1);
        if (!o || !o.value || m < 1 || p < 1) {
            f('hasil').textContent = 'Pilih pengajuan dosen atau isi barang, jumlah mahasiswa, dan ukuran per mahasiswa untuk melihat perhitungan per pcs.';
            return;
        }
        const sat = o.dataset.satuan, stok = +o.dataset.stok;
        const total = Math.ceil(Math.round(m * p * (1 + t / 100) * 1e6) / 1e6);
        const totalPcs = Math.ceil(total / uPcs);
        const pesan = Math.max(0, total - stok);
        const pesanPcs = Math.ceil(pesan / uPcs);
        const mhsPerPcs = (uPcs / p).toFixed(1);
        f('hasil').textContent = `Ukuran per mahasiswa: ${p} ${sat} (1 pcs @ ${uPcs} ${sat} cukup untuk ≈ ${mhsPerPcs} mhs). Total kebutuhan ${m} mhs (+tol ${t}%): ${total} ${sat} (${totalPcs} pcs). Stok saat ini: ${stok} ${sat}. Perlu dipesan: ${pesan} ${sat} (${pesanPcs} pcs).`;
    }
    const selPd = f('pengajuan_dosen_id');
    if (selPd) {
        selPd.addEventListener('change', () => {
            const opt = selPd.selectedOptions[0];
            if (opt && opt.value) {
                f('kode').value = opt.dataset.kode || '';
                f('mata_kuliah').value = opt.dataset.mk || '';
                f('mahasiswa').value = opt.dataset.mhs || '';
                f('per_mhs').value = opt.dataset.per || '';
                f('ukuran_pcs').value = opt.dataset.pcs || 100;
                f('minimum_baru').value = opt.dataset.min || 0;
                hitung();
            }
        });
    }
    f('kode').addEventListener('change', () => {
        const o = f('kode').selectedOptions[0];
        if (o && o.value) {
            f('ukuran_pcs').value = o.dataset.pcs || 1;
            f('minimum_baru').value = o.dataset.min || 0;
        }
        hitung();
    });
    ['mahasiswa', 'per_mhs', 'ukuran_pcs', 'tol'].forEach(i => f(i).addEventListener('input', hitung));
    hitung();
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Kalab Point 1 & 3: Kepala Lab melakukan approval atas pengajuan dari PLP (tanpa input manual)
function fitur_approval_kalab() {
    $redirect = role_url('approval_kalab');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $aksi = $_POST['aksi'] ?? '';
        if ($aksi === 'tarik_plp') {
            // Mengambil langsung dari pengajuan dosen yang sudah disetujui PLP
            $idxPd = find_idx($d['pengajuan_dosen'], 'id', post_int('pd_id'));
            if ($idxPd === null || $d['pengajuan_dosen'][$idxPd]['status'] !== 'disetujui') {
                go($redirect, 'Data pengajuan yang disetujui PLP tidak ditemukan.', 'bad');
            }
            $pd = $d['pengajuan_dosen'][$idxPd];
            $idxB = find_idx($d['barang'], 'kode', $pd['kode']);
            $stokSkrg = $idxB !== null ? (int)$d['barang'][$idxB]['stok'] : 0;
            $minSkrg = $idxB !== null ? (int)$d['barang'][$idxB]['minimum'] : 0;
            $ukPcs = (int)($pd['ukuran_pcs'] ?? ($idxB !== null ? $d['barang'][$idxB]['ukuran_pcs'] : 100));
            $total = (int)$pd['jumlah'];
            $pesan = max(0, $total - $stokSkrg);
            $jmlPcs = (int)ceil($total / max(1, $ukPcs));

            $d['pengajuan_dosen'][$idxPd]['diajukan_ke_kalab'] = true;
            $d['pengajuan'][] = [
                'id' => count($d['pengajuan']) + 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'PLP (Disetujui PLP)',
                'oleh_email' => 'plp@polban.ac.id',
                'pengajuan_dosen_id' => $pd['id'],
                'kode' => $pd['kode'],
                'nama' => $pd['nama'],
                'satuan' => $pd['satuan'],
                'ukuran_pcs' => $ukPcs,
                'ukuran_per_mhs' => $pd['ukuran_per_mhs'] ?? $total,
                'jml_mhs' => $pd['jml_mhs'] ?? 1,
                'jml_pcs' => $jmlPcs,
                'minimum_baru' => $minSkrg,
                'jumlah' => $total,
                'stok' => $stokSkrg,
                'pesan' => $pesan,
                'ket' => "Diambil dari Pengajuan Disetujui PLP #{$pd['id']} ({$pd['sub_praktikum']} - {$pd['kelas']}), stok $stokSkrg, perlu dipesan $pesan {$pd['satuan']}",
                'status' => 'disetujui',
                'disetujui_oleh' => $_SESSION['user']['nama'] . ' (Kepala Lab)',
                'catatan' => 'Disetujui langsung oleh Kepala Lab dari daftar pengajuan PLP',
            ];
            save($d);
            go($redirect, "Pengajuan #{$pd['id']} ({$pd['nama']}) berhasil diambil dari persetujuan PLP dan langsung disetujui Kepala Lab.");
        }

        $n = find_idx($d['pengajuan'], 'id', post_int('id'));
        if ($n === null || $d['pengajuan'][$n]['status'] !== 'menunggu' || !in_array($aksi, ['setujui', 'tolak'], true)) {
            go($redirect, 'Data pengajuan tidak ditemukan atau sudah ditinjau.', 'bad');
        }
        $d['pengajuan'][$n]['status'] = $aksi === 'setujui' ? 'disetujui' : 'ditolak';
        $d['pengajuan'][$n]['catatan'] = trim($_POST['catatan'] ?? '');
        $d['pengajuan'][$n]['disetujui_oleh'] = $_SESSION['user']['nama'] . ' (Kepala Lab)';
        save($d);
        go($redirect, 'Keputusan Kepala Lab atas pengajuan PLP berhasil disimpan.');
    }

    $title = 'Approval Pengajuan Kebutuhan Bahan (Kepala Lab)'; $active = 'approval_kalab'; $d = db();
    $dariPlpBelumTarik = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui' && empty($r['diajukan_ke_kalab']));
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">Kepala Lab tidak perlu menginput pengajuan secara manual. Data diambil otomatis dari pengajuan yang telah disetujui/diajukan oleh PLP untuk di-<em>approve</em> oleh Kepala Lab.</p>

    <h2>1. Pengajuan dari PLP yang Menunggu Approval Kepala Lab</h2>
    <?php req_table(array_reverse($d['pengajuan']), 'pengajuan', true, 'Approval Kepala Lab'); ?>

    <h2>2. Pengajuan Praktikum yang Sudah Disetujui PLP (Siap Diambil / Di-Approve Kalab)</h2>
    <?php if (!$dariPlpBelumTarik): ?>
        <p class="empty">Seluruh pengajuan yang disetujui PLP sudah masuk ke daftar approval Kepala Lab di atas.</p>
    <?php else: ?>
        <div class="tablewrap"><table>
            <tr><th>No</th><th>Tanggal</th><th>Dosen Pengaju</th><th>Bahan</th><th>Ukuran/Mhs & Pcs</th><th>Total Kebutuhan</th><th>Kelas & Praktikum</th><th>Status PLP</th><th>Aksi Kepala Lab</th></tr>
            <?php foreach (array_reverse($dariPlpBelumTarik) as $pd): ?>
            <tr>
                <td>#<?= e($pd['id']) ?></td>
                <td><?= e($pd['tanggal']) ?></td>
                <td><?= e($pd['oleh']) ?></td>
                <td><?= e($pd['nama'] . ' (' . $pd['kode'] . ')') ?></td>
                <td><?= e(($pd['ukuran_per_mhs'] ?? '-') . ' ' . $pd['satuan'] . '/mhs (' . ($pd['jml_pcs'] ?? 1) . ' pcs)') ?></td>
                <td><strong><?= e($pd['jumlah'] . ' ' . $pd['satuan']) ?></strong></td>
                <td><?= e($pd['kelas'] . ' - ' . $pd['sub_praktikum']) ?></td>
                <td><?= chip($pd['status']) ?></td>
                <td>
                    <form class="inline" method="post">
                        <?= field_csrf() ?>
                        <input type="hidden" name="aksi" value="tarik_plp">
                        <input type="hidden" name="pd_id" value="<?= e($pd['id']) ?>">
                        <button class="btn" type="submit">Ambil & Setujui (Kalab)</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table></div>
    <?php endif;
    require __DIR__ . '/partials/footer.php';
}

// Kalab Point 2: Fitur untuk melihat keseluruhan pengajuan bahan
function fitur_semua_pengajuan() {
    $title = 'Keseluruhan Pengajuan Bahan'; $active = 'semua_pengajuan'; $d = db();
    $st = $_GET['status'] ?? '';
    $rowsPlp = array_filter($d['pengajuan'], fn($r) => $st === '' || $r['status'] === $st);
    $rowsDosen = array_filter($d['pengajuan_dosen'], fn($r) => $st === '' || $r['status'] === $st);
    $resetUrl = role_url('semua_pengajuan');
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="cards">
        <div><strong><?= count($d['pengajuan_dosen']) ?></strong><span>Pengajuan Bahan Dosen</span></div>
        <div><strong><?= count($d['pengajuan']) ?></strong><span>Pengajuan PLP ke Kalab</span></div>
        <div><strong><?= count(array_filter($d['pengajuan'], fn($r) => $r['status'] === 'disetujui')) ?></strong><span>Disetujui Kepala Lab</span></div>
        <div><strong><?= count($d['pembelian']) ?></strong><span>Permintaan Pembelian</span></div>
    </div>
    <form class="filter" method="get">
        <input type="hidden" name="fitur" value="semua_pengajuan">
        <div><label for="status">Filter Status Pengajuan</label>
        <select id="status" name="status"><option value="">Semua Status</option>
        <?php foreach (['menunggu', 'disetujui', 'ditolak', 'dipesan'] as $s): ?><option value="<?= $s ?>"<?= $st === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
        </select></div>
        <button class="btn" type="submit">Terapkan</button><a class="reset" href="<?= e($resetUrl) ?>">Reset</a>
    </form>

    <h2>A. Daftar Pengajuan Kebutuhan PLP (Approval Kepala Lab)</h2>
    <?php req_table(array_reverse($rowsPlp)); ?>

    <h2>B. Daftar Pengajuan Bahan Praktikum 1 Semester (Dosen ke PLP)</h2>
    <?php dosen_table(array_reverse($rowsDosen)); ?>

    <h2>C. Daftar Permintaan Pembelian</h2>
    <?php if (!$d['pembelian']): ?><p class="empty">Belum ada permintaan pembelian.</p><?php else: ?>
    <div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Dari Pengajuan</th><th>Status</th></tr>
    <?php foreach (array_reverse($d['pembelian']) as $b): ?>
    <tr><td><?= e($b['id']) ?></td><td><?= e($b['tanggal']) ?></td><td><?= e($b['nama']) ?></td><td><?= e($b['jumlah'] . ' ' . $b['satuan']) ?></td><td>#<?= e($b['pengajuan_id']) ?></td><td><?= chip($b['status']) ?></td></tr>
    <?php endforeach; ?></table></div><?php endif;
    require __DIR__ . '/partials/footer.php';
}

// Dosen Point 1 & PLP Point 1:
// Dosen mengajukan bahan praktikum 1 semester + bisa menambahkan bahan baru sesuai kebutuhan praktikum
// + mencantumkan ukuran per mahasiswa dan ukuran per pcs
function fitur_pengajuan_dosen() {
    $redirect = role_url('pengajuan_dosen');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $pilihanKode = $_POST['kode'] ?? '';
        $ukPerMhs = post_int('ukuran_per_mhs');
        $jmlMhs = post_int('jml_mhs');
        $jml = post_int('jumlah');
        if ($jml < 1 && $ukPerMhs > 0 && $jmlMhs > 0) {
            $jml = $ukPerMhs * $jmlMhs;
        }
        $kelas = trim($_POST['kelas'] ?? '');
        $sub = trim($_POST['sub_praktikum'] ?? '');

        // Penambahan bahan baru sesuai kebutuhan praktikum jika Dosen memilih '+ Tambah Bahan Baru'
        if ($pilihanKode === '__BARU__') {
            $namaBaru = trim($_POST['nama_baru'] ?? '');
            $spekBaru = trim($_POST['spesifikasi_baru'] ?? '');
            $satuanBaru = trim($_POST['satuan_baru'] ?? 'cm');
            $ukPcsBaru = max(1, post_int('ukuran_pcs_baru'));
            if ($namaBaru === '' || $spekBaru === '' || $satuanBaru === '') {
                go($redirect, 'Lengkapi nama bahan baru, spesifikasi/ukuran, dan satuan untuk penambahan bahan baru.', 'bad');
            }
            $kodeBaru = 'MPK-' . str_pad((string)(count($d['barang']) + 25), 4, '0', STR_PAD_LEFT);
            $d['barang'][] = [
                'kode' => $kodeBaru,
                'nama' => $namaBaru,
                'spesifikasi' => $spekBaru,
                'satuan' => $satuanBaru,
                'ukuran_pcs' => $ukPcsBaru,
                'rak' => 'A-02',
                'minimum' => $jml,
                'stok' => 0,
                'foto' => '',
            ];
            $n = count($d['barang']) - 1;
        } else {
            $n = find_idx($d['barang'], 'kode', $pilihanKode);
        }

        if ($n === null || $jml < 1 || $ukPerMhs < 1 || $jmlMhs < 1 || $kelas === '' || $sub === '') {
            go($redirect, 'Pilih/tambah bahan, isi ukuran per mahasiswa, jumlah mahasiswa, kelas, dan nama/sub praktikum.', 'bad');
        }

        $b = $d['barang'][$n];
        $ukPcs = max(1, (int)($b['ukuran_pcs'] ?? 1));
        $jmlPcs = (int)ceil($jml / $ukPcs);
        $pesan = max(0, $jml - $b['stok']);

        $d['pengajuan_dosen'][] = [
            'id' => count($d['pengajuan_dosen']) + 1,
            'tanggal' => date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'ukuran_pcs' => $ukPcs,
            'ukuran_per_mhs' => $ukPerMhs,
            'jml_mhs' => $jmlMhs,
            'jumlah' => $jml,
            'jml_pcs' => $jmlPcs,
            'stok' => $b['stok'],
            'pesan' => $pesan,
            'kelas' => $kelas,
            'sub_praktikum' => $sub,
            'ket' => "Kelas: $kelas | Sub: $sub | $jmlMhs mhs × $ukPerMhs {$b['satuan']} (1 pcs = $ukPcs {$b['satuan']}, butuh $jmlPcs pcs)",
            'status' => 'menunggu',
            'diajukan_ke_kalab' => false,
            'catatan' => '',
        ];
        save($d);
        go($redirect, "Pengajuan bahan praktikum {$b['nama']} ($jml {$b['satuan']} / $jmlPcs pcs) berhasil dikirim untuk ditinjau PLP.");
    }
    $title = 'Pengajuan Bahan Praktikum (1 Semester)'; $active = 'pengajuan_dosen'; $d = db();
    $milik = array_reverse(array_filter($d['pengajuan_dosen'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email']));
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">Ajukan bahan yang dibutuhkan untuk praktikum dalam 1 semester (bisa memilih bahan yang tersedia atau menambahkan bahan baru sesuai kebutuhan praktikum).</p>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="kode">Nama Bahan Praktikum</label>
        <select id="kode" name="kode" required>
            <option value="">Pilih bahan praktikum</option>
            <?php foreach ($d['barang'] as $b): ?>
            <option value="<?= e($b['kode']) ?>" data-satuan="<?= e($b['satuan']) ?>" data-pcs="<?= e($b['ukuran_pcs'] ?? 1) ?>">
                <?= e($b['nama'] . ' - ' . $b['spesifikasi'] . ' (1 pcs = ' . ($b['ukuran_pcs'] ?? 1) . ' ' . $b['satuan'] . ')') ?>
            </option>
            <?php endforeach; ?>
            <option value="__BARU__">+ Tambah Bahan Baru Sesuai Kebutuhan Praktikum</option>
        </select>

        <div id="box_bahan_baru" style="display:none;background:#F5F7FA;border:1px dashed #AAB4C3;padding:12px;border-radius:6px;margin-bottom:12px">
            <div class="grid2">
                <div><label for="nama_baru">Nama Bahan Baru</label><input id="nama_baru" name="nama_baru" placeholder="Contoh: Aluminium Silinder"></div>
                <div><label for="spesifikasi_baru">Spesifikasi / Ukuran per Pcs</label><input id="spesifikasi_baru" name="spesifikasi_baru" placeholder="Contoh: Ø25 mm, panjang 100 cm"></div>
            </div>
            <div class="grid2">
                <div><label for="satuan_baru">Satuan Ukuran</label><input id="satuan_baru" name="satuan_baru" value="cm" placeholder="cm / pcs / lembar"></div>
                <div><label for="ukuran_pcs_baru">Ukuran per 1 Pcs / Batang</label><input id="ukuran_pcs_baru" name="ukuran_pcs_baru" type="number" min="1" value="100"></div>
            </div>
        </div>

        <div class="grid3">
            <div><label for="ukuran_per_mhs">Ukuran / Kebutuhan per Mahasiswa</label><input id="ukuran_per_mhs" name="ukuran_per_mhs" type="number" min="1" placeholder="Contoh: 20" required></div>
            <div><label for="jml_mhs">Jumlah Mahasiswa di Kelas</label><input id="jml_mhs" name="jml_mhs" type="number" min="1" placeholder="Contoh: 24" required></div>
            <div><label for="jumlah">Total Kebutuhan (1 Semester)</label><input id="jumlah" name="jumlah" type="number" min="1" required></div>
        </div>
        <div class="grid2">
            <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" placeholder="Contoh: 2B - D3 Teknik Mesin" required></div>
            <div><label for="sub_praktikum">Nama / Sub Praktikum</label><input id="sub_praktikum" name="sub_praktikum" placeholder="Contoh: Bubut Bertingkat" required></div>
        </div>
        <p id="info_pcs_dosen" class="calc" aria-live="polite">Pilih bahan dan isi ukuran per mahasiswa serta jumlah mahasiswa untuk melihat konversi kebutuhan per pcs.</p>
        <button class="btn" type="submit">Ajukan Bahan Praktikum</button>
    </form>
    <h2>Status Pengajuan Bahan Semester Saya</h2>
    <?php dosen_table($milik); ?>
    <script>
    const selKode = document.getElementById('kode'), boxBaru = document.getElementById('box_bahan_baru');
    const inUk = document.getElementById('ukuran_per_mhs'), inMhs = document.getElementById('jml_mhs'), inTotal = document.getElementById('jumlah');
    const infoPcs = document.getElementById('info_pcs_dosen');
    function updateDosenCalc() {
        const isBaru = selKode.value === '__BARU__';
        boxBaru.style.display = isBaru ? 'block' : 'none';
        const uk = +inUk.value || 0, mhs = +inMhs.value || 0;
        if (uk > 0 && mhs > 0) {
            inTotal.value = uk * mhs;
        }
        const total = +inTotal.value || 0;
        const opt = selKode.selectedOptions[0];
        const sat = isBaru ? (document.getElementById('satuan_baru').value || 'satuan') : (opt ? (opt.dataset.satuan || '') : '');
        const uPcs = isBaru ? (+document.getElementById('ukuran_pcs_baru').value || 1) : (opt ? (+opt.dataset.pcs || 1) : 1);
        if (total > 0 && sat) {
            const pcs = Math.ceil(total / Math.max(1, uPcs));
            infoPcs.textContent = `Perhitungan: ${mhs} mahasiswa × ${uk} ${sat}/mahasiswa = ${total} ${sat} (Setara ≈ ${pcs} pcs @ ${uPcs} ${sat}/pcs).`;
        }
    }
    selKode.addEventListener('change', updateDosenCalc);
    [inUk, inMhs, inTotal, document.getElementById('satuan_baru'), document.getElementById('ukuran_pcs_baru')].forEach(el => el && el.addEventListener('input', updateDosenCalc));
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Mahasiswa Point 1, 2, 3:
// 1. Tidak perlu form NIM karena sudah login
// 2. Tanggal tercatat otomatis (date('Y-m-d'))
// 3. Pengambilan bisa diambil sebagian (bertahap) maupun penuh, dengan pelacakan sisa pengambilan
function fitur_konfirmasi_mhs() {
    $redirect = role_url('permintaan');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();

        // Jika mahasiswa mengajukan pengambilan sisa dari pengambilan sebagian sebelumnya
        if (($_POST['aksi_mhs'] ?? '') === 'ambil_sisa') {
            $parentIdx = find_idx($d['permintaan'], 'id', post_int('parent_id'));
            $jmlSisa = post_int('jumlah_sisa');
            if ($parentIdx === null || $jmlSisa < 1 || $jmlSisa > ($d['permintaan'][$parentIdx]['sisa_ambil'] ?? 0)) {
                go($redirect, 'Jumlah pengambilan sisa tidak valid.', 'bad');
            }
            $parent = &$d['permintaan'][$parentIdx];
            $parent['sisa_ambil'] -= $jmlSisa;
            $sisaBaru = $parent['sisa_ambil'];
            $d['permintaan'][] = [
                'id' => count($d['permintaan']) + 1,
                'tanggal' => date('Y-m-d'), // tercatat otomatis
                'oleh' => $_SESSION['user']['nama'],
                'oleh_email' => $_SESSION['user']['email'],
                'pengajuan_dosen_id' => $parent['pengajuan_dosen_id'] ?? 0,
                'kode' => $parent['kode'],
                'nama' => $parent['nama'],
                'satuan' => $parent['satuan'],
                'tipe_ambil' => $sisaBaru > 0 ? 'sebagian' : 'penuh',
                'total_jatah' => $parent['total_jatah'] ?? $parent['jumlah'],
                'jumlah' => $jmlSisa,
                'sisa_ambil' => $sisaBaru,
                'kelas' => $parent['kelas'],
                'sub_praktikum' => $parent['sub_praktikum'],
                'ket' => "Pengambilan Lanjutan (Sisa) Praktikum: {$parent['sub_praktikum']} | Kelas: {$parent['kelas']} | Diambil: $jmlSisa {$parent['satuan']} (Sisa: $sisaBaru {$parent['satuan']})",
                'status' => 'menunggu',
                'disetujui_oleh' => '',
                'catatan' => '',
            ];
            unset($parent);
            save($d);
            go($redirect, "Pengajuan pengambilan sisa bahan ($jmlSisa) berhasil dikirim dengan tanggal otomatis hari ini.");
        }

        $pdId = post_int('pengajuan_dosen_id');
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $tipeAmbil = ($_POST['tipe_ambil'] ?? 'penuh') === 'sebagian' ? 'sebagian' : 'penuh';
        $totalJatah = post_int('total_jatah');
        $jml = post_int('jumlah');
        $kelas = trim($_POST['kelas'] ?? '');
        $sub = trim($_POST['sub_praktikum'] ?? '');
        $tglOtomatis = date('Y-m-d'); // Tanggal tercatat otomatis tanpa input form

        if ($pdId > 0) {
            $idxPd = find_idx($d['pengajuan_dosen'], 'id', $pdId);
            if ($idxPd !== null) {
                $pd = $d['pengajuan_dosen'][$idxPd];
                $n = find_idx($d['barang'], 'kode', $pd['kode']);
                if ($kelas === '') $kelas = $pd['kelas'];
                if ($sub === '') $sub = $pd['sub_praktikum'];
                if ($totalJatah < 1) $totalJatah = (int)($pd['ukuran_per_mhs'] ?? $jml);
            }
        }

        if ($totalJatah < $jml) $totalJatah = $jml;
        if ($tipeAmbil === 'penuh') {
            $jml = $totalJatah;
        }
        $sisaAmbil = max(0, $totalJatah - $jml);

        if ($n === null || $jml < 1 || $kelas === '' || $sub === '') {
            go($redirect, 'Lengkapi pilihan praktikum/bahan, kelas, sub praktikum, dan jumlah pengambilan bahan.', 'bad');
        }
        $b = $d['barang'][$n];
        $ketAmbil = $sisaAmbil > 0
            ? "Pengambilan: Sebagian ($jml/$totalJatah {$b['satuan']}, sisa $sisaAmbil {$b['satuan']})"
            : "Pengambilan: Penuh ($jml {$b['satuan']})";

        $d['permintaan'][] = [
            'id' => count($d['permintaan']) + 1,
            'tanggal' => $tglOtomatis,
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'pengajuan_dosen_id' => $pdId,
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'tipe_ambil' => $sisaAmbil > 0 ? 'sebagian' : 'penuh',
            'total_jatah' => $totalJatah,
            'jumlah' => $jml,
            'sisa_ambil' => $sisaAmbil,
            'kelas' => $kelas,
            'sub_praktikum' => $sub,
            'ket' => "Konfirmasi Praktikum: $sub | Kelas: $kelas | $ketAmbil",
            'status' => 'menunggu',
            'disetujui_oleh' => '',
            'catatan' => '',
        ];
        save($d);
        go($redirect, "Konfirmasi praktikum & pengajuan pengambilan ($ketAmbil) berhasil dikirim pada tanggal $tglOtomatis.");
    }
    $title = 'Konfirmasi Praktikum & Pengambilan Bahan'; $active = 'permintaan'; $d = db();
    $jadwalDosen = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui');
    if (!$jadwalDosen) $jadwalDosen = $d['pengajuan_dosen'];
    $milik = array_reverse(array_filter($d['permintaan'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email'] || norm_role($_SESSION['user']['role']) === 'mhs'));
    require __DIR__ . '/partials/header.php';
    ?>
    <p class="sum">
        Identitas Mahasiswa: <strong><?= e($_SESSION['user']['nama']) ?> (<?= e($_SESSION['user']['email']) ?>)</strong> —
        Tanggal Konfirmasi Tercatat Otomatis: <strong><?= date('Y-m-d') ?></strong>.
    </p>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <?php if ($jadwalDosen): ?>
        <label for="pengajuan_dosen_id">Pilih Praktikum (dari Pengajuan Dosen)</label>
        <select id="pengajuan_dosen_id" name="pengajuan_dosen_id">
            <option value="">-- Pilih praktikum dosen --</option>
            <?php foreach ($jadwalDosen as $jd): ?>
            <option value="<?= e($jd['id']) ?>"
                data-kode="<?= e($jd['kode']) ?>"
                data-kelas="<?= e($jd['kelas']) ?>"
                data-sub="<?= e($jd['sub_praktikum']) ?>"
                data-jatah="<?= e($jd['ukuran_per_mhs'] ?? 10) ?>">
                <?= e($jd['sub_praktikum'] . ' | Kelas ' . $jd['kelas'] . ' | Bahan: ' . $jd['nama'] . ' (Jatah/mhs: ' . ($jd['ukuran_per_mhs'] ?? '-') . ' ' . $jd['satuan'] . ')') ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <label for="kode">Nama Bahan yang Diambil</label>
        <select id="kode" name="kode" required><option value="">Pilih bahan</option>
        <?php foreach ($d['barang'] as $b): ?><option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' - ' . $b['spesifikasi'] . ' (' . $b['satuan'] . ')') ?></option><?php endforeach; ?>
        </select>
        <div class="grid2">
            <div><label for="kelas">Kelas</label><input id="kelas" name="kelas" placeholder="Contoh: 2B - D3 Teknik Mesin" required></div>
            <div><label for="sub_praktikum">Nama / Sub Praktikum</label><input id="sub_praktikum" name="sub_praktikum" placeholder="Contoh: Bubut Bertingkat" required></div>
        </div>
        <div class="grid3">
            <div>
                <label for="tipe_ambil">Metode Pengambilan Bahan</label>
                <select id="tipe_ambil" name="tipe_ambil">
                    <option value="penuh">Ambil Penuh (Seluruh Jatah)</option>
                    <option value="sebagian">Ambil Sebagian (Bertahap)</option>
                </select>
            </div>
            <div>
                <label for="total_jatah">Total Jatah Kebutuhan per Mahasiswa</label>
                <input id="total_jatah" name="total_jatah" type="number" min="1" required>
            </div>
            <div>
                <label for="jumlah">Jumlah Diambil Sekarang</label>
                <input id="jumlah" name="jumlah" type="number" min="1" required>
            </div>
        </div>
        <p id="info_ambil" class="calc">Tanggal praktikum tercatat otomatis hari ini (<?= date('Y-m-d') ?>). Pengambilan dapat dilakukan penuh atau sebagian (bertahap).</p>
        <button class="btn" type="submit">Kirim Konfirmasi & Pengajuan Pengambilan</button>
    </form>
    <h2>Status Konfirmasi & Pengambilan Bahan Saya</h2>
    <?php mhs_table($milik, false, true); ?>
    <script>
    const selPd = document.getElementById('pengajuan_dosen_id');
    const inTipe = document.getElementById('tipe_ambil'), inJatah = document.getElementById('total_jatah'), inJml = document.getElementById('jumlah');
    const infoAmbil = document.getElementById('info_ambil');
    function syncAmbil() {
        const jatah = +inJatah.value || 0;
        if (inTipe.value === 'penuh' && jatah > 0) {
            inJml.value = jatah;
        }
        const ambil = +inJml.value || 0;
        const sisa = Math.max(0, jatah - ambil);
        if (jatah > 0 && ambil > 0) {
            infoAmbil.textContent = sisa > 0
                ? `Pengambilan Sebagian: diambil sekarang ${ambil}, sisa ${sisa} dapat diambil pada tahap berikutnya.`
                : `Pengambilan Penuh: seluruh jatah (${ambil}) diambil sekaligus.`;
        }
    }
    if (selPd) {
        selPd.addEventListener('change', () => {
            const opt = selPd.selectedOptions[0];
            if (opt && opt.value) {
                document.getElementById('kode').value = opt.dataset.kode || '';
                document.getElementById('kelas').value = opt.dataset.kelas || '';
                document.getElementById('sub_praktikum').value = opt.dataset.sub || '';
                inJatah.value = opt.dataset.jatah || 10;
                inJml.value = opt.dataset.jatah || 10;
                syncAmbil();
            }
        });
    }
    [inTipe, inJatah, inJml].forEach(el => el && el.addEventListener('input', syncAmbil));
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Teknisi Point 2 & 4 + PLP:
// - Teknisi hanya bisa approval untuk pengajuan mahasiswa (dilengkapi fitur approval sekaligus untuk 1 praktikum)
// - PLP bisa meninjau pengajuan Dosen (sekaligus set stok minimum) dan konfirmasi mahasiswa
function fitur_tinjau() {
    $role = norm_role($_SESSION['user']['role']);
    $redirect = role_url('tinjau');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();

        // Teknisi Point 4: Approval sekaligus untuk 1 praktikum pada bagian konfirmasi praktikum mhs
        if (($_POST['aksi_bulk'] ?? '') === 'setujui_praktikum') {
            $tgl = $_POST['tanggal'] ?? '';
            $kls = $_POST['kelas'] ?? '';
            $sub = $_POST['sub_praktikum'] ?? '';
            $cat = trim($_POST['catatan'] ?? '');
            $countApproved = 0;
            foreach ($d['permintaan'] as &$pm) {
                if ($pm['status'] === 'menunggu' && ($pm['tanggal'] ?? '') === $tgl && ($pm['kelas'] ?? '') === $kls && ($pm['sub_praktikum'] ?? '') === $sub) {
                    $pm['status'] = 'disetujui';
                    $pm['catatan'] = $cat !== '' ? $cat : 'Disetujui sekaligus (1 praktikum)';
                    $pm['disetujui_oleh'] = $_SESSION['user']['nama'] . ' (' . (ROLES[$role] ?? $role) . ')';
                    $countApproved++;
                }
            }
            unset($pm);
            save($d);
            go($redirect, "Berhasil menyetujui sekaligus $countApproved konfirmasi mahasiswa untuk praktikum $sub ($kls).");
        }

        $tipe = $_POST['tipe'] ?? '';
        $aksi = $_POST['aksi'] ?? '';
        // Teknisi hanya boleh meninjau permintaan mahasiswa
        $allowedTipe = $role === 'plp' ? ['pengajuan_dosen', 'permintaan'] : ['permintaan'];
        if (!in_array($tipe, $allowedTipe, true) || !in_array($aksi, ['setujui', 'tolak'], true)) {
            go($redirect, 'Permintaan tidak valid atau di luar kewenangan role Anda.', 'bad');
        }
        $n = find_idx($d[$tipe], 'id', post_int('id'));
        if ($n === null || $d[$tipe][$n]['status'] !== 'menunggu') {
            go($redirect, 'Data tidak ditemukan atau sudah ditinjau.', 'bad');
        }
        $d[$tipe][$n]['status'] = $aksi === 'setujui' ? 'disetujui' : 'ditolak';
        $d[$tipe][$n]['catatan'] = trim($_POST['catatan'] ?? '');
        $d[$tipe][$n]['disetujui_oleh'] = $_SESSION['user']['nama'] . ' (' . (ROLES[$role] ?? $role) . ')';

        // PLP Point 2: Jika PLP menginput stok minimum saat meninjau pengajuan dosen
        if ($tipe === 'pengajuan_dosen' && isset($_POST['minimum_baru']) && $_POST['minimum_baru'] !== '') {
            $minBaru = max(0, post_int('minimum_baru'));
            $idxB = find_idx($d['barang'], 'kode', $d['pengajuan_dosen'][$n]['kode']);
            if ($idxB !== null) {
                $d['barang'][$idxB]['minimum'] = $minBaru;
            }
        }
        save($d);
        go($redirect, 'Hasil tinjauan berhasil disimpan.');
    }

    $title = $role === 'teknisi' ? 'Konfirmasi Praktikum Mahasiswa (Approval Teknisi)' : 'Tinjau Pengajuan Dosen & Mahasiswa';
    $active = 'tinjau';
    $d = db();
    require __DIR__ . '/partials/header.php';

    if ($role === 'plp'): ?>
        <h2>Pengajuan Bahan Praktikum 1 Semester (Dosen)</h2>
        <?php dosen_table(array_reverse($d['pengajuan_dosen']), true); ?>
    <?php endif; ?>

    <?php render_rekap_kehadiran_praktikum($d['permintaan'], true); ?>
    <h2>Daftar Konfirmasi Praktikum & Pengambilan Bahan (Mahasiswa)</h2>
    <?php mhs_table(array_reverse($d['permintaan']), true);
    require __DIR__ . '/partials/footer.php';
}

function fitur_pembelian() {
    $redirect = role_url('pembelian');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $n = find_idx($d['pengajuan'], 'id', post_int('id'));
        if ($n === null || $d['pengajuan'][$n]['status'] !== 'disetujui' || ($d['pengajuan'][$n]['pesan'] ?? $d['pengajuan'][$n]['jumlah']) < 1) {
            go($redirect, 'Pengajuan belum disetujui Kepala Lab atau stok sudah mencukupi.', 'bad');
        }
        $p = $d['pengajuan'][$n];
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
        $d['pengajuan'][$n]['status'] = 'dipesan';
        save($d);
        go($redirect, "Permintaan pembelian {$p['nama']} sebanyak {$jmlPesan} {$p['satuan']} dibuat.");
    }
    $title = 'Permintaan Pembelian'; $active = 'pembelian'; $d = db();
    $siap = array_filter($d['pengajuan'], fn($r) => $r['status'] === 'disetujui');
    require __DIR__ . '/partials/header.php';
    ?>
    <h2>Pengajuan PLP yang Disetujui Kepala Lab, Siap Dibuatkan Pembelian</h2>
    <?php if (!$siap): ?><p class="empty">Belum ada pengajuan yang disetujui Kepala Lab.</p><?php else: ?>
    <div class="tablewrap"><table>
    <tr><th>No</th><th>Barang</th><th>Ukuran/Mhs & Pcs</th><th>Kebutuhan</th><th>Perlu dipesan</th><th>Aksi</th></tr>
    <?php foreach ($siap as $r): ?>
    <tr>
        <td><?= e($r['id']) ?></td>
        <td><?= e($r['nama']) ?></td>
        <td><?= e(($r['ukuran_per_mhs'] ?? '-') . ' ' . $r['satuan'] . '/mhs (' . ($r['jml_pcs'] ?? 1) . ' pcs)') ?></td>
        <td><?= e($r['jumlah'] . ' ' . $r['satuan']) ?></td>
        <td><?= e($r['pesan'] . ' ' . $r['satuan']) ?></td>
        <td><?php if ($r['pesan'] > 0): ?><form class="inline" method="post"><?= field_csrf() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>"><button class="btn">Buat permintaan pembelian</button></form><?php else: ?>Stok sudah cukup<?php endif; ?></td>
    </tr>
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
    <h2>Pengajuan Bahan Semester (Dosen ke PLP)</h2>
    <?php dosen_table(array_reverse($rowsDosen)); ?>
    <h2>Pengajuan Kebutuhan Pengadaan (PLP ke Kepala Lab)</h2>
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
