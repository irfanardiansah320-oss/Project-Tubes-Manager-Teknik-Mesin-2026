<?php
session_start();
const APP_NAME = 'Inventaris Bahan Habis Pakai';
const DB_FILE = __DIR__ . '/storage/db.json'; // penyimpanan sementara (JSON), ganti ke MySQL nanti

// Sesuai Notulensi 27 Nov 2026: Sistem disederhanakan menjadi 5 Role Utama.
// Kalab & Kajur digabung ke dalam entitas Dosen dengan atribut 'jabatan' (biasa | kalab | kajur).
const ROLES = [
    'admin'     => 'Administrator',
    'dosen'     => 'Dosen',
    'plp'       => 'Pranata Laboratorium Pendidikan',
    'teknisi'   => 'Teknisi',
    'mhs'       => 'Mahasiswa',
    'mahasiswa' => 'Mahasiswa',
    // Alias kompatibilitas data lama
    'kalab'     => 'Dosen (Kepala Lab)',
    'kajur'     => 'Dosen (Ketua Jurusan)',
];

const JABATAN_DOSEN = [
    'biasa' => 'Dosen Pengampu',
    'kalab' => 'Kepala Laboratorium (Kalab)',
    'kajur' => 'Ketua Jurusan (Kajur)',
];

const LAB_LIST = [
    'Lab Permesinan',
    'Lab Pengelasan & Fabrikasi',
    'Lab CNC & Otomasi',
    'Lab Uji Bahan & Metrologi',
];

const ROLE_FILES = [
    'admin'     => 'admin.php',
    'dosen'     => 'dosen.php',
    'kalab'     => 'dosen.php',
    'kajur'     => 'dosen.php',
    'teknisi'   => 'teknisi.php',
    'plp'       => 'plp.php',
    'mhs'       => 'mhs.php',
    'mahasiswa' => 'mhs.php',
];

// Menu per role (untuk Dosen ditentukan secara dinamis oleh get_user_menus() berdasarkan atribut jabatan)
const ROLE_MENUS = [
    'admin' => [
        'admin_dashboard' => 'Dashboard Admin',
        'admin_users'     => 'Pengguna & Mutasi Jabatan',
        'admin_akademik'  => 'Matkul, Prodi & Semester',
        'admin_aturan'    => 'Business Rule & Time Frame',
    ],
    'dosen' => [
        'pengajuan_dosen' => 'Permintaan & Usulan Kebutuhan Bahan',
    ],
    'plp' => [
        'dashboard'  => 'Dashboard',
        'stok'       => 'Stok Bahan Lab',
        'rak'        => 'Rak & Lokasi Lab',
        'peringatan' => 'Peringatan Stok',
        'tinjau'     => 'Tinjau Permintaan Dosen & Mhs',
        'pengajuan'  => 'Pengajuan Pengadaan 2 Smt (ke Kalab)',
        'cek_barang' => 'Cek Barang Real-Time',
        'pembelian'  => 'Distribusi Pengadaan ke Lab',
        'rekap'      => 'Rekap Permintaan & Pengajuan',
    ],
    'teknisi' => [
        'dashboard'   => 'Dashboard',
        'stok'        => 'Stok Bahan Lab',
        'rak'         => 'Rak & Lokasi Lab',
        'peringatan'  => 'Peringatan Stok',
        'masuk'       => 'Penerimaan Bahan Lab',
        'keluar'      => 'Pengeluaran Bahan',
        'tinjau'      => 'Pelayanan Pengambilan Mhs',
        'stockopname' => 'Stock Opname Fisik',
    ],
    'mhs' => [
        'permintaan' => 'Pengambilan Bahan Praktikum (Individu)',
    ],
];

// Pemetaan file fitur langsung (kompatibilitas akses langsung)
const PAGES = [
    'dashboard.php'  => ['Dashboard', ['teknisi', 'dosen', 'plp', 'admin']],
    'stok.php'       => ['Stok Bahan Lab', ['teknisi', 'dosen', 'plp', 'admin']],
    'rak.php'        => ['Rak & Lokasi Lab', ['teknisi', 'dosen', 'plp', 'admin']],
    'peringatan.php' => ['Peringatan Stok', ['teknisi', 'dosen', 'plp', 'admin']],
    'masuk.php'      => ['Penerimaan Bahan Lab', ['teknisi']],
    'keluar.php'     => ['Pengeluaran Bahan', ['teknisi']],
    'pengajuan.php'  => ['Pengajuan Pengadaan 2 Semester', ['plp', 'dosen']],
    'tinjau.php'     => ['Tinjau Permintaan', ['plp', 'teknisi', 'dosen']],
    'pembelian.php'  => ['Distribusi Pengadaan', ['plp']],
    'rekap.php'      => ['Rekap', ['dosen', 'plp', 'admin']],
    'permintaan.php' => ['Pengambilan Bahan Praktikum', ['mhs', 'mahasiswa']],
];

const DEFAULT_RAK_KAPASITAS = [
    'A-01' => 50,
    'A-02' => 500,
    'A-03' => 500,
];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function norm_role($r) {
    if ($r === 'mahasiswa') return 'mhs';
    if (in_array($r, ['kalab', 'kajur'], true)) return 'dosen';
    return $r;
}

function norm_jabatan($userRow) {
    $rawRole = $userRow['role'] ?? '';
    $jab = $userRow['jabatan'] ?? '';
    if (in_array($jab, ['biasa', 'kalab', 'kajur'], true)) return $jab;
    if ($rawRole === 'kalab') return 'kalab';
    if ($rawRole === 'kajur') return 'kajur';
    return norm_role($rawRole) === 'dosen' ? 'biasa' : '';
}

function current_jabatan() {
    $u = $_SESSION['user'] ?? [];
    if (norm_role($u['role'] ?? '') !== 'dosen') return '';
    return norm_jabatan($u);
}

function get_user_menus() {
    $u = $_SESSION['user'] ?? [];
    $roleKey = norm_role($u['role'] ?? '');
    if ($roleKey !== 'dosen') {
        return ROLE_MENUS[$roleKey] ?? [];
    }
    $jab = current_jabatan();
    if ($jab === 'kalab') {
        return [
            'approval_kalab'  => 'Approval Pengajuan Pengadaan (Kalab)',
            'semua_pengajuan' => 'Monitoring Permintaan & Pengajuan',
            'peringatan'      => 'Peringatan Stok Lab',
            'pengajuan_dosen' => 'Permintaan & Usulan Bahan (Dosen)',
        ];
    }
    if ($jab === 'kajur') {
        return [
            'dashboard'       => 'Dashboard Monitoring (Kajur)',
            'stok'            => 'Stok Bahan Laboratorium',
            'rak'             => 'Rak & Lokasi Laboratorium',
            'rekap'           => 'Rekap Permintaan & Pengajuan',
            'pengajuan_dosen' => 'Permintaan & Usulan Bahan (Dosen)',
        ];
    }
    return [
        'pengajuan_dosen' => 'Permintaan & Usulan Kebutuhan Bahan',
    ];
}

function seed() {
    $h = password_hash('password', PASSWORD_DEFAULT);
    $users = [];
    foreach ([
        ['kalab', 'Kepala Lab', ''],
        ['kajur', 'Ketua Jurusan', ''],
        ['teknisi', 'Teknisi Lab', ''],
        ['plp', 'PLP', ''],
        ['dosen', 'Dosen Pengampu', ''],
        ['mhs', 'Mahasiswa Contoh', '231211001'],
        ['mahasiswa', 'Mahasiswa Contoh 2', '231211002'],
    ] as [$r, $n, $nim]) {
        $users[] = ['email' => "$r@polban.ac.id", 'nama' => $n, 'role' => norm_role($r), 'nim' => $nim, 'hash' => $h];
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
                'catatan' => 'Disetujui PLP untuk 2 semester',
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
                'nim' => '231211001',
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
    if (!isset($d['rak_lokasi'])) $d['rak_lokasi'] = [
        'A-01' => ['ruangan' => 'Lab Pengelasan & Fabrikasi', 'lemari' => 'Lemari Plat 1'],
        'A-02' => ['ruangan' => 'Lab Permesinan', 'lemari' => 'Lemari Bahan Bubut'],
        'A-03' => ['ruangan' => 'Lab Permesinan', 'lemari' => 'Lemari Logam Non-Ferro'],
    ];
    if (!isset($d['pengajuan_dosen'])) $d['pengajuan_dosen'] = [];
    if (!isset($d['permintaan'])) $d['permintaan'] = [];
    if (!isset($d['pengajuan'])) $d['pengajuan'] = [];
    if (!isset($d['stockopname'])) $d['stockopname'] = [];
    if (!isset($d['pembelian'])) $d['pembelian'] = [];
    if (!isset($d['log'])) $d['log'] = [];

    // Normalisasi 5 Role Utama & pastikan akun Administrator tersedia
    $hasAdmin = false;
    foreach ($d['users'] as &$u) {
        if (($u['role'] ?? '') === 'admin') $hasAdmin = true;
        if (in_array($u['role'] ?? '', ['kalab', 'kajur'], true)) {
            $u['jabatan'] = $u['role'];
            $u['role'] = 'dosen';
        } elseif (($u['role'] ?? '') === 'dosen' && empty($u['jabatan'])) {
            $u['jabatan'] = 'biasa';
        }
        if (empty($u['lab'])) {
            $u['lab'] = in_array($u['jabatan'] ?? '', ['kajur'], true) || ($u['role'] ?? '') === 'admin'
                ? 'Kantor Jurusan Teknik Mesin'
                : 'Lab Permesinan';
        }
        if (($u['role'] ?? '') === 'dosen' && empty($u['matkul_diampu'])) {
            $u['matkul_diampu'] = ['MK-01', 'MK-02', 'MK-03'];
        }
    }
    unset($u);
    if (!$hasAdmin) {
        array_unshift($d['users'], [
            'email' => 'admin@polban.ac.id',
            'nama' => 'Administrator Jurusan',
            'role' => 'admin',
            'jabatan' => '',
            'nip' => '199001012020121001',
            'lab' => 'Kantor Jurusan Teknik Mesin',
            'hash' => '$2y$12$elnWViB9rmQp//0GQc.FvujWnl5m0a0GKVUvRXhTmS3/HpoBknKom',
        ]);
    }

    // Master Program Studi, Mata Kuliah & Silabus/Jobsheet, serta Business Rule Time Frame
    if (!isset($d['master_prodi'])) {
        $d['master_prodi'] = [
            ['kode' => 'D3-TM', 'nama' => 'D3 Teknik Mesin', 'jenjang' => 'D3'],
            ['kode' => 'D4-TPPM', 'nama' => 'D4 Teknik Perancangan & Konstruksi Mesin', 'jenjang' => 'D4'],
            ['kode' => 'D4-PM', 'nama' => 'D4 Proses Manufaktur', 'jenjang' => 'D4'],
        ];
    }
    if (!isset($d['master_matkul'])) {
        $d['master_matkul'] = [
            ['kode' => 'MK-01', 'nama' => 'Praktik Pemesinan Bubut', 'prodi' => 'D3 Teknik Mesin', 'semester' => '3 (Ganjil) & 4 (Genap)', 'lab' => 'Lab Permesinan', 'jobsheet' => 'Bubut Bertingkat & Silinder', 'dosen_email' => 'dosen@polban.ac.id', 'bahan_kode' => ['MPK-0012', 'MPK-0018', 'MPK-0028']],
            ['kode' => 'MK-02', 'nama' => 'Praktik Pengelasan & Fabrikasi', 'prodi' => 'D3 Teknik Mesin', 'semester' => '3 (Ganjil) & 4 (Genap)', 'lab' => 'Lab Pengelasan & Fabrikasi', 'jobsheet' => 'Las SMAW & Sambungan Plat', 'dosen_email' => 'dosen@polban.ac.id', 'bahan_kode' => ['MPK-0018', 'MPK-0021']],
            ['kode' => 'MK-03', 'nama' => 'Praktik Uji Bahan & Metrologi', 'prodi' => 'D4 Proses Manufaktur', 'semester' => '5 (Ganjil) & 6 (Genap)', 'lab' => 'Lab Uji Bahan & Metrologi', 'jobsheet' => 'Uji Tarik & Uji Keras Spesimen', 'dosen_email' => 'kalab@polban.ac.id', 'bahan_kode' => ['MPK-0012', 'MPK-0021']],
        ];
    }
    if (!isset($d['business_rules'])) {
        $d['business_rules'] = [
            'periode_aktif' => true,
            'bulan_mulai' => 'Maret',
            'bulan_selesai' => 'Mei',
            'tahun_ajaran' => '2026/2027 (2 Semester)',
            'kebijakan_overlimit' => 'Izinkan dengan Pencatatan Jatah Minus & Kompensasi/Denda',
            'denda_per_satuan' => 5000,
        ];
    }
    if (!isset($d['mutasi_log'])) {
        $d['mutasi_log'] = [];
    }

    foreach ($d['barang'] as &$b) {
        if (!isset($b['ukuran_pcs']) || $b['ukuran_pcs'] < 1) {
            $b['ukuran_pcs'] = $b['satuan'] === 'cm' ? 100 : 1;
        }
        $b['rak'] = trim((string)($b['rak'] ?? ''));
        if (empty($b['lab'])) {
            $b['lab'] = ($b['rak'] === 'A-01') ? 'Lab Pengelasan & Fabrikasi' : 'Lab Permesinan';
        }
        if ($b['rak'] !== '' && !isset($d['rak_kapasitas'][$b['rak']])) {
            $d['rak_kapasitas'][$b['rak']] = 500;
        }
    }
    unset($b);
    unset($d['rak_kapasitas']['']);
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
// Rumus Pengadaan 2 Semester sesuai Notulensi Bagian 5:
// Jumlah Pengajuan = ceil( (Total Permintaan - Sisa Stok) + Stok Minimum + Toleransi Gagal Potong )
function kebutuhan($mhs, $per, $tol, $stok, $min = 0) {
    $totalPermintaan = $mhs * $per;
    $gagalPotong = $totalPermintaan * ($tol / 100);
    $kebutuhanKotor = (int)ceil(round($totalPermintaan + $gagalPotong, 6));
    $pengajuan = (int)ceil(round(max(0, ($totalPermintaan - $stok) + $min + $gagalPotong), 6));
    return [$kebutuhanKotor, $pengajuan, (int)$totalPermintaan, (int)ceil(round($gagalPotong, 6))];
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
function sync_session_user() {
    if (empty($_SESSION['user'])) return;
    $uList = db()['users'] ?? [];
    $uIdx = find_idx($uList, 'email', $_SESSION['user']['email'] ?? '');
    if ($uIdx !== null) {
        $_SESSION['user']['role'] = norm_role($uList[$uIdx]['role']);
        $_SESSION['user']['jabatan'] = norm_jabatan($uList[$uIdx]);
        $_SESSION['user']['nim'] = $uList[$uIdx]['nim'] ?? ($_SESSION['user']['nim'] ?? '');
        $_SESSION['user']['nama'] = $uList[$uIdx]['nama'] ?? $_SESSION['user']['nama'];
    }
}

function guard($file) {
    if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
    sync_session_user();
    $role = norm_role($_SESSION['user']['role']);
    $allowed = array_map('norm_role', PAGES[$file][1] ?? []);
    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        exit('403 - Anda tidak berwenang membuka halaman ini.');
    }
    // Batasi visibilitas stok untuk Dosen Biasa sesuai Notulensi Bagian 3
    if ($role === 'dosen' && current_jabatan() === 'biasa' && in_array($file, ['stok.php', 'rak.php', 'peringatan.php', 'dashboard.php'], true)) {
        go('dosen.php', 'Visibilitas stok dibatasi: Dosen biasa tidak diperlihatkan informasi jumlah ketersediaan stok bahan di laboratorium.', 'warn');
    }
}

function guard_role($expectedRole) {
    if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
    sync_session_user();
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

// Konfigurasi warna sidebar, grup menu, badge tugas, dan tombol aksi utama per role sesuai Wireframe v4 & 5 Role Notulensi
function role_theme_meta($roleKey, $activeKey = '', $jabKey = '') {
    if ($roleKey === 'dosen') {
        if ($jabKey === 'kalab') {
            return [
                'theme' => '#4E2A7F',
                'box' => '#5E3891',
                'soft' => '#EFEAF6',
                'border' => '#DCD0EC',
                'role_short' => 'Dosen · Jabatan: Kalab',
                'modul' => 'Modul: Approval Pengajuan & Permintaan',
            ];
        }
        if ($jabKey === 'kajur') {
            return [
                'theme' => '#2D3A4A',
                'box' => '#3B4A5C',
                'soft' => '#EBEEF2',
                'border' => '#D5DCE4',
                'role_short' => 'Dosen · Jabatan: Kajur',
                'modul' => 'Modul: Monitoring Jurusan',
            ];
        }
        return [
            'theme' => '#1A44A7',
            'box' => '#2753BA',
            'soft' => '#EBF0FA',
            'border' => '#D0DCF5',
            'role_short' => 'Dosen Pengampu',
            'modul' => 'Modul: Permintaan & Usulan 2 Semester',
        ];
    }

    $themes = [
        'admin' => [
            'theme' => '#165B33',
            'box' => '#226B40',
            'soft' => '#E9F3EC',
            'border' => '#D2E5D8',
            'role_short' => 'Administrator',
            'modul' => 'Modul: Master Data & Business Rule',
        ],
        'teknisi' => [
            'theme' => '#165B33',
            'box' => '#226B40',
            'soft' => '#E9F3EC',
            'border' => '#D2E5D8',
            'role_short' => 'Teknisi Lab',
            'modul' => in_array($activeKey, ['masuk', 'keluar', 'tinjau'], true) ? 'Modul: Logistik & Pelayanan Lab' : 'Modul: Bahan Habis Pakai',
        ],
        'plp' => [
            'theme' => '#0F5C5C',
            'box' => '#1B6B6B',
            'soft' => '#E6F2F2',
            'border' => '#CCE3E3',
            'role_short' => 'PLP Laboratorium',
            'modul' => in_array($activeKey, ['cek_barang'], true) ? 'Modul: Verifikasi & Opname' : (in_array($activeKey, ['pengajuan', 'pembelian'], true) ? 'Modul: Pengajuan Pengadaan 2 Smt' : 'Modul: Permintaan & Stok Lab'),
        ],
        'mhs' => [
            'theme' => '#1A44A7',
            'box' => '#2753BA',
            'soft' => '#EBF0FA',
            'border' => '#D0DCF5',
            'role_short' => 'Mahasiswa (Individu)',
            'modul' => 'Modul: Pengambilan Praktikum Individu',
        ],
    ];
    return $themes[$roleKey] ?? $themes['teknisi'];
}

function role_menu_groups($roleKey, $flatMenus, $jabKey = '') {
    if ($roleKey === 'dosen') {
        if ($jabKey === 'kalab') {
            $map = [
                'JABATAN: KEPALA LAB' => ['approval_kalab', 'semua_pengajuan', 'peringatan'],
                'PERAN DOSEN PENGAMPU' => ['pengajuan_dosen'],
            ];
        } elseif ($jabKey === 'kajur') {
            $map = [
                'JABATAN: KETUA JURUSAN' => ['dashboard', 'stok', 'rak', 'rekap'],
                'PERAN DOSEN PENGAMPU' => ['pengajuan_dosen'],
            ];
        } else {
            $map = [
                'DOSEN PENGAMPU' => ['pengajuan_dosen'],
            ];
        }
    } else {
        $groups = [
            'admin' => [
                'MASTER DATA & AKUN' => ['admin_dashboard', 'admin_users', 'admin_akademik'],
                'PENGATURAN SISTEM' => ['admin_aturan'],
            ],
            'teknisi' => [
                'INVENTARIS LAB' => ['dashboard', 'stok', 'rak', 'peringatan'],
                'LOGISTIK & PELAYANAN' => ['masuk', 'keluar', 'tinjau', 'stockopname'],
            ],
            'plp' => [
                'PERMINTAAN & PENGAJUAN' => ['dashboard', 'tinjau', 'pengajuan', 'pembelian', 'rekap'],
                'VERIFIKASI & OPNAME' => ['cek_barang'],
                'STOK LABORATORIUM' => ['stok', 'rak', 'peringatan'],
            ],
            'mhs' => [
                'AKUN INDIVIDU MAHASISWA' => ['permintaan'],
            ],
        ];
        $map = $groups[$roleKey] ?? ['MENU UTAMA' => array_keys($flatMenus)];
    }
    $result = [];
    foreach ($map as $gName => $keys) {
        foreach ($keys as $k) {
            if (isset($flatMenus[$k])) {
                $result[$gName][$k] = $flatMenus[$k];
            }
        }
    }
    return $result ?: ['MENU UTAMA' => $flatMenus];
}

function sidebar_badges($roleKey, $jabKey = '') {
    $d = db();
    $lowCount = count(array_filter($d['barang'], 'low'));
    $waitDosen = count(array_filter($d['pengajuan_dosen'], fn($r) => ($r['status'] ?? '') === 'menunggu'));
    $waitMhs = count(array_filter($d['permintaan'], fn($r) => ($r['status'] ?? '') === 'menunggu'));
    $waitKalab = count(array_filter($d['pengajuan'], fn($r) => ($r['status'] ?? '') === 'menunggu'));
    $waitOpname = count(array_filter($d['stockopname'], fn($r) => ($r['status'] ?? '') === 'menunggu_cek_plp'));
    $readyBeli = count(array_filter($d['pengajuan'], fn($r) => ($r['status'] ?? '') === 'disetujui' && ($r['pesan'] ?? 0) > 0));

    return [
        'peringatan' => $lowCount,
        'masuk' => count(array_filter($d['pembelian'], fn($r) => ($r['status'] ?? '') === 'diajukan')),
        'tinjau' => $roleKey === 'plp' ? ($waitDosen + $waitMhs) : $waitMhs,
        'approval_kalab' => $waitKalab,
        'cek_barang' => $waitOpname,
        'pembelian' => $readyBeli,
        'pengajuan_dosen' => count(array_filter($d['pengajuan_dosen'], fn($r) => ($r['oleh_email'] ?? '') === ($_SESSION['user']['email'] ?? '') && ($r['status'] ?? '') === 'menunggu')),
        'permintaan' => count(array_filter($d['permintaan'], fn($r) => ($r['oleh_email'] ?? '') === ($_SESSION['user']['email'] ?? '') && ($r['status'] ?? '') === 'menunggu')),
    ];
}

function page_header_cta($roleKey, $activeKey, $roleFile, $jabKey = '') {
    if ($roleKey === 'admin') {
        if ($activeKey === 'admin_dashboard') return ['label' => 'Mutasi Jabatan Dosen', 'url' => "admin.php?fitur=admin_users"];
    } elseif ($roleKey === 'teknisi') {
        if (in_array($activeKey, ['dashboard', 'stok'], true)) return ['label' => '+ Penerimaan Bahan Lab', 'url' => "$roleFile?fitur=masuk"];
        if ($activeKey === 'rak') return ['label' => '+ Atur Kapasitas Rak', 'url' => "$roleFile?fitur=rak#nama_rak"];
    } elseif ($roleKey === 'plp') {
        if ($activeKey === 'dashboard') return ['label' => '+ Buat Pengajuan 2 Smt', 'url' => "$roleFile?fitur=pengajuan"];
        if ($activeKey === 'tinjau') return ['label' => '+ Susun Pengajuan ke Kalab', 'url' => "$roleFile?fitur=pengajuan"];
    } elseif ($roleKey === 'dosen' && $jabKey === 'kalab') {
        if ($activeKey === 'semua_pengajuan') return ['label' => 'Approval Pengajuan PLP', 'url' => "$roleFile?fitur=approval_kalab"];
    } elseif ($roleKey === 'dosen' && $jabKey === 'kajur') {
        if ($activeKey === 'dashboard') return ['label' => 'Lihat Rekap Lengkap', 'url' => "$roleFile?fitur=rekap"];
    }
    return null;
}

function render_wf_steps($title, $steps, $activeStep = 1, $note = '') {
    echo '<div class="wf-steps-box">';
    if ($title !== '') echo '<div class="wf-steps-title">' . e($title) . '</div>';
    echo '<div class="wf-steps">';
    foreach ($steps as $idx => $label) {
        $num = $idx + 1;
        $doneClass = $num <= $activeStep ? ' done' : '';
        echo '<div class="wf-step' . $doneClass . '"><span class="wf-step-num">' . e($num) . '</span><span class="wf-step-label">' . e($label) . '</span></div>';
    }
    echo '</div>';
    if ($note !== '') echo '<p class="wf-steps-note">' . e($note) . '</p>';
    echo '</div>';
}

function req_table($rows, $tipe = '', $aksi = false, $approverLabel = 'Tinjau') {
    if (!$rows) { echo '<p class="empty">Belum ada data pengajuan pengadaan 2 semester.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Penyusun</th><th>Bahan &amp; Lab</th><th>Ukuran / Mhs &amp; Pcs</th><th>Total Permintaan</th><th>Jumlah Pengajuan (Ke Pusat)</th><th>Stok Min</th><th>Rincian Kalkulasi</th><th>Status</th>' . ($aksi ? '<th>' . e($approverLabel) . '</th>' : '') . '</tr>';
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
        $labBadge = !empty($r['lab']) ? '<br><small class="muted">' . e($r['lab']) . '</small>' : '';
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td><strong>' . e($r['nama']) . '</strong> (' . e($r['kode']) . ')' . $labBadge . '</td><td>' . $ukMhs . '</td><td>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</td><td><strong>' . $pesanTxt . '</strong></td><td>' . $minTxt . '</td><td>' . e($r['ket']) . $extra . '</td><td>' . chip($r['status']) . '</td>';
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
    if (!$rows) { echo '<p class="empty">Belum ada data permintaan penggunaan atau usulan kebutuhan bahan dari dosen.</p>'; return; }
    echo '<div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Dosen</th><th>Jenis &amp; Lab Tujuan</th><th>Nama Bahan</th><th>Ukuran/Mhs &amp; Pcs</th><th>Total Kebutuhan</th><th>Kelas / Keperluan</th><th>Matkul / Kegiatan</th><th>Status</th>' . ($aksi ? '<th>Tinjau &amp; Set Stok Min (PLP)</th>' : '') . '</tr>';
    foreach ($rows as $r) {
        $cat = ($r['catatan'] ?? '') !== '' ? '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>' : '';
        $jenisTrans = ($r['jenis_transaksi'] ?? 'usulan_2smt') === 'permintaan_pakai'
            ? '<span class="chip ok">Permintaan Pakai</span>'
            : '<span class="chip warn">Usulan 2 Smt</span>';
        $katPakai = !empty($r['kategori_penggunaan']) ? '<br><small class="muted">' . e(ucfirst($r['kategori_penggunaan'])) . '</small>' : '';
        $labTxt = !empty($r['lab']) ? '<br><small class="muted">' . e($r['lab']) . '</small>' : '';
        $ukInfo = isset($r['ukuran_per_mhs']) && $r['ukuran_per_mhs'] > 0
            ? e($r['ukuran_per_mhs'] . ' ' . $r['satuan'] . ' × ' . ($r['jml_mhs'] ?? 1) . ' org') . '<br><small class="muted">1 pcs = ' . e(($r['ukuran_pcs'] ?? 1) . ' ' . $r['satuan']) . ' (≈ ' . e($r['jml_pcs'] ?? 1) . ' pcs)</small>'
            : '-';
        echo '<tr><td>' . e($r['id']) . '</td><td>' . e($r['tanggal']) . '</td><td>' . e($r['oleh']) . '</td><td>' . $jenisTrans . $katPakai . $labTxt . '</td><td><strong>' . e($r['nama']) . '</strong> (' . e($r['kode']) . ')</td><td>' . $ukInfo . '</td><td><strong>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</strong></td><td>' . e($r['kelas'] ?? '-') . '</td><td>' . e($r['sub_praktikum'] ?? '-') . $cat . '</td><td>' . chip($r['status']) . '</td>';
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

function mhs_table($rows, $aksi = false, $allowAmbilSisa = false, $bulk = false) {
    if (!$rows) { echo '<p class="empty">Belum ada permintaan pengambilan bahan praktikum individu dari mahasiswa.</p>'; return; }

    $isMhsView = ($allowAmbilSisa || norm_role($_SESSION['user']['role'] ?? '') === 'mhs');

    static $userNimMap = null;
    if ($userNimMap === null) {
        $userNimMap = [];
        $dTemp = db();
        foreach ($dTemp['users'] ?? [] as $u) {
            if (!empty($u['email'])) $userNimMap[$u['email']] = $u['nim'] ?? '';
        }
    }

    echo '<div class="tablewrap"><table><tr>'
        . ($bulk ? '<th class="chk"><input type="checkbox" class="cekall" aria-label="Pilih semua"></th>' : '')
        . '<th>No</th><th>Tgl Otomatis</th><th>NIM Individu</th><th>Mahasiswa (Tidak Diwakilkan)</th>'
        . (!$isMhsView ? '<th>Kelas</th><th>Sub Praktikum</th>' : '')
        . '<th>Bahan Diambil</th><th>Total Jatah</th><th>Jumlah Diambil</th><th>Sisa / Jatah Minus &amp; Kompensasi</th><th>Status</th>'
        . ($aksi ? '<th>Tinjau</th>' : '')
        . ($allowAmbilSisa ? '<th>Ambil Sisa</th>' : '')
        . '</tr>';
    foreach ($rows as $r) {
        $extra = '';
        if (!empty($r['disetujui_oleh'])) {
            $extra .= '<br><small class="muted">Oleh: ' . e($r['disetujui_oleh']) . '</small>';
        }
        if (($r['catatan'] ?? '') !== '') {
            $extra .= '<br><small class="muted">Catatan: ' . e($r['catatan']) . '</small>';
        }
        $totalJatah = $r['total_jatah'] ?? $r['jumlah'];
        $sisaAmbil = (int)($r['sisa_ambil'] ?? 0);
        $jatahMinus = (int)($r['jatah_minus'] ?? ($sisaAmbil < 0 ? abs($sisaAmbil) : 0));
        $kompensasi = trim((string)($r['kompensasi'] ?? ''));
        $nim = !empty($r['nim']) ? $r['nim'] : ($userNimMap[$r['oleh_email'] ?? ''] ?? '-');
        $chkCell = $bulk ? '<td class="chk">' . ($r['status'] === 'menunggu' ? '<input type="checkbox" class="pilih" name="ids[]" value="' . e($r['id']) . '" form="bulk" aria-label="Pilih konfirmasi #' . e($r['id']) . '">' : '') . '</td>' : '';

        if ($jatahMinus > 0) {
            $sisaHtml = '<span class="chip bad">Minus -' . e($jatahMinus . ' ' . $r['satuan']) . ' (Over-Limit)</span>'
                . ($kompensasi !== '' ? '<br><small class="muted">Kompensasi/Denda: ' . e($kompensasi) . '</small>' : '');
        } elseif ($sisaAmbil > 0) {
            $sisaHtml = '<span class="chip warn">' . e($sisaAmbil . ' ' . $r['satuan']) . '</span>';
        } else {
            $sisaHtml = '<span class="chip ok">0 ' . e($r['satuan']) . ' (Tepat Jatah)</span>';
        }

        echo '<tr>' . $chkCell
            . '<td>' . e($r['id']) . '</td>'
            . '<td>' . e($r['tanggal']) . '</td>'
            . '<td><strong>' . e($nim) . '</strong></td>'
            . '<td>' . e($r['oleh']) . '<br><small class="muted">Akun Individu</small></td>'
            . (!$isMhsView ? '<td>' . e($r['kelas'] ?? '-') . '</td><td>' . e($r['sub_praktikum'] ?? $r['ket'] ?? '-') . $extra . '</td>' : '')
            . '<td>' . e($r['nama']) . '</td>'
            . '<td>' . e($totalJatah . ' ' . $r['satuan']) . '</td>'
            . '<td><strong>' . e($r['jumlah'] . ' ' . $r['satuan']) . '</strong></td>'
            . '<td>' . $sisaHtml . '</td>'
            . '<td>' . chip($r['status']) . '</td>';
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

/* ---- ROLE 1: ADMINISTRATOR (Master Data, Mutasi Jabatan, Business Rule & Time Frame) ---- */
function fitur_admin_dashboard() {
    $d = db();
    $title = 'Dashboard Administrator';
    $active = 'admin_dashboard';
    $users = $d['users'];
    $dosenCount = count(array_filter($users, fn($u) => norm_role($u['role']) === 'dosen'));
    $mhsCount = count(array_filter($users, fn($u) => norm_role($u['role']) === 'mhs'));
    $br = $d['business_rules'];
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="cards">
        <div>
            <span>Total pengguna (5 Role)</span>
            <strong><?= str_pad((string)count($users), 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Entitas Dosen (Biasa/Kalab/Kajur)</span>
            <strong><?= str_pad((string)$dosenCount, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Mata kuliah &amp; silabus/jobsheet</span>
            <strong><?= str_pad((string)count($d['master_matkul']), 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Time frame usulan 2 semester</span>
            <strong style="font-size:20px;margin-top:6px"><?= !empty($br['periode_aktif']) ? 'DIBUKA (' . e($br['bulan_mulai'] . '–' . $br['bulan_selesai']) . ')' : 'DITUTUP' ?></strong>
        </div>
    </div>

    <div class="grid2">
        <div class="box">
            <h3>Pejabat Struktural Dosen Aktif Saat Ini</h3>
            <p class="muted" style="font-size:13px;margin:0 0 12px">Sesuai Notulensi: Kalab dan Kajur merupakan akun Dosen dengan atribut jabatan dinamis yang dikelola oleh Administrator.</p>
            <div class="tablewrap">
                <table>
                    <tr><th>Nama Dosen</th><th>Email / NIP</th><th>Atribut Jabatan</th><th>Penempatan Lab / Unit</th></tr>
                    <?php foreach ($users as $u): if (norm_role($u['role']) !== 'dosen') continue; $jb = norm_jabatan($u); ?>
                    <tr>
                        <td><strong><?= e($u['nama']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="chip <?= $jb === 'kalab' ? 'warn' : ($jb === 'kajur' ? 'ok' : '') ?>"><?= e(JABATAN_DOSEN[$jb] ?? 'Dosen Biasa') ?></span></td>
                        <td><?= e($u['lab'] ?? 'Lab Permesinan') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div style="margin-top:12px">
                <a class="btn" href="admin.php?fitur=admin_users">Kelola Pengguna &amp; Mutasi Jabatan</a>
            </div>
        </div>

        <div class="box">
            <h3>Ringkasan Business Rule &amp; Pemetaan Akademik</h3>
            <div class="tablewrap">
                <table>
                    <tr><th>Aturan Bisnis</th><th>Konfigurasi Aktif</th></tr>
                    <tr><td><strong>Periode Pengisian Usulan 2 Semester</strong></td><td><?= e($br['bulan_mulai'] . ' – ' . $br['bulan_selesai'] . ' (' . $br['tahun_ajaran'] . ')') ?> <?= !empty($br['periode_aktif']) ? '<span class="chip ok">Terbuka</span>' : '<span class="chip bad">Tertutup</span>' ?></td></tr>
                    <tr><td><strong>Visibilitas Stok Bahan</strong></td><td>Dibatasi (Dosen Biasa tidak melihat stok; hanya PLP, Teknisi, Admin, Kalab/Kajur)</td></tr>
                    <tr><td><strong>Pengambilan Mahasiswa</strong></td><td>Wajib Akun Individu (1 mahasiswa, tidak dapat diwakilkan)</td></tr>
                    <tr><td><strong>Kebijakan Over-Limit Mahasiswa</strong></td><td><?= e($br['kebijakan_overlimit']) ?> (Rp <?= number_format((int)($br['denda_per_satuan'] ?? 5000), 0, ',', '.') ?>/satuan)</td></tr>
                    <tr><td><strong>Penyimpanan Pengadaan</strong></td><td>Langsung didistribusikan ke masing-masing Laboratorium (tanpa gudang pusat)</td></tr>
                </table>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px">
                <a class="btn sec" href="admin.php?fitur=admin_akademik">Kelola Matkul &amp; Prodi</a>
                <a class="btn sec" href="admin.php?fitur=admin_aturan">Ubah Time Frame &amp; Aturan</a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
}

function fitur_admin_users() {
    $redirect = 'admin.php?fitur=admin_users';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $aksi = $_POST['aksi_admin'] ?? '';

        if ($aksi === 'mutasi_jabatan') {
            $email = trim($_POST['email'] ?? '');
            $jabBaru = $_POST['jabatan_baru'] ?? 'biasa';
            $labBaru = trim($_POST['lab_baru'] ?? 'Lab Permesinan');
            $idx = find_idx($d['users'], 'email', $email);
            if ($idx === null || !in_array($jabBaru, ['biasa', 'kalab', 'kajur'], true)) {
                go($redirect, 'Data dosen atau jabatan tidak valid.', 'bad');
            }
            $jabLama = norm_jabatan($d['users'][$idx]);
            // Jika mengangkat Kalab di lab yang sama atau Kajur baru, mutasikan pejabat lama menjadi Dosen Biasa
            if (in_array($jabBaru, ['kalab', 'kajur'], true)) {
                foreach ($d['users'] as &$uOther) {
                    if ($uOther['email'] === $email || norm_role($uOther['role']) !== 'dosen') continue;
                    if ($jabBaru === 'kajur' && norm_jabatan($uOther) === 'kajur') {
                        $uOther['jabatan'] = 'biasa';
                    } elseif ($jabBaru === 'kalab' && norm_jabatan($uOther) === 'kalab' && ($uOther['lab'] ?? '') === $labBaru) {
                        $uOther['jabatan'] = 'biasa';
                    }
                }
                unset($uOther);
            }
            $d['users'][$idx]['role'] = 'dosen';
            $d['users'][$idx]['jabatan'] = $jabBaru;
            $d['users'][$idx]['lab'] = $labBaru;
            $d['mutasi_log'][] = [
                'waktu' => date('Y-m-d H:i'),
                'nama' => $d['users'][$idx]['nama'],
                'email' => $email,
                'dari' => JABATAN_DOSEN[$jabLama] ?? $jabLama,
                'ke' => JABATAN_DOSEN[$jabBaru] ?? $jabBaru,
                'lab' => $labBaru,
                'oleh' => $_SESSION['user']['nama'],
            ];
            save($d);
            go($redirect, "Mutasi jabatan berhasil: {$d['users'][$idx]['nama']} kini menjabat sebagai " . (JABATAN_DOSEN[$jabBaru] ?? $jabBaru) . " ($labBaru).");
        } elseif ($aksi === 'tambah_user') {
            $nama = trim($_POST['nama'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $role = $_POST['role'] ?? 'mhs';
            $jabatan = $role === 'dosen' ? ($_POST['jabatan'] ?? 'biasa') : '';
            $nimNip = trim($_POST['nim_nip'] ?? '');
            $lab = trim($_POST['lab'] ?? 'Lab Permesinan');
            $pass = $_POST['password'] ?? 'password';
            if ($nama === '' || $email === '' || find_idx($d['users'], 'email', $email) !== null) {
                go($redirect, 'Nama dan email wajib diisi (email tidak boleh duplikat).', 'bad');
            }
            $d['users'][] = [
                'email' => $email,
                'nama' => $nama,
                'role' => norm_role($role),
                'jabatan' => $jabatan,
                'nim' => $role === 'mhs' ? $nimNip : '',
                'nip' => $role !== 'mhs' ? $nimNip : '',
                'lab' => $lab,
                'matkul_diampu' => $role === 'dosen' ? ['MK-01'] : [],
                'hash' => password_hash($pass !== '' ? $pass : 'password', PASSWORD_DEFAULT),
            ];
            save($d);
            go($redirect, "Akun pengguna baru ($nama - $email) berhasil ditambahkan.");
        }
    }

    $d = db();
    $title = 'Kelola Pengguna & Mutasi Jabatan (5 Role Utama)';
    $active = 'admin_users';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="grid2">
        <div class="box form">
            <h3>Mutasi / Pergantian Jabatan Dosen (Dosen Biasa &harr; Kalab &harr; Kajur)</h3>
            <p class="muted" style="font-size:12.5px;margin:0 0 12px">Sesuai Notulensi: Akun Kalab dan Kajur digabung ke dalam entitas Dosen. Hak akses berubah dinamis mengikuti jabatan yang ditetapkan di sini.</p>
            <form method="post">
                <?= field_csrf() ?>
                <input type="hidden" name="aksi_admin" value="mutasi_jabatan">
                <label for="m_email">Pilih Akun Dosen</label>
                <select id="m_email" name="email" required>
                    <?php foreach ($d['users'] as $u): if (norm_role($u['role']) !== 'dosen') continue; $jb = norm_jabatan($u); ?>
                    <option value="<?= e($u['email']) ?>"><?= e($u['nama'] . ' (' . $u['email'] . ') — Saat ini: ' . (JABATAN_DOSEN[$jb] ?? 'Dosen Biasa') . ' [' . ($u['lab'] ?? 'Lab Permesinan') . ']') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="grid2">
                    <div>
                        <label for="m_jabatan">Tetapkan Jabatan Baru</label>
                        <select id="m_jabatan" name="jabatan_baru" required>
                            <?php foreach (JABATAN_DOSEN as $k => $lbl): ?>
                            <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="m_lab">Penempatan Laboratorium / Jurusan</label>
                        <select id="m_lab" name="lab_baru">
                            <?php foreach (array_merge(LAB_LIST, ['Kantor Jurusan Teknik Mesin']) as $lb): ?>
                            <option value="<?= e($lb) ?>"><?= e($lb) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button class="btn" type="submit">Simpan Mutasi Jabatan</button>
            </form>
        </div>

        <div class="box form">
            <h3>Tambah Akun Pengguna Baru (5 Role Utama)</h3>
            <form method="post">
                <?= field_csrf() ?>
                <input type="hidden" name="aksi_admin" value="tambah_user">
                <div class="grid2">
                    <div><label for="u_nama">Nama Lengkap *</label><input id="u_nama" name="nama" placeholder="Nama lengkap" required></div>
                    <div><label for="u_email">Email / Username *</label><input id="u_email" name="email" type="email" placeholder="nama@polban.ac.id" required></div>
                </div>
                <div class="grid3">
                    <div>
                        <label for="u_role">Role Utama *</label>
                        <select id="u_role" name="role" onchange="document.getElementById('wrap_jab').style.display=this.value==='dosen'?'block':'none'">
                            <option value="dosen">Dosen</option>
                            <option value="plp">PLP</option>
                            <option value="teknisi">Teknisi</option>
                            <option value="mhs">Mahasiswa (Individu)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div id="wrap_jab">
                        <label for="u_jab">Jabatan Dosen</label>
                        <select id="u_jab" name="jabatan">
                            <?php foreach (JABATAN_DOSEN as $k => $lbl): ?>
                            <option value="<?= e($k) ?>"><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="u_nim">NIM / NIP</label>
                        <input id="u_nim" name="nim_nip" placeholder="NIM (Mhs) / NIP">
                    </div>
                </div>
                <div class="grid2">
                    <div>
                        <label for="u_lab">Penempatan Lab / Unit</label>
                        <select id="u_lab" name="lab">
                            <?php foreach (array_merge(LAB_LIST, ['Kantor Jurusan Teknik Mesin']) as $lb): ?>
                            <option value="<?= e($lb) ?>"><?= e($lb) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="u_pw">Password Awal</label>
                        <input id="u_pw" name="password" value="password">
                    </div>
                </div>
                <button class="btn" type="submit">+ Tambah Pengguna</button>
            </form>
        </div>
    </div>

    <div class="box">
        <h3>Daftar Seluruh Pengguna Sistem (5 Role Utama)</h3>
        <div class="tablewrap">
            <table>
                <tr><th>Nama</th><th>Email</th><th>Role Utama</th><th>Atribut Jabatan (Khusus Dosen)</th><th>NIM / NIP</th><th>Penempatan Lab / Unit</th></tr>
                <?php foreach ($d['users'] as $u):
                    $rKey = norm_role($u['role']);
                    $jb = norm_jabatan($u);
                ?>
                <tr>
                    <td><strong><?= e($u['nama']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="chip ok"><?= e(ROLES[$rKey] ?? strtoupper($rKey)) ?></span></td>
                    <td><?= $rKey === 'dosen' ? '<span class="chip ' . ($jb !== 'biasa' ? 'warn' : 'ok') . '">' . e(JABATAN_DOSEN[$jb] ?? 'Dosen Biasa') . '</span>' : '-' ?></td>
                    <td><?= e(($u['nim'] ?? '') ?: ($u['nip'] ?? '-')) ?></td>
                    <td><?= e($u['lab'] ?? 'Lab Permesinan') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <?php if (!empty($d['mutasi_log'])): ?>
    <div class="box">
        <h3>Riwayat Mutasi Jabatan Dosen</h3>
        <div class="tablewrap">
            <table>
                <tr><th>Waktu</th><th>Dosen</th><th>Jabatan Sebelumnya</th><th>Jabatan Baru</th><th>Laboratorium / Unit</th><th>Diubah Oleh</th></tr>
                <?php foreach (array_reverse($d['mutasi_log']) as $ml): ?>
                <tr>
                    <td><?= e($ml['waktu']) ?></td>
                    <td><strong><?= e($ml['nama']) ?></strong> (<?= e($ml['email']) ?>)</td>
                    <td><?= e($ml['dari']) ?></td>
                    <td><span class="chip ok"><?= e($ml['ke']) ?></span></td>
                    <td><?= e($ml['lab']) ?></td>
                    <td><?= e($ml['oleh']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif;
    require __DIR__ . '/partials/footer.php';
}

function fitur_admin_akademik() {
    $redirect = 'admin.php?fitur=admin_akademik';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $aksi = $_POST['aksi_akad'] ?? '';
        if ($aksi === 'tambah_matkul') {
            $kode = strtoupper(trim($_POST['kode'] ?? ''));
            $nama = trim($_POST['nama'] ?? '');
            $prodi = trim($_POST['prodi'] ?? '');
            $smt = trim($_POST['semester'] ?? '3 (Ganjil) & 4 (Genap)');
            $lab = trim($_POST['lab'] ?? 'Lab Permesinan');
            $jobsheet = trim($_POST['jobsheet'] ?? '');
            $dosenEmail = trim($_POST['dosen_email'] ?? 'dosen@polban.ac.id');
            $bahanKode = array_values(array_filter((array)($_POST['bahan_kode'] ?? [])));
            if ($kode === '' || $nama === '') go($redirect, 'Kode dan Nama Mata Kuliah wajib diisi.', 'bad');
            $d['master_matkul'][] = [
                'kode' => $kode,
                'nama' => $nama,
                'prodi' => $prodi,
                'semester' => $smt,
                'lab' => $lab,
                'jobsheet' => $jobsheet,
                'dosen_email' => $dosenEmail,
                'bahan_kode' => $bahanKode ?: array_column($d['barang'], 'kode'),
            ];
            save($d);
            go($redirect, "Mata kuliah & silabus/jobsheet $nama ($kode) berhasil ditambahkan.");
        } elseif ($aksi === 'tambah_prodi') {
            $kode = strtoupper(trim($_POST['kode_prodi'] ?? ''));
            $nama = trim($_POST['nama_prodi'] ?? '');
            $jenjang = trim($_POST['jenjang'] ?? 'D3');
            if ($kode === '' || $nama === '') go($redirect, 'Kode dan Nama Program Studi wajib diisi.', 'bad');
            $d['master_prodi'][] = ['kode' => $kode, 'nama' => $nama, 'jenjang' => $jenjang];
            save($d);
            go($redirect, "Program Studi $nama berhasil ditambahkan.");
        }
    }

    $d = db();
    $title = 'Master Mata Kuliah, Silabus/Jobsheet, Prodi & Semester';
    $active = 'admin_akademik';
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="grid2">
        <div class="box form">
            <h3>Tambah Mata Kuliah, Pemetaan Semester &amp; Silabus/Jobsheet Lab</h3>
            <p class="muted" style="font-size:12.5px;margin:0 0 12px">Sesuai Notulensi: Dosen biasa hanya dapat memilih bahan yang digunakan pada mata kuliah/praktikum yang diampunya serta laboratorium tujuan.</p>
            <form method="post">
                <?= field_csrf() ?>
                <input type="hidden" name="aksi_akad" value="tambah_matkul">
                <div class="grid2">
                    <div><label for="mk_kode">Kode MK *</label><input id="mk_kode" name="kode" placeholder="MK-04" required></div>
                    <div><label for="mk_nama">Nama Mata Kuliah / Praktikum *</label><input id="mk_nama" name="nama" placeholder="Praktik CNC Milling" required></div>
                </div>
                <div class="grid2">
                    <div>
                        <label for="mk_prodi">Program Studi</label>
                        <select id="mk_prodi" name="prodi">
                            <?php foreach ($d['master_prodi'] as $pr): ?>
                            <option value="<?= e($pr['nama']) ?>"><?= e($pr['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="mk_smt">Pemetaan 2 Semester</label>
                        <input id="mk_smt" name="semester" value="3 (Ganjil) & 4 (Genap)" required>
                    </div>
                </div>
                <div class="grid2">
                    <div>
                        <label for="mk_lab">Laboratorium Tujuan</label>
                        <select id="mk_lab" name="lab">
                            <?php foreach (LAB_LIST as $lb): ?>
                            <option value="<?= e($lb) ?>"><?= e($lb) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="mk_dosen">Dosen Pengampu</label>
                        <select id="mk_dosen" name="dosen_email">
                            <?php foreach ($d['users'] as $u): if (norm_role($u['role']) !== 'dosen') continue; ?>
                            <option value="<?= e($u['email']) ?>"><?= e($u['nama'] . ' (' . $u['email'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label for="mk_js">Acuan Silabus / Jobsheet Praktikum</label>
                <input id="mk_js" name="jobsheet" placeholder="Contoh: Uji Tarik, Uji Keras, Bubut Bertingkat" required>

                <label>Bahan Habis Pakai yang Digunakan pada Mata Kuliah Ini</label>
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px">
                    <?php foreach ($d['barang'] as $b): ?>
                    <label style="display:inline-flex;align-items:center;gap:6px;background:#F8FAFC;border:1px solid #CBD5E1;padding:6px 10px;border-radius:8px;cursor:pointer">
                        <input type="checkbox" name="bahan_kode[]" value="<?= e($b['kode']) ?>" checked style="width:auto;margin:0">
                        <span><?= e($b['nama'] . ' (' . $b['kode'] . ')') ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button class="btn" type="submit">+ Simpan Mata Kuliah &amp; Jobsheet</button>
            </form>
        </div>

        <div>
            <div class="box form">
                <h3>Tambah Program Studi</h3>
                <form method="post">
                    <?= field_csrf() ?>
                    <input type="hidden" name="aksi_akad" value="tambah_prodi">
                    <div class="grid3">
                        <div><label for="pr_kode">Kode</label><input id="pr_kode" name="kode_prodi" placeholder="D4-TRM" required></div>
                        <div><label for="pr_nama">Nama Program Studi</label><input id="pr_nama" name="nama_prodi" placeholder="D4 Teknologi Rekayasa Mesin" required></div>
                        <div><label for="pr_jj">Jenjang</label><select id="pr_jj" name="jenjang"><option value="D3">D3</option><option value="D4">D4</option></select></div>
                    </div>
                    <button class="btn" type="submit">+ Tambah Program Studi</button>
                </form>
            </div>

            <div class="box">
                <h3>Daftar Program Studi Jurusan Teknik Mesin</h3>
                <div class="tablewrap">
                    <table>
                        <tr><th>Kode</th><th>Program Studi</th><th>Jenjang</th></tr>
                        <?php foreach ($d['master_prodi'] as $pr): ?>
                        <tr><td><strong><?= e($pr['kode']) ?></strong></td><td><?= e($pr['nama']) ?></td><td><span class="chip ok"><?= e($pr['jenjang']) ?></span></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box">
        <h3>Pemetaan Mata Kuliah, Silabus/Jobsheet, Laboratorium &amp; Bahan yang Diampu</h3>
        <div class="tablewrap">
            <table>
                <tr><th>Kode</th><th>Mata Kuliah</th><th>Prodi &amp; Semester</th><th>Laboratorium</th><th>Silabus / Jobsheet</th><th>Dosen Pengampu</th><th>Bahan Terkait</th></tr>
                <?php foreach ($d['master_matkul'] as $mk): ?>
                <tr>
                    <td><strong><?= e($mk['kode']) ?></strong></td>
                    <td><strong><?= e($mk['nama']) ?></strong></td>
                    <td><?= e($mk['prodi']) ?><br><small class="muted">Smt: <?= e($mk['semester']) ?></small></td>
                    <td><span class="chip ok"><?= e($mk['lab']) ?></span></td>
                    <td><?= e($mk['jobsheet']) ?></td>
                    <td><?= e($mk['dosen_email']) ?></td>
                    <td><?= e(implode(', ', $mk['bahan_kode'] ?? [])) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php
    require __DIR__ . '/partials/footer.php';
}

function fitur_admin_aturan() {
    $redirect = 'admin.php?fitur=admin_aturan';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $d['business_rules'] = [
            'periode_aktif' => !empty($_POST['periode_aktif']),
            'bulan_mulai' => trim($_POST['bulan_mulai'] ?? 'Maret'),
            'bulan_selesai' => trim($_POST['bulan_selesai'] ?? 'Mei'),
            'tahun_ajaran' => trim($_POST['tahun_ajaran'] ?? '2026/2027 (2 Semester)'),
            'kebijakan_overlimit' => trim($_POST['kebijakan_overlimit'] ?? 'Izinkan dengan Pencatatan Jatah Minus & Kompensasi/Denda'),
            'denda_per_satuan' => max(0, post_int('denda_per_satuan')),
        ];
        save($d);
        go($redirect, 'Konfigurasi Business Rule & Time Frame Pengisian Usulan 2 Semester berhasil diperbarui.');
    }

    $d = db();
    $br = $d['business_rules'];
    $title = 'Business Rule & Time Frame Pengadaan 2 Semester';
    $active = 'admin_aturan';
    require __DIR__ . '/partials/header.php';
    ?>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <h3>Pengaturan Batasan Waktu (Time Frame) &amp; Aturan Bisnis Jurusan Teknik Mesin</h3>
        <p class="muted" style="font-size:13px;margin:0 0 16px">Sesuai Notulensi: Sistem wajib menerapkan periode waktu pengisian usulan kebutuhan 2 semester oleh dosen sebelum semester berjalan (misal rentang Maret–Mei) serta aturan penanganan kelebihan jatah (over-limit) mahasiswa.</p>

        <div class="grid3">
            <div>
                <label for="periode_aktif">Status Periode Usulan Kebutuhan 2 Semester</label>
                <select id="periode_aktif" name="periode_aktif">
                    <option value="1"<?= !empty($br['periode_aktif']) ? ' selected' : '' ?>>DIBUKA (Dosen Dapat Menginput Usulan 2 Semester)</option>
                    <option value="0"<?= empty($br['periode_aktif']) ? ' selected' : '' ?>>DITUTUP (Di Luar Time Frame Pengisian)</option>
                </select>
            </div>
            <div>
                <label for="bulan_mulai">Bulan Mulai Time Frame</label>
                <input id="bulan_mulai" name="bulan_mulai" value="<?= e($br['bulan_mulai']) ?>" required>
            </div>
            <div>
                <label for="bulan_selesai">Bulan Selesai Time Frame</label>
                <input id="bulan_selesai" name="bulan_selesai" value="<?= e($br['bulan_selesai']) ?>" required>
            </div>
        </div>

        <div class="grid3">
            <div>
                <label for="tahun_ajaran">Tahun Ajaran Pengadaan (2 Semester)</label>
                <input id="tahun_ajaran" name="tahun_ajaran" value="<?= e($br['tahun_ajaran']) ?>" required>
            </div>
            <div>
                <label for="kebijakan_overlimit">Mekanisme Over-Limit Mahasiswa</label>
                <select id="kebijakan_overlimit" name="kebijakan_overlimit">
                    <option value="Izinkan dengan Pencatatan Jatah Minus & Kompensasi/Denda"<?= ($br['kebijakan_overlimit'] ?? '') === 'Izinkan dengan Pencatatan Jatah Minus & Kompensasi/Denda' ? ' selected' : '' ?>>Pencatatan Jatah Minus &amp; Kompensasi/Denda</option>
                    <option value="Batasi Ketat Sesuai Jatah Maksimum"<?= ($br['kebijakan_overlimit'] ?? '') === 'Batasi Ketat Sesuai Jatah Maksimum' ? ' selected' : '' ?>>Batasi Ketat Sesuai Jatah Maksimum</option>
                </select>
            </div>
            <div>
                <label for="denda_per_satuan">Tarif Kompensasi / Denda Over-Limit (Rp / satuan)</label>
                <input id="denda_per_satuan" name="denda_per_satuan" type="number" min="0" value="<?= e($br['denda_per_satuan'] ?? 5000) ?>">
            </div>
        </div>

        <button class="btn" type="submit">Simpan Business Rule &amp; Time Frame</button>
    </form>
    <?php
    require __DIR__ . '/partials/footer.php';
}

function fitur_dashboard() {
    $d = db();
    $all = $d['barang'];
    $role = norm_role($_SESSION['user']['role'] ?? 'teknisi');
    $q = strtolower(trim($_GET['q'] ?? ''));
    $rak = $_GET['rak'] ?? '';
    $st = $_GET['status'] ?? '';
    $raks = array_values(array_filter(array_unique(array_column($all, 'rak')), fn($r) => trim((string)$r) !== '')); sort($raks);
    $list = array_filter($all, function ($i) use ($q, $rak, $st) {
        if ($q !== '' && !str_contains(strtolower($i['nama'] . ' ' . $i['kode'] . ' ' . $i['spesifikasi']), $q)) return false;
        if ($rak !== '' && $i['rak'] !== $rak) return false;
        if ($st === 'low' && !low($i)) return false;
        if ($st === 'ok' && low($i)) return false;
        return true;
    });
    $lowItems = array_filter($all, 'low');
    $lowCount = count($lowItems);
    $amanCount = count($all) - $lowCount;

    $waitDosen = array_filter($d['pengajuan_dosen'], fn($r) => ($r['status'] ?? '') === 'menunggu');
    $waitMhs = array_filter($d['permintaan'], fn($r) => ($r['status'] ?? '') === 'menunggu');
    $waitOpname = array_filter($d['stockopname'], fn($r) => ($r['status'] ?? '') === 'menunggu_cek_plp');

    $title = $role === 'kajur' ? 'Dashboard Kajur (Monitoring Bahan)' : ($role === 'plp' ? 'Dashboard Pengelolaan Bahan' : 'Dashboard Bahan Habis Pakai');
    $active = 'dashboard';
    $resetUrl = role_url('dashboard');
    require __DIR__ . '/partials/header.php';
    ?>

    <?php if ($role === 'plp'): ?>
        <!-- KPI Cards gaya Wireframe v4 Page 17 (PLP / Teknisi) -->
        <div class="cards">
            <div>
                <span>Pengajuan masuk (Dosen &amp; Mhs)</span>
                <strong><?= str_pad((string)(count($waitDosen) + count($waitMhs)), 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Jenis bahan tersedia</span>
                <strong><?= str_pad((string)$amanCount, 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Stok di bawah minimum</span>
                <strong><?= str_pad((string)$lowCount, 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Menunggu verifikasi opname</span>
                <strong><?= str_pad((string)count($waitOpname), 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
        </div>

        <div class="grid2" style="margin-bottom:18px">
            <div class="box" style="margin-bottom:0">
                <h3>Pengajuan masuk dari Dosen &amp; Mahasiswa</h3>
                <?php
                $antreanPlp = [];
                foreach (array_reverse($waitDosen) as $wd) {
                    $antreanPlp[] = ['pengaju' => $wd['oleh'] . ' (Dosen)', 'isi' => $wd['nama'] . ' · ' . $wd['jumlah'] . ' ' . $wd['satuan'], 'url' => role_url('tinjau') . '&tab=dosen'];
                }
                foreach (array_reverse($waitMhs) as $wm) {
                    $antreanPlp[] = ['pengaju' => $wm['oleh'] . ' (' . ($wm['kelas'] ?? 'Mhs') . ')', 'isi' => $wm['nama'] . ' · ' . $wm['jumlah'] . ' ' . $wm['satuan'], 'url' => role_url('tinjau') . '&tab=mhs'];
                }
                $antreanPlp = array_slice($antreanPlp, 0, 4);
                ?>
                <?php if (!$antreanPlp): ?>
                    <p class="muted" style="margin:0;font-size:13px">Tidak ada pengajuan baru yang menunggu tinjauan PLP.</p>
                <?php else: ?>
                    <div class="tablewrap"><table>
                        <tr><th>Pengaju</th><th>Bahan &amp; Kebutuhan</th><th>Aksi</th></tr>
                        <?php foreach ($antreanPlp as $ap): ?>
                        <tr>
                            <td><?= e($ap['pengaju']) ?></td>
                            <td><?= e($ap['isi']) ?></td>
                            <td><a class="btn" href="<?= e($ap['url']) ?>" style="padding:6px 14px;font-size:12.5px">Proses</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </table></div>
                <?php endif; ?>
            </div>

            <div class="box" style="margin-bottom:0">
                <h3>Perlu perhatian (Stok Kritis &amp; Opname)</h3>
                <div class="tablewrap"><table>
                    <tr><th>Bahan / Rak</th><th>Keterangan</th><th>Status</th></tr>
                    <?php foreach (array_slice($lowItems, 0, 3) as $li): ?>
                    <tr>
                        <td><strong><?= e($li['nama']) ?></strong> (<?= $li['rak'] !== '' ? 'Rak ' . e($li['rak']) : 'Belum masuk rak' ?>)</td>
                        <td>Sisa <?= e($li['stok']) ?> / min <?= e($li['minimum'] . ' ' . $li['satuan']) ?></td>
                        <td><span class="chip bad">Di bawah minimum</span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach (array_slice(array_reverse($waitOpname), 0, 2) as $wo): ?>
                    <tr>
                        <td><strong><?= e($wo['nama']) ?></strong> (<?= !empty($wo['rak']) ? 'Rak ' . e($wo['rak']) : 'Belum masuk rak' ?>)</td>
                        <td>Fisik: <?= e($wo['stok_fisik'] . ' ' . $wo['satuan']) ?> (Oleh <?= e($wo['oleh']) ?>)</td>
                        <td><a href="<?= e(role_url('cek_barang')) ?>" class="chip warn" style="text-decoration:none">Menunggu cek PLP</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowItems && !$waitOpname): ?>
                    <tr><td colspan="3" class="muted">Semua stok bahan aman dan opname terverifikasi.</td></tr>
                    <?php endif; ?>
                </table></div>
            </div>
        </div>

    <?php elseif ($role === 'kajur'): ?>
        <!-- KPI Cards gaya Wireframe v4 Page 36 (Dashboard Kajur monitoring) -->
        <div class="cards cards-3">
            <div>
                <span>Pengadaan diajukan ke Kalab</span>
                <strong><?= str_pad((string)count($d['pengajuan']), 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Total jenis bahan praktikum</span>
                <strong><?= str_pad((string)count($all), 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Bahan di bawah minimum</span>
                <strong><?= str_pad((string)$lowCount, 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
        </div>

        <div class="box">
            <h3>Ringkasan Pengajuan Pengadaan &amp; Kebutuhan Bahan</h3>
            <?php if (!$d['pengajuan']): ?>
                <p class="muted" style="margin:0">Belum ada pengajuan pengadaan bahan.</p>
            <?php else: ?>
                <div class="tablewrap"><table>
                    <tr><th>No. Dokumen</th><th>Bahan &amp; Kebutuhan</th><th>Status Approval Kalab</th><th>Aksi</th></tr>
                    <?php foreach (array_slice(array_reverse($d['pengajuan']), 0, 4) as $pg): ?>
                    <tr>
                        <td><strong>#PG-<?= str_pad((string)$pg['id'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= e($pg['nama']) ?> — <?= e($pg['jumlah'] . ' ' . $pg['satuan']) ?> (Pesan: <?= e($pg['pesan'] . ' ' . $pg['satuan']) ?>)</td>
                        <td><?= chip($pg['status']) ?></td>
                        <td><a class="btn" href="<?= e(role_url('rekap') . '&tab=pengadaan') ?>" style="padding:6px 14px;font-size:12.5px">Monitoring</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table></div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- KPI Cards gaya Wireframe v4 Page 7 (Teknisi / Inventaris Bahan) -->
        <div class="cards">
            <div>
                <span>Total jenis bahan</span>
                <strong><?= str_pad((string)count($all), 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Stok aman</span>
                <strong><?= str_pad((string)$amanCount, 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Konfirmasi mhs menunggu</span>
                <strong><?= str_pad((string)count($waitMhs), 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span>Di bawah minimum</span>
                <strong><?= str_pad((string)$lowCount, 2, '0', STR_PAD_LEFT) ?></strong>
            </div>
        </div>

        <div class="grid3" style="margin-bottom:18px">
            <div class="box" style="margin-bottom:0">
                <h3>Bahan di bawah stok minimum</h3>
                <?php if (!$lowItems): ?>
                    <p class="muted" style="font-size:13px;margin:0">Semua bahan berada di atas stok minimum.</p>
                <?php else: ?>
                    <div class="tablewrap"><table>
                        <tr><th>Bahan</th><th>Stok</th><th>Status</th></tr>
                        <?php foreach (array_slice($lowItems, 0, 3) as $li): ?>
                        <tr>
                            <td><?= e($li['nama']) ?></td>
                            <td><?= e($li['stok'] . '/' . $li['minimum'] . ' ' . $li['satuan']) ?></td>
                            <td><span class="chip bad">Kurang</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table></div>
                <?php endif; ?>
                <div style="margin-top:12px"><a class="btn sec" href="<?= e(role_url('peringatan')) ?>" style="padding:6px 14px;font-size:12.5px">Lihat semua</a></div>
            </div>

            <div class="box" style="margin-bottom:0">
                <h3>Sebaran bahan per rak</h3>
                <div class="tablewrap"><table>
                    <tr><th>Rak</th><th>Terisi</th><th>Status</th></tr>
                    <?php foreach (array_keys($d['rak_kapasitas']) as $rkName): $rkInf = rak_info($d, $rkName); ?>
                    <tr>
                        <td><strong>Rak <?= e($rkName) ?></strong></td>
                        <td><?= e($rkInf['terpakai'] . ' / ' . $rkInf['kapasitas']) ?></td>
                        <td><span class="chip <?= e($rkInf['chip']) ?>"><?= e($rkInf['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table></div>
            </div>

            <div class="box" style="margin-bottom:0">
                <h3>Perubahan data terakhir</h3>
                <?php if (!$d['log']): ?>
                    <p class="muted" style="font-size:13px;margin:0">Belum ada log aktivitas pemasukan/pengeluaran.</p>
                <?php else: ?>
                    <div class="tablewrap"><table>
                        <tr><th>Kejadian</th><th>Oleh</th></tr>
                        <?php foreach (array_slice(array_reverse($d['log']), 0, 3) as $lg): ?>
                        <tr>
                            <td><?= e(ucfirst($lg['jenis']) . ' ' . $lg['kode'] . ' (' . $lg['jumlah'] . ')') ?></td>
                            <td><?= e($lg['oleh']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pencarian & Katalog Bahan gaya Wireframe v4 Page 8 (Cari Alat / Katalog Card Grid) -->
    <div class="box">
        <h3>Pencarian &amp; Filter Katalog Bahan Habis Pakai</h3>
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="fitur" value="dashboard">
            <div style="flex:2;min-width:220px">
                <label for="q">Cari bahan</label>
                <input id="q" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Ketik nama bahan, spesifikasi, atau kode inventaris..." style="width:100%;padding:9px 12px;border:1px solid #CBD5E1;border-radius:10px">
            </div>
            <div style="flex:1;min-width:150px">
                <label for="rak">Lokasi Rak</label>
                <select id="rak" name="rak" style="width:100%;padding:9px 12px;border:1px solid #CBD5E1;border-radius:10px;background:#fff">
                    <option value="">Semua rak</option>
                    <?php foreach ($raks as $r): ?><option value="<?= e($r) ?>"<?= $r === $rak ? ' selected' : '' ?>>Rak <?= e($r) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button class="btn" type="submit">Cari</button>
            <?php if ($q !== '' || $rak !== '' || $st !== ''): ?><a class="reset" href="<?= e($resetUrl) ?>">Reset</a><?php endif; ?>
        </form>
        <div class="pill-bar">
            <a class="pill-filter<?= $st === '' && $rak === '' ? ' on' : '' ?>" href="<?= e($resetUrl) ?>">Semua (<?= count($all) ?>)</a>
            <a class="pill-filter<?= $st === 'ok' ? ' on' : '' ?>" href="<?= e($resetUrl . '&status=ok') ?>">Tersedia / Aman (<?= $amanCount ?>)</a>
            <a class="pill-filter<?= $st === 'low' ? ' on' : '' ?>" href="<?= e($resetUrl . '&status=low') ?>">Di Bawah Minimum (<?= $lowCount ?>)</a>
            <?php foreach ($raks as $r): ?>
                <a class="pill-filter<?= $rak === $r ? ' on' : '' ?>" href="<?= e($resetUrl . '&rak=' . urlencode($r)) ?>">Rak <?= e($r) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$list): ?><p class="empty">Tidak ada bahan yang cocok. Ubah atau reset filter.</p><?php endif; ?>

    <div class="catalog-grid">
        <?php foreach ($list as $i):
            $isLow = low($i);
            if ($i['rak'] !== '') {
                $lok = rak_lokasi($d, $i['rak']);
                $lokStr = ($lok['ruangan'] !== 'Belum diatur' ? $lok['ruangan'] . ' › ' : '')
                        . ($lok['lemari'] !== 'Belum diatur' ? $lok['lemari'] . ' › ' : '')
                        . 'Rak ' . $i['rak'];
            } else {
                $lokStr = 'Belum masuk rak';
            }
        ?>
        <article class="catalog-card">
            <div>
                <h3><?= e($i['nama']) ?></h3>
                <div class="catalog-meta"><?= e($i['kode']) ?> &middot; <?= e($i['spesifikasi']) ?></div>
                <div class="catalog-loc" style="margin-top:10px"><?= e($lokStr) ?></div>
                <div style="margin-top:10px;font-size:13px;display:flex;justify-content:space-between">
                    <span>Stok: <strong><?= e($i['stok'] . ' ' . $i['satuan']) ?></strong></span>
                    <span class="muted">Min: <?= e($i['minimum'] . ' ' . $i['satuan']) ?></span>
                </div>
                <div class="bar" aria-hidden="true"><i style="width:<?= stok_pct($i) ?>%;background:<?= $isLow ? '#D92D20' : 'var(--theme)' ?>"></i></div>
            </div>
            <div class="catalog-foot">
                <span class="chip <?= $isLow ? 'bad' : 'ok' ?>"><?= $isLow ? 'Di bawah minimum' : 'Tersedia' ?></span>
                <a class="btn sec" href="<?= e(role_url('stok')) ?>" style="padding:6px 14px;font-size:12.5px">Detail Stok</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php
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
        <td><?= $i['rak'] !== '' ? e($i['rak']) : '<span class="muted">Belum masuk rak</span>' ?></td><td><?= e($i['satuan']) ?></td><td><?= e($i['minimum']) ?></td><td><?= e($i['stok']) ?></td>
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

// Lokasi rak: ruangan -> lemari -> rak. Rak yang belum diatur ditampilkan sebagai 'Belum diatur'.
function rak_lokasi($d, $rak) {
    $l = $d['rak_lokasi'][$rak] ?? [];
    $ru = trim((string)($l['ruangan'] ?? ''));
    $lm = trim((string)($l['lemari'] ?? ''));
    return ['ruangan' => $ru !== '' ? $ru : 'Belum diatur', 'lemari' => $lm !== '' ? $lm : 'Belum diatur'];
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
            $ru = mb_substr(trim($_POST['ruangan'] ?? ''), 0, 60);
            $lm = mb_substr(trim($_POST['lemari'] ?? ''), 0, 60);
            if ($ru !== '' || $lm !== '') $d['rak_lokasi'][$rk] = ['ruangan' => $ru, 'lemari' => $lm];
            save($d);
            go($redirect, "Kapasitas Rak $rk berhasil diatur menjadi $kap.");
        } elseif ($aksi === 'pindah_rak') {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
            $rakTujuan = strtoupper(trim($_POST['rak_tujuan'] ?? ''));
            if ($n === null || $rakTujuan === '') go($redirect, 'Pilih barang dan rak tujuan.', 'bad');
            if ($d['barang'][$n]['rak'] === $rakTujuan) {
                go($redirect, "Barang {$d['barang'][$n]['nama']} sudah berada di Rak $rakTujuan.", 'warn');
            }
            if (!isset($d['rak_kapasitas'][$rakTujuan])) $d['rak_kapasitas'][$rakTujuan] = 500;
            $infoTujuan = rak_info($d, $rakTujuan);
            $stokBarang = (int)$d['barang'][$n]['stok'];
            if (($infoTujuan['terpakai'] + $stokBarang) > $infoTujuan['kapasitas']) {
                go($redirect, "Kapasitas Rak $rakTujuan tidak mencukupi! Sisa kapasitas: {$infoTujuan['sisa']}, stok barang yang dipindah: $stokBarang.", 'bad');
            }
            $rakAsal = $d['barang'][$n]['rak'];
            $d['barang'][$n]['rak'] = $rakTujuan;
            save($d);
            $asalTxt = $rakAsal !== '' ? "dari Rak $rakAsal " : '';
            go($redirect, "Barang {$d['barang'][$n]['nama']} berhasil ditempatkan {$asalTxt}ke Rak $rakTujuan.");
        }
    }

    $byRak = [];
    foreach (array_keys($d['rak_kapasitas']) as $rName) {
        if ($rName !== '') $byRak[$rName] = [];
    }
    foreach ($d['barang'] as $i) {
        if ($i['rak'] !== '') {
            $byRak[$i['rak']][] = $i;
        }
    }
    ksort($byRak);

    // Filter bertingkat: ruangan -> lemari
    $lokasi = [];
    foreach (array_keys($byRak) as $rName) $lokasi[$rName] = rak_lokasi($d, $rName);
    $fRuangan = trim($_GET['ruangan'] ?? '');
    $fLemari = trim($_GET['lemari'] ?? '');
    $listRuangan = array_values(array_unique(array_column($lokasi, 'ruangan')));
    sort($listRuangan, SORT_NATURAL | SORT_FLAG_CASE);
    $listLemari = [];
    foreach ($lokasi as $l) {
        if ($fRuangan === '' || $l['ruangan'] === $fRuangan) $listLemari[] = $l['lemari'];
    }
    $listLemari = array_values(array_unique($listLemari));
    sort($listLemari, SORT_NATURAL | SORT_FLAG_CASE);
    if ($fLemari !== '' && !in_array($fLemari, $listLemari, true)) $fLemari = '';
    $tampil = array_filter($byRak, function ($rName) use ($lokasi, $fRuangan, $fLemari) {
        return ($fRuangan === '' || $lokasi[$rName]['ruangan'] === $fRuangan)
            && ($fLemari === '' || $lokasi[$rName]['lemari'] === $fLemari);
    }, ARRAY_FILTER_USE_KEY);
    $title = 'Rak & Status Kapasitas'; $active = 'rak';
    require __DIR__ . '/partials/header.php';

    if ($role === 'teknisi'): ?>
    <div class="grid2">
        <form class="box form" method="post">
            <?= field_csrf() ?>
            <input type="hidden" name="aksi_rak" value="kapasitas">
            <h3 style="margin-top:0;margin-bottom:6px;font-size:16px">Tambah / Ubah Kapasitas Rak</h3>
            <p class="muted" style="font-size:12px;margin:0 0 12px">Atur daya tampung maksimum rak serta lokasi ruangan &amp; lemari penyimpanannya.</p>
            <label for="nama_rak">Nama Rak</label>
            <input id="nama_rak" name="nama_rak" placeholder="Contoh: A-01" required>
            <label for="kapasitas">Kapasitas Maksimum Rak (Total Unit/Satuan)</label>
            <input id="kapasitas" name="kapasitas" type="number" min="1" value="500" required>
            <div class="grid2">
                <div>
                    <label for="ruangan">Ruangan</label>
                    <input id="ruangan" name="ruangan" list="dl_ruangan" maxlength="60" placeholder="Contoh: Lab Kerja Bangku">
                </div>
                <div>
                    <label for="lemari">Lemari</label>
                    <input id="lemari" name="lemari" list="dl_lemari" maxlength="60" placeholder="Contoh: Lemari 1">
                </div>
            </div>
            <datalist id="dl_ruangan"><?php foreach (array_unique(array_column($lokasi, 'ruangan')) as $o): if ($o !== 'Belum diatur'): ?><option value="<?= e($o) ?>"><?php endif; endforeach; ?></datalist>
            <datalist id="dl_lemari"><?php foreach (array_unique(array_column($lokasi, 'lemari')) as $o): if ($o !== 'Belum diatur'): ?><option value="<?= e($o) ?>"><?php endif; endforeach; ?></datalist>
            <p class="muted" style="font-size:12px;margin:0 0 12px">Ruangan &amp; lemari boleh dikosongkan jika hanya mengubah kapasitas (lokasi lama tetap).</p>
            <button class="btn" type="submit">Simpan Kapasitas Rak</button>
        </form>
        <form class="box form" method="post">
            <?= field_csrf() ?>
            <input type="hidden" name="aksi_rak" value="pindah_rak">
            <h3 style="margin-top:0;margin-bottom:6px;font-size:16px">Pindah / Relokasi Rak Barang</h3>
            <p class="muted" style="font-size:12px;margin:0 0 12px">Menata ulang posisi penyimpanan barang yang sudah ada ke rak lain (jumlah stok tetap sama).</p>
            <label for="kode">Pilih Barang yang Dipindahkan</label>
            <select id="kode" name="kode" required>
                <option value="">-- Pilih barang --</option>
                <?php foreach ($d['barang'] as $b):
                    $lblRakSaatIni = $b['rak'] !== '' ? 'Saat ini di Rak ' . $b['rak'] : 'Belum masuk rak';
                ?>
                <option value="<?= e($b['kode']) ?>"><?= e($b['nama'] . ' (' . $b['kode'] . ') - Stok: ' . $b['stok'] . ' ' . $b['satuan'] . ' [' . $lblRakSaatIni . ']') ?></option>
                <?php endforeach; ?>
            </select>
            <label for="rak_tujuan">Pilih Rak Tujuan</label>
            <select id="rak_tujuan" name="rak_tujuan" required>
                <option value="">-- Pilih rak tujuan --</option>
                <?php foreach (array_keys($byRak) as $rkName): $inf = rak_info($d, $rkName); ?>
                <option value="<?= e($rkName) ?>">Rak <?= e($rkName) ?> (Terisi: <?= e($inf['terpakai'] . '/' . $inf['kapasitas']) ?>, Sisa: <?= e($inf['sisa']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">Pindahkan Barang ke Rak Tujuan</button>
        </form>
    </div>
    <?php endif; ?>

    <form class="filter" method="get" action="">
        <input type="hidden" name="fitur" value="rak">
        <div>
            <label for="f_ruangan">Ruangan</label>
            <select id="f_ruangan" name="ruangan" onchange="this.form.lemari.value='';this.form.submit()">
                <option value="">Semua ruangan</option>
                <?php foreach ($listRuangan as $o): ?>
                <option value="<?= e($o) ?>"<?= $o === $fRuangan ? ' selected' : '' ?>><?= e($o) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="f_lemari">Lemari</label>
            <select id="f_lemari" name="lemari" onchange="this.form.submit()">
                <option value="">Semua lemari</option>
                <?php foreach ($listLemari as $o): ?>
                <option value="<?= e($o) ?>"<?= $o === $fLemari ? ' selected' : '' ?>><?= e($o) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn" type="submit">Terapkan</button>
        <?php if ($fRuangan !== '' || $fLemari !== ''): ?><a class="reset" href="?fitur=rak">Reset filter</a><?php endif; ?>
        <span class="muted" style="font-size:13px;margin-left:auto">Menampilkan <?= count($tampil) ?> dari <?= count($byRak) ?> rak</span>
    </form>
    <?php if (!$tampil): ?><p class="empty">Tidak ada rak pada ruangan/lemari yang dipilih.</p><?php endif; ?>

    <?php foreach ($tampil as $rak => $rows):
        $rkInfo = rak_info($d, $rak);
        $lok = $lokasi[$rak];
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
        <p class="stats">Ruangan: <strong><?= e($lok['ruangan']) ?></strong> &middot; Lemari: <strong><?= e($lok['lemari']) ?></strong></p>
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
    <tr><td><?= e($i['kode']) ?></td><td><?= e($i['nama']) ?></td><td><?= $i['rak'] !== '' ? e($i['rak']) : '<span class="muted">Belum masuk rak</span>' ?></td><td><?= e($i['stok'] . ' ' . $i['satuan']) ?></td><td><?= e($i['minimum']) ?></td><td><span class="chip bad"><?= e($i['minimum'] - $i['stok']) ?> <?= e($i['satuan']) ?></span></td></tr>
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

        // Jika barang sudah punya rak, otomatis masuk ke rak tersebut; jika belum masuk rak, gunakan rak pilihan Teknisi
        $targetRak = trim((string)($d['barang'][$n]['rak'] ?? ''));
        if ($targetRak === '') {
            $targetRak = strtoupper(trim((string)($_POST['rak_tujuan'] ?? '')));
        }
        if ($targetRak === '') {
            go($redirect, 'Barang ini belum memiliki rak penyimpanan. Silakan pilih Rak Tujuan Penyimpanan terlebih dahulu.', 'bad');
        }
        if (!isset($d['rak_kapasitas'][$targetRak])) $d['rak_kapasitas'][$targetRak] = 500;
        $rkInfo = rak_info($d, $targetRak);

        // Validasi kapasitas rak penyimpanan barang
        if (($rkInfo['terpakai'] + $jml) > $rkInfo['kapasitas']) {
            go($redirect, "Gagal menambahkan stok ke Rak $targetRak! Kapasitas maksimum rak ({$rkInfo['kapasitas']}) terlampaui. Terisi saat ini: {$rkInfo['terpakai']}, sisa kapasitas: {$rkInfo['sisa']}, jumlah masuk: $jml.", 'bad');
        }

        $d['barang'][$n]['rak'] = $targetRak;
        $d['barang'][$n]['stok'] += $jml;
        $kodeMasuk = $d['barang'][$n]['kode'];

        // Tandai otomatis permintaan pembelian (atau pengajuan yang sudah dipesan) menjadi selesai diterima
        $pembelianId = post_int('pembelian_id');
        foreach ($d['pembelian'] as &$pb) {
            if (($pb['status'] ?? '') === 'diajukan' && (($pembelianId > 0 && (int)$pb['id'] === $pembelianId) || $pb['kode'] === $kodeMasuk)) {
                $pb['status'] = 'selesai';
                $idxPg = find_idx($d['pengajuan'], 'id', $pb['pengajuan_id'] ?? 0);
                if ($idxPg !== null) {
                    $d['pengajuan'][$idxPg]['status'] = 'selesai';
                }
            }
        }
        unset($pb);

        $totalRakBaru = rak_terpakai($d['barang'], $targetRak);
        $catMasuk = trim($_POST['catatan'] ?? '');
        if ($catMasuk === '') $catMasuk = 'Penerimaan pengadaan / barang masuk';
        add_log($d, 'masuk', $kodeMasuk, $jml, "Rak $targetRak (Total isi rak: $totalRakBaru/{$rkInfo['kapasitas']}) - " . $catMasuk);
        save($d);
        go($redirect, "Stok {$d['barang'][$n]['nama']} bertambah $jml {$d['barang'][$n]['satuan']} dan disimpan di Rak $targetRak (Kapasitas terpakai: $totalRakBaru/{$rkInfo['kapasitas']}).");
    }

    $title = 'Barang Masuk'; $active = 'masuk'; $d = db();
    $riwayat = array_slice(array_reverse(array_filter($d['log'], fn($l) => $l['jenis'] === 'masuk')), 0, 10);

    // Hitung total pesanan yang sedang diajukan per kode barang (dari Permintaan Pembelian PLP / Approval Kalab)
    $dipesanMap = [];
    $pembelianMenunggu = [];
    foreach ($d['pembelian'] as $pb) {
        if (($pb['status'] ?? '') === 'diajukan') {
            $kd = $pb['kode'];
            $dipesanMap[$kd] = ($dipesanMap[$kd] ?? 0) + (int)$pb['jumlah'];
            $pembelianMenunggu[] = $pb;
        }
    }
    foreach ($d['pengajuan'] as $pg) {
        if (in_array($pg['status'] ?? '', ['disetujui', 'dipesan'], true) && empty($dipesanMap[$pg['kode']]) && ($pg['pesan'] ?? 0) > 0) {
            $dipesanMap[$pg['kode']] = (int)$pg['pesan'];
        }
    }

    require __DIR__ . '/partials/header.php';
    ?>

    <?php if ($pembelianMenunggu): ?>
    <div class="box">
        <h3>Pesanan Pembelian Siap Diterima ke Gudang / Rak</h3>
        <p class="muted" style="font-size:13px;margin:0 0 12px">Daftar bahan di bawah ini sudah disetujui Kepala Lab dan dibuatkan Permintaan Pembelian oleh PLP. Klik <strong>Pilih &amp; Isi Otomatis</strong> saat barang fisik tiba di laboratorium.</p>
        <div class="tablewrap">
            <table>
                <tr>
                    <th>No. Pembelian</th>
                    <th>Tanggal</th>
                    <th>Nama Bahan</th>
                    <th>Rak Saat Ini</th>
                    <th>Jumlah Dipesan</th>
                    <th>Status</th>
                    <th>Aksi Teknisi</th>
                </tr>
                <?php foreach ($pembelianMenunggu as $pb):
                    $idxB = find_idx($d['barang'], 'kode', $pb['kode']);
                    $rakSkrg = ($idxB !== null && $d['barang'][$idxB]['rak'] !== '') ? 'Rak ' . $d['barang'][$idxB]['rak'] : 'Belum masuk rak';
                ?>
                <tr>
                    <td><strong>#PB-<?= e($pb['id']) ?></strong> (Pengajuan #<?= e($pb['pengajuan_id']) ?>)</td>
                    <td><?= e($pb['tanggal']) ?></td>
                    <td><strong><?= e($pb['nama']) ?></strong> (<?= e($pb['kode']) ?>)</td>
                    <td><?= $rakSkrg === 'Belum masuk rak' ? '<span class="chip warn">Belum masuk rak</span>' : e($rakSkrg) ?></td>
                    <td><strong><?= e($pb['jumlah'] . ' ' . $pb['satuan']) ?></strong></td>
                    <td><?= chip($pb['status']) ?></td>
                    <td>
                        <button type="button" class="btn" style="padding:6px 14px;font-size:12.5px" onclick="isiDariPembelian('<?= e($pb['kode']) ?>', <?= (int)$pb['jumlah'] ?>, <?= (int)$pb['id'] ?>)">Pilih &amp; Isi Otomatis</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <form class="box form" method="post" id="form_barang_masuk">
        <?= field_csrf() ?>
        <input type="hidden" id="pembelian_id" name="pembelian_id" value="0">
        <label for="kode">Pilih Barang Masuk</label>
        <select id="kode" name="kode" required>
            <option value="">-- Pilih barang --</option>
            <?php foreach ($d['barang'] as $b):
                $hasRak = ($b['rak'] !== '');
                $rk = $hasRak ? rak_info($d, $b['rak']) : ['sisa' => '-', 'kapasitas' => '-', 'terpakai' => '-'];
                $lok = $hasRak ? rak_lokasi($d, $b['rak']) : ['ruangan' => '-', 'lemari' => '-'];
                $jmlPesan = $dipesanMap[$b['kode']] ?? 0;
                $pesanInfo = $jmlPesan > 0 ? ' | Dipesan: ' . $jmlPesan . ' ' . $b['satuan'] : '';
                $labelOpt = $hasRak
                    ? ($b['nama'] . ' (' . $b['kode'] . ') — Rak ' . $b['rak'] . ' [Stok: ' . $b['stok'] . ' ' . $b['satuan'] . $pesanInfo . ', Sisa rak: ' . $rk['sisa'] . ']')
                    : ($b['nama'] . ' (' . $b['kode'] . ') — Belum masuk rak [Stok gudang: ' . $b['stok'] . ' ' . $b['satuan'] . $pesanInfo . ']');
            ?>
                <option value="<?= e($b['kode']) ?>" 
                        data-rak="<?= e($b['rak']) ?>" 
                        data-stok="<?= e($b['stok']) ?>" 
                        data-satuan="<?= e($b['satuan']) ?>"
                        data-dipesan="<?= e($jmlPesan) ?>"
                        data-sisa="<?= e($rk['sisa']) ?>"
                        data-kap="<?= e($rk['kapasitas']) ?>"
                        data-terpakai="<?= e($rk['terpakai']) ?>"
                        data-ruangan="<?= e($lok['ruangan']) ?>"
                        data-lemari="<?= e($lok['lemari']) ?>">
                    <?= e($labelOpt) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="pilih_rak_baru_box" style="display:none;margin:8px 0 16px;padding:14px 16px;background:var(--warn-bg);border:1px solid #FEC84B;border-radius:10px">
            <label for="rak_tujuan" style="color:var(--warn);font-weight:700">Barang ini belum dimasukkan ke dalam rak. Pilih Rak Penyimpanan:</label>
            <select id="rak_tujuan" name="rak_tujuan" style="margin-bottom:4px">
                <option value="">-- Pilih Rak Tujuan --</option>
                <?php foreach (array_keys($d['rak_kapasitas']) as $rkName): if ($rkName === '') continue; $inf = rak_info($d, $rkName); ?>
                <option value="<?= e($rkName) ?>">Rak <?= e($rkName) ?> (Terisi: <?= e($inf['terpakai'] . '/' . $inf['kapasitas']) ?>, Sisa kapasitas: <?= e($inf['sisa']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <small class="muted" style="display:block;font-size:12px">Setelah barang masuk dicatat, barang ini akan terdaftar di rak yang Anda pilih.</small>
        </div>

        <div id="info_rak_box" style="display:none;margin:8px 0 16px;padding:12px 14px;background:#F5F7FA;border:1px solid var(--line);border-left:4px solid var(--theme);border-radius:6px">
            <div style="font-size:13px;line-height:1.6">
                <div>Lokasi Penyimpanan: <strong>Rak <span id="lbl_rak">-</span></strong> (<span id="lbl_lokasi">-</span>)</div>
                <div>Status Kapasitas Rak: Terisi <strong id="lbl_terpakai">0</strong> / <span id="lbl_kap">0</span> &middot; Sisa Ruang Rak: <strong id="lbl_sisa" style="color:#27ae60">0</strong> unit</div>
                <div class="muted" style="font-size:12px;margin-top:4px">*) Barang masuk otomatis disimpan ke rak di atas. Untuk memindahkan posisi barang ke rak lain, gunakan menu <strong>Rak &amp; Kapasitas</strong>.</div>
            </div>
        </div>

        <div class="grid2">
            <div>
                <label for="jumlah">Jumlah Masuk (dijumlahkan ke dalam rak)</label>
                <input id="jumlah" name="jumlah" type="number" min="1" placeholder="Masukkan jumlah barang" required>
            </div>
            <div>
                <label for="catatan">Catatan / Asal Pengadaan</label>
                <input id="catatan" name="catatan" placeholder="Contoh: Pengadaan Laboratorium 2026">
            </div>
        </div>
        <button class="btn" type="submit">Catat Barang Masuk</button>
    </form>
    <h2>Riwayat terbaru</h2>
    <?php include __DIR__ . '/partials/log.php'; ?>
    <script>
    const selBarang = document.getElementById('kode');
    const infoBox = document.getElementById('info_rak_box');
    const pilihRakBox = document.getElementById('pilih_rak_baru_box');
    const selRakTujuan = document.getElementById('rak_tujuan');
    const inJumlah = document.getElementById('jumlah');
    const inCatatan = document.getElementById('catatan');
    const inPembelianId = document.getElementById('pembelian_id');
    const lblRak = document.getElementById('lbl_rak');
    const lblLokasi = document.getElementById('lbl_lokasi');
    const lblTerpakai = document.getElementById('lbl_terpakai');
    const lblKap = document.getElementById('lbl_kap');
    const lblSisa = document.getElementById('lbl_sisa');

    function syncBarangMasukUI() {
        const opt = selBarang.selectedOptions[0];
        if (!opt || !opt.value) {
            infoBox.style.display = 'none';
            pilihRakBox.style.display = 'none';
            selRakTujuan.required = false;
            inJumlah.value = '';
            inCatatan.value = '';
            if (inPembelianId) inPembelianId.value = '0';
            return;
        }
        const dipesan = +(opt.dataset.dipesan || 0);
        if (dipesan > 0) {
            inJumlah.value = dipesan;
            if (!inCatatan.value) {
                inCatatan.value = 'Penerimaan dari Permintaan Pembelian PLP (' + dipesan + ' ' + (opt.dataset.satuan || '') + ')';
            }
        }
        if (opt.dataset.rak) {
            lblRak.textContent = opt.dataset.rak;
            lblLokasi.textContent = 'Ruangan: ' + opt.dataset.ruangan + ', Lemari: ' + opt.dataset.lemari;
            lblTerpakai.textContent = opt.dataset.terpakai;
            lblKap.textContent = opt.dataset.kap;
            lblSisa.textContent = opt.dataset.sisa;
            infoBox.style.display = 'block';
            pilihRakBox.style.display = 'none';
            selRakTujuan.required = false;
        } else {
            infoBox.style.display = 'none';
            pilihRakBox.style.display = 'block';
            selRakTujuan.required = true;
        }
    }

    function isiDariPembelian(kode, jumlah, pbId) {
        selBarang.value = kode;
        inPembelianId.value = pbId;
        syncBarangMasukUI();
        inJumlah.value = jumlah;
        inCatatan.value = 'Penerimaan Permintaan Pembelian #PB-' + pbId;
        document.getElementById('form_barang_masuk').scrollIntoView({ behavior: 'smooth' });
    }

    if (selBarang) {
        selBarang.addEventListener('change', () => {
            inPembelianId.value = 0;
            inCatatan.value = '';
            syncBarangMasukUI();
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
            <?php foreach ($d['barang'] as $b):
                $rkTxt = $b['rak'] !== '' ? 'Rak ' . $b['rak'] : 'Belum masuk rak';
            ?>
            <option value="<?= e($b['kode']) ?>" data-stok="<?= e($b['stok']) ?>" data-satuan="<?= e($b['satuan']) ?>">
                <?= e($b['nama'] . ' (' . $b['kode'] . ') - ' . $rkTxt . ' | Stok Sistem: ' . $b['stok'] . ' ' . $b['satuan']) ?>
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
        <?php foreach ($d['barang'] as $b): $hasRak = ($b['rak'] !== ''); $rk = $hasRak ? rak_info($d, $b['rak']) : null; ?>
        <tr>
            <td><?= e($b['kode']) ?></td>
            <td><?= e($b['nama']) ?> <br><small class="muted"><?= e($b['spesifikasi']) ?></small></td>
            <td><?php if ($hasRak): ?>Rak <?= e($b['rak']) ?> <span class="chip <?= e($rk['chip']) ?>"><?= e($rk['terpakai'] . '/' . $rk['kapasitas'] . ' (' . $rk['status'] . ')') ?></span><?php else: ?><span class="muted">Belum masuk rak</span><?php endif; ?></td>
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

// PLP Point 1, 2 & Kalab Point 1, 3 (Revisi Notulensi Bagian 1 & 5):
// - Standarisasi istilah: "Pengajuan" = dokumen rencana pengadaan/pembelian bahan habis pakai dari lab ke pusat Polban untuk 2 semester.
// - Rumus: Jumlah Pengajuan = ceil( (Total Permintaan - Sisa Stok) + Stok Minimum + Toleransi Gagal Potong )
// - Acuan: Silabus/jobsheet laboratorium & rekap masukan kebutuhan dari para dosen.
function fitur_pengajuan() {
    $redirect = role_url('pengajuan');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $pdId = post_int('pengajuan_dosen_id');
        $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        $mk = trim($_POST['mata_kuliah'] ?? '');
        $labTujuan = trim($_POST['lab_tujuan'] ?? 'Lab Permesinan');
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
                if (!empty($pd['lab'])) $labTujuan = $pd['lab'];
                $d['pengajuan_dosen'][$idxPd]['diajukan_ke_kalab'] = true;
            }
        }

        if ($n === null || $mk === '' || $mhs < 1 || $per < 1 || $tol < 0 || $tol > 100 || $minBaru === false || $minBaru < 0) {
            go($redirect, 'Lengkapi data pengajuan pengadaan 2 semester, ukuran per mahasiswa, ukuran per pcs, stok minimum, dan faktor gagal potong (0-100%).', 'bad');
        }

        // Update stok minimum, ukuran per pcs & lab pada barang yang diajukan oleh PLP
        $d['barang'][$n]['minimum'] = (int)$minBaru;
        $d['barang'][$n]['ukuran_pcs'] = $ukuranPcs;
        $d['barang'][$n]['lab'] = $labTujuan;
        $b = $d['barang'][$n];

        [$kebutuhanKotor, $pesan, $totalPermintaan, $gagalPotong] = kebutuhan($mhs, $per, $tol, $b['stok'], (int)$minBaru);
        $jmlPcs = (int)ceil($totalPermintaan / $ukuranPcs);
        $pesanPcs = (int)ceil($pesan / $ukuranPcs);

        $d['pengajuan'][] = [
            'id' => count($d['pengajuan']) + 1,
            'tanggal' => date('Y-m-d'),
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'pengajuan_dosen_id' => $pdId,
            'lab' => $labTujuan,
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'ukuran_pcs' => $ukuranPcs,
            'ukuran_per_mhs' => $per,
            'jml_mhs' => $mhs,
            'jml_pcs' => $pesanPcs,
            'minimum_baru' => (int)$minBaru,
            'jumlah' => $totalPermintaan,
            'stok' => $b['stok'],
            'pesan' => $pesan,
            'ket' => "[$labTujuan] $mk: Total Permintaan ($mhs × $per = $totalPermintaan {$b['satuan']}) - Sisa Stok ({$b['stok']} {$b['satuan']}) + Stok Min ($minBaru {$b['satuan']}) + Gagal Potong $tol% ($gagalPotong {$b['satuan']}) = Pengajuan $pesan {$b['satuan']} (dibulatkan ke atas ≈ $pesanPcs pcs @{$ukuranPcs} {$b['satuan']})",
            'status' => 'menunggu',
            'disetujui_oleh' => '',
            'catatan' => '',
        ];
        save($d);
        go($redirect, "Draf Pengajuan Pengadaan 2 Semester berhasil dikirim PLP ke Kalab. Rumus: ($totalPermintaan - {$b['stok']}) + $minBaru + $gagalPotong = $pesan {$b['satuan']} ($pesanPcs pcs).");
    }

    $title = 'Pengajuan Pengadaan Bahan 2 Semester (PLP ke Kalab)'; $active = 'pengajuan'; $d = db();
    $disetujuiPlp = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui');
    require __DIR__ . '/partials/header.php';
    render_wf_steps('Alur persetujuan pengajuan pengadaan 2 semester', [
        'Silabus/Jobsheet & Usulan Dosen',
        'PLP Hitung Rumus Pengadaan',
        'Disetujui Kalab',
        'Diajukan ke Pusat & Distribusi ke Lab',
    ], 2, 'Rumus Notulensi: Jumlah Pengajuan = ceil( (Total Permintaan - Sisa Stok) + Stok Minimum + Toleransi Gagal Potong ).');
    ?>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <label for="pengajuan_dosen_id">Acuan Kebutuhan: Ambil dari Usulan Kebutuhan / Permintaan Dosen &amp; Silabus/Jobsheet Lab</label>
        <select id="pengajuan_dosen_id" name="pengajuan_dosen_id">
            <option value="">-- Pilih Usulan / Permintaan Dosen yang Disetujui PLP --</option>
            <?php foreach ($disetujuiPlp as $pd):
                $idxB = find_idx($d['barang'], 'kode', $pd['kode']);
                $minSkrg = $idxB !== null ? $d['barang'][$idxB]['minimum'] : 100;
                $ukPcsSkrg = $pd['ukuran_pcs'] ?? ($idxB !== null ? ($d['barang'][$idxB]['ukuran_pcs'] ?? 100) : 100);
            ?>
            <option value="<?= e($pd['id']) ?>"
                data-kode="<?= e($pd['kode']) ?>"
                data-mk="<?= e($pd['sub_praktikum'] . ' - ' . $pd['kelas']) ?>"
                data-lab="<?= e($pd['lab'] ?? 'Lab Permesinan') ?>"
                data-mhs="<?= e($pd['jml_mhs'] ?? 1) ?>"
                data-per="<?= e($pd['ukuran_per_mhs'] ?? $pd['jumlah']) ?>"
                data-pcs="<?= e($ukPcsSkrg) ?>"
                data-min="<?= e($minSkrg) ?>">
                #<?= e($pd['id']) ?> [<?= e($pd['lab'] ?? 'Lab Permesinan') ?>] <?= e($pd['sub_praktikum']) ?> (<?= e($pd['kelas']) ?>) — <?= e($pd['nama']) ?>: <?= e(($pd['jml_mhs'] ?? 1) . ' × ' . ($pd['ukuran_per_mhs'] ?? $pd['jumlah']) . ' ' . $pd['satuan']) ?> <?= !empty($pd['diajukan_ke_kalab']) ? '[Sudah Masuk Draf Pengajuan]' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>

        <div class="grid3">
            <div>
                <label for="mata_kuliah">Silabus / Jobsheet &amp; Kelas</label>
                <input id="mata_kuliah" name="mata_kuliah" placeholder="Contoh: Uji Tarik / Bubut Bertingkat - 2B" required>
            </div>
            <div>
                <label for="lab_tujuan">Laboratorium Penyimpanan Tujuan</label>
                <select id="lab_tujuan" name="lab_tujuan">
                    <?php foreach (LAB_LIST as $lb): ?>
                    <option value="<?= e($lb) ?>"><?= e($lb) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="kode">Bahan Habis Pakai</label>
                <select id="kode" name="kode" required>
                    <option value="">Pilih bahan</option>
                    <?php foreach ($d['barang'] as $b): ?>
                    <option value="<?= e($b['kode']) ?>" data-stok="<?= e($b['stok']) ?>" data-satuan="<?= e($b['satuan']) ?>" data-pcs="<?= e($b['ukuran_pcs'] ?? 1) ?>" data-min="<?= e($b['minimum']) ?>">
                        <?= e($b['nama'] . ' (' . $b['kode'] . ') - Sisa Stok: ' . $b['stok'] . ' ' . $b['satuan'] . ', Stok Min: ' . $b['minimum'] . ' ' . $b['satuan']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid3">
            <div><label for="mahasiswa">Jumlah Mahasiswa / Sampel</label><input id="mahasiswa" name="mahasiswa" type="number" min="1" required></div>
            <div><label for="per_mhs">Kebutuhan per Mahasiswa / Jobsheet</label><input id="per_mhs" name="per_mhs" type="number" min="1" required></div>
            <div><label for="ukuran_pcs">Ukuran per 1 Satuan Beli (Pcs/Batang)</label><input id="ukuran_pcs" name="ukuran_pcs" type="number" min="1" value="100" required></div>
        </div>
        <div class="grid2">
            <div><label for="minimum_baru">Batas Stok Minimum Lab (Dijaga Tetap Tersisa)</label><input id="minimum_baru" name="minimum_baru" type="number" min="0" required></div>
            <div><label for="tol">Faktor Barang Gagal Potong (%)</label><input id="tol" name="tol" type="number" min="0" max="100" value="0"></div>
        </div>
        <p id="hasil" class="calc" aria-live="polite"></p>
        <button class="btn" type="submit">Susun &amp; Kirim Pengajuan Pengadaan ke Kalab</button>
    </form>

    <h2>Daftar Dokumen Pengajuan Pengadaan 2 Semester (PLP &rarr; Kalab &rarr; Pusat Polban)</h2>
    <?php req_table(array_reverse($d['pengajuan'])); ?>
    <script>
    const f = id => document.getElementById(id);
    function hitung() {
        const o = f('kode').selectedOptions[0], m = +f('mahasiswa').value, p = +f('per_mhs').value, t = +f('tol').value || 0, min = +f('minimum_baru').value || 0, uPcs = Math.max(1, +f('ukuran_pcs').value || 1);
        if (!o || !o.value || m < 1 || p < 1) {
            f('hasil').textContent = 'Pilih usulan dosen atau isi bahan, jumlah mahasiswa, dan ukuran per mahasiswa untuk melihat kalkulasi rumus pengadaan.';
            return;
        }
        const sat = o.dataset.satuan, stok = +o.dataset.stok;
        const totalPermintaan = m * p;
        const gagalPotong = Math.ceil(Math.round(totalPermintaan * (t / 100) * 1e6) / 1e6);
        const pengajuan = Math.max(0, Math.ceil(Math.round(((totalPermintaan - stok) + min + (totalPermintaan * (t / 100))) * 1e6) / 1e6));
        const pengajuanPcs = Math.ceil(pengajuan / uPcs);
        f('hasil').textContent = `Rumus Pengadaan: (Total Permintaan ${totalPermintaan} ${sat} − Sisa Stok ${stok} ${sat}) + Stok Minimum ${min} ${sat} + Gagal Potong ${t}% (${gagalPotong} ${sat}) = Jumlah Pengajuan: ${pengajuan} ${sat} (Dibulatkan ke atas ≈ ${pengajuanPcs} pcs @ ${uPcs} ${sat}). Setelah ${totalPermintaan} ${sat} terpakai, sisa stok di lab tetap terjaga minimal ${min} ${sat}.`;
    }
    const selPd = f('pengajuan_dosen_id');
    if (selPd) {
        selPd.addEventListener('change', () => {
            const opt = selPd.selectedOptions[0];
            if (opt && opt.value) {
                f('kode').value = opt.dataset.kode || '';
                f('mata_kuliah').value = opt.dataset.mk || '';
                if (f('lab_tujuan') && opt.dataset.lab) f('lab_tujuan').value = opt.dataset.lab;
                f('mahasiswa').value = opt.dataset.mhs || '';
                f('per_mhs').value = opt.dataset.per || '';
                f('ukuran_pcs').value = opt.dataset.pcs || 100;
                f('minimum_baru').value = opt.dataset.min || 0;
                hitung();
            } else {
                f('kode').value = '';
                f('mata_kuliah').value = '';
                if (f('lab_tujuan')) f('lab_tujuan').selectedIndex = 0;
                f('mahasiswa').value = '';
                f('per_mhs').value = '';
                f('ukuran_pcs').value = '100';
                f('minimum_baru').value = '';
                f('tol').value = '0';
                hitung();
            }
        });
    }
    f('kode').addEventListener('change', () => {
        const o = f('kode').selectedOptions[0];
        if (o && o.value) {
            f('ukuran_pcs').value = o.dataset.pcs || 1;
            f('minimum_baru').value = o.dataset.min || 0;
        } else {
            f('ukuran_pcs').value = '100';
            f('minimum_baru').value = '';
        }
        hitung();
    });
    ['mahasiswa', 'per_mhs', 'ukuran_pcs', 'minimum_baru', 'tol'].forEach(i => f(i).addEventListener('input', hitung));
    hitung();
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Kalab Point 1 & 3: Dosen dengan jabatan Kepala Lab melakukan approval atas pengajuan pengadaan 2 semester dari PLP
function fitur_approval_kalab() {
    $redirect = role_url('approval_kalab');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $aksi = $_POST['aksi'] ?? '';
        if ($aksi === 'tarik_plp') {
            $idxPd = find_idx($d['pengajuan_dosen'], 'id', post_int('pd_id'));
            if ($idxPd === null || $d['pengajuan_dosen'][$idxPd]['status'] !== 'disetujui') {
                go($redirect, 'Data usulan/permintaan yang disetujui PLP tidak ditemukan.', 'bad');
            }
            $pd = $d['pengajuan_dosen'][$idxPd];
            $idxB = find_idx($d['barang'], 'kode', $pd['kode']);
            $stokSkrg = $idxB !== null ? (int)$d['barang'][$idxB]['stok'] : 0;
            $minSkrg = $idxB !== null ? (int)$d['barang'][$idxB]['minimum'] : 0;
            $ukPcs = (int)($pd['ukuran_pcs'] ?? ($idxB !== null ? $d['barang'][$idxB]['ukuran_pcs'] : 100));
            $totalPermintaan = (int)$pd['jumlah'];
            // Rumus Notulensi: (Total Permintaan - Sisa Stok) + Stok Minimum
            $pesan = max(0, ($totalPermintaan - $stokSkrg) + $minSkrg);
            $jmlPcs = (int)ceil($pesan / max(1, $ukPcs));

            $d['pengajuan_dosen'][$idxPd]['diajukan_ke_kalab'] = true;
            $d['pengajuan'][] = [
                'id' => count($d['pengajuan']) + 1,
                'tanggal' => date('Y-m-d'),
                'oleh' => 'PLP (Disetujui PLP)',
                'oleh_email' => 'plp@polban.ac.id',
                'pengajuan_dosen_id' => $pd['id'],
                'lab' => $pd['lab'] ?? 'Lab Permesinan',
                'kode' => $pd['kode'],
                'nama' => $pd['nama'],
                'satuan' => $pd['satuan'],
                'ukuran_pcs' => $ukPcs,
                'ukuran_per_mhs' => $pd['ukuran_per_mhs'] ?? $totalPermintaan,
                'jml_mhs' => $pd['jml_mhs'] ?? 1,
                'jml_pcs' => $jmlPcs,
                'minimum_baru' => $minSkrg,
                'jumlah' => $totalPermintaan,
                'stok' => $stokSkrg,
                'pesan' => $pesan,
                'ket' => "Diambil dari Usulan/Permintaan #{$pd['id']} ({$pd['sub_praktikum']} - {$pd['kelas']}): ($totalPermintaan - $stokSkrg) + Stok Min $minSkrg = Pengajuan $pesan {$pd['satuan']} ($jmlPcs pcs)",
                'status' => 'disetujui',
                'disetujui_oleh' => $_SESSION['user']['nama'] . ' (Dosen · Jabatan: Kalab)',
                'catatan' => 'Disetujui langsung oleh Kepala Lab untuk diajukan ke pusat Polban',
            ];
            save($d);
            go($redirect, "Usulan #{$pd['id']} ({$pd['nama']}) berhasil ditarik dan disetujui Kalab dengan rumus pengadaan: ($totalPermintaan - $stokSkrg) + $minSkrg = $pesan {$pd['satuan']}.");
        }

        $n = find_idx($d['pengajuan'], 'id', post_int('id'));
        if ($n === null || $d['pengajuan'][$n]['status'] !== 'menunggu' || !in_array($aksi, ['setujui', 'tolak'], true)) {
            go($redirect, 'Data pengajuan tidak ditemukan atau sudah ditinjau.', 'bad');
        }
        $d['pengajuan'][$n]['status'] = $aksi === 'setujui' ? 'disetujui' : 'ditolak';
        $d['pengajuan'][$n]['catatan'] = trim($_POST['catatan'] ?? '');
        $d['pengajuan'][$n]['disetujui_oleh'] = $_SESSION['user']['nama'] . ' (Dosen · Jabatan: Kalab)';
        save($d);
        go($redirect, 'Keputusan Kepala Lab atas dokumen pengajuan pengadaan 2 semester berhasil disimpan.');
    }

    $title = 'Approval Pengajuan Pengadaan 2 Semester (Jabatan: Kalab)'; $active = 'approval_kalab'; $d = db();
    $dariPlpBelumTarik = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui' && empty($r['diajukan_ke_kalab']));
    $menungguKalab = count(array_filter($d['pengajuan'], fn($r) => $r['status'] === 'menunggu'));
    $lowCountKalab = count(array_filter($d['barang'], 'low'));
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="cards cards-3">
        <div>
            <span>Pengajuan menunggu approval Kalab</span>
            <strong><?= str_pad((string)$menungguKalab, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Usulan siap ditarik dari PLP</span>
            <strong><?= str_pad((string)count($dariPlpBelumTarik), 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Bahan lab di bawah minimum</span>
            <strong><?= str_pad((string)$lowCountKalab, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
    </div>
    <?php
    render_wf_steps('Alur persetujuan pengajuan pengadaan 2 semester', [
        'Usulan Kebutuhan Dosen & Jobsheet',
        'PLP Susun Draf Pengajuan',
        'Approval Kalab (Jabatan Dosen)',
        'Diajukan ke Pusat Polban & Distribusi Lab',
    ], 3, 'Akun Kalab merupakan entitas Dosen dengan atribut jabatan Kepala Lab. Kalab menyetujui draf pengajuan dari PLP sebelum diajukan ke pusat Polban.');
    ?>

    <h2>1. Draf Pengajuan Pengadaan 2 Semester dari PLP (Menunggu Approval Kalab)</h2>
    <?php req_table(array_reverse($d['pengajuan']), 'pengajuan', true, 'Approval Kalab'); ?>

    <h2>2. Usulan / Permintaan Dosen yang Sudah Diverifikasi PLP (Siap Ditarik ke Pengajuan)</h2>
    <?php if (!$dariPlpBelumTarik): ?>
        <p class="empty">Seluruh usulan yang disetujui PLP sudah masuk ke daftar pengajuan Kepala Lab di atas.</p>
    <?php else: ?>
        <div class="tablewrap"><table>
            <tr><th>No</th><th>Tanggal</th><th>Dosen</th><th>Bahan &amp; Lab</th><th>Ukuran/Mhs &amp; Pcs</th><th>Total Permintaan</th><th>Kelas &amp; Kegiatan</th><th>Status PLP</th><th>Aksi Kalab</th></tr>
            <?php foreach (array_reverse($dariPlpBelumTarik) as $pd): ?>
            <tr>
                <td>#<?= e($pd['id']) ?></td>
                <td><?= e($pd['tanggal']) ?></td>
                <td><?= e($pd['oleh']) ?></td>
                <td><?= e($pd['nama'] . ' (' . $pd['kode'] . ')') ?><br><small class="muted"><?= e($pd['lab'] ?? 'Lab Permesinan') ?></small></td>
                <td><?= e(($pd['ukuran_per_mhs'] ?? '-') . ' ' . $pd['satuan'] . '/mhs (' . ($pd['jml_pcs'] ?? 1) . ' pcs)') ?></td>
                <td><strong><?= e($pd['jumlah'] . ' ' . $pd['satuan']) ?></strong></td>
                <td><?= e($pd['kelas'] . ' - ' . $pd['sub_praktikum']) ?></td>
                <td><?= chip($pd['status']) ?></td>
                <td>
                    <form class="inline" method="post">
                        <?= field_csrf() ?>
                        <input type="hidden" name="aksi" value="tarik_plp">
                        <input type="hidden" name="pd_id" value="<?= e($pd['id']) ?>">
                        <button class="btn" type="submit">Tarik &amp; Setujui (Kalab)</button>
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

    <h2>B. Daftar Pengajuan Bahan Praktikum 2 Semester (Dosen ke PLP)</h2>
    <?php dosen_table(array_reverse($rowsDosen)); ?>

    <h2>C. Daftar Permintaan Pembelian</h2>
    <?php if (!$d['pembelian']): ?><p class="empty">Belum ada permintaan pembelian.</p><?php else: ?>
    <div class="tablewrap"><table><tr><th>No</th><th>Tanggal</th><th>Barang</th><th>Jumlah</th><th>Dari Pengajuan</th><th>Status</th></tr>
    <?php foreach (array_reverse($d['pembelian']) as $b): ?>
    <tr><td><?= e($b['id']) ?></td><td><?= e($b['tanggal']) ?></td><td><?= e($b['nama']) ?></td><td><?= e($b['jumlah'] . ' ' . $b['satuan']) ?></td><td>#<?= e($b['pengajuan_id']) ?></td><td><?= chip($b['status']) ?></td></tr>
    <?php endforeach; ?></table></div><?php endif;
    require __DIR__ . '/partials/footer.php';
}

// Dosen (Revisi Notulensi Bagian 3):
// - Visibilitas Stok Dibatasi: Dosen biasa TIDAK diperlihatkan informasi jumlah ketersediaan stok bahan di laboratorium.
// - Pemisahan Dua Jenis Transaksi:
//   1. Permintaan Penggunaan Bahan (untuk pelaksanaan praktikum atau keperluan lain seperti Penelitian).
//   2. Usulan Kebutuhan Bahan sebagai bahan pertimbangan pengadaan 2 semester (dibatasi Time Frame misal Maret–Mei).
// - Ruang Lingkup Pemilihan Bahan & Lab: Dosen biasa hanya memilih bahan pada mata kuliah/praktikum yang diampunya serta lab tujuan.
// - Usulan Bahan Baru: Dosen dapat menambahkan data kebutuhan bahan baru yang belum tersedia di master.
function fitur_pengajuan_dosen() {
    $redirect = role_url('pengajuan_dosen');
    $d = db();
    $jab = current_jabatan();
    $isPejabat = in_array($jab, ['kalab', 'kajur'], true);
    $br = $d['business_rules'];
    $periodeAktif = !empty($br['periode_aktif']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $jenisTransaksi = $_POST['jenis_transaksi'] ?? 'permintaan_pakai';
        $kategoriPakai = $_POST['kategori_penggunaan'] ?? 'praktikum';
        $labTujuan = trim($_POST['lab_tujuan'] ?? 'Lab Permesinan');

        if ($jenisTransaksi === 'usulan_2smt' && !$periodeAktif) {
            go($redirect, "Periode pengisian Usulan Kebutuhan Bahan 2 Semester sedang DITUTUP (Time Frame: {$br['bulan_mulai']}–{$br['bulan_selesai']}). Silakan pilih 'Permintaan Penggunaan Bahan' atau hubungi Administrator.", 'bad');
        }

        if (!empty($_POST['items_json'])) {
            $items = json_decode((string)$_POST['items_json'], true);
            $jmlMhs = max(1, post_int('jml_mhs'));
            $kelas = trim($_POST['kelas'] ?? '');
            $sub = trim($_POST['sub_praktikum'] ?? '');
            $namaPengaju = trim($_POST['nama_pengaju'] ?? $_SESSION['user']['nama']);

            if (!is_array($items) || !$items || $kelas === '' || $sub === '') {
                go($redirect, 'Pilih minimal satu bahan dari katalog, lalu lengkapi Kelas/Unit, Jumlah Mahasiswa/Sampel, dan Mata Kuliah/Judul Penelitian.', 'bad');
            }

            $addedCount = 0;
            $addedNames = [];
            foreach ($items as $it) {
                $ukPerMhs = max(1, (int)($it['uk_per_mhs'] ?? 0));
                if ($ukPerMhs < 1) continue;
                $jml = $ukPerMhs * $jmlMhs;

                if (!empty($it['is_new'])) {
                    $namaBaru = trim((string)($it['nama'] ?? ''));
                    $spekBaru = trim((string)($it['spesifikasi'] ?? ''));
                    $satuanBaru = trim((string)($it['satuan'] ?? 'cm')) ?: 'cm';
                    $ukPcsBaru = max(1, (int)($it['ukuran_pcs'] ?? 100));
                    if ($namaBaru === '') continue;
                    $kodeBaru = 'MPK-' . str_pad((string)(count($d['barang']) + 25), 4, '0', STR_PAD_LEFT);
                    $d['barang'][] = [
                        'kode' => $kodeBaru,
                        'nama' => $namaBaru,
                        'spesifikasi' => $spekBaru !== '' ? $spekBaru : "1 pcs = $ukPcsBaru $satuanBaru",
                        'satuan' => $satuanBaru,
                        'ukuran_pcs' => $ukPcsBaru,
                        'rak' => '',
                        'lab' => $labTujuan,
                        'minimum' => $jml,
                        'stok' => 0,
                        'foto' => '',
                    ];
                    $n = count($d['barang']) - 1;
                } else {
                    $n = find_idx($d['barang'], 'kode', $it['kode'] ?? '');
                }

                if ($n === null) continue;
                $b = $d['barang'][$n];
                $ukPcs = max(1, (int)($b['ukuran_pcs'] ?? 1));
                $jmlPcs = (int)ceil($jml / $ukPcs);
                $pesan = max(0, $jml - $b['stok']);

                $labelJenis = $jenisTransaksi === 'permintaan_pakai' ? 'Permintaan Penggunaan' : 'Usulan Kebutuhan 2 Smt';
                $d['pengajuan_dosen'][] = [
                    'id' => count($d['pengajuan_dosen']) + 1,
                    'tanggal' => date('Y-m-d'),
                    'oleh' => $namaPengaju !== '' ? $namaPengaju : $_SESSION['user']['nama'],
                    'oleh_email' => $_SESSION['user']['email'],
                    'jenis_transaksi' => $jenisTransaksi,
                    'kategori_penggunaan' => $kategoriPakai,
                    'lab' => $labTujuan,
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
                    'ket' => "[$labelJenis - " . ucfirst($kategoriPakai) . " | $labTujuan] Kelas/Unit: $kelas | Kegiatan: $sub | $jmlMhs × $ukPerMhs {$b['satuan']} = $jml {$b['satuan']} (≈ $jmlPcs pcs)",
                    'status' => 'menunggu',
                    'diajukan_ke_kalab' => false,
                    'catatan' => '',
                ];
                $addedCount++;
                $addedNames[] = "{$b['nama']} ($jml {$b['satuan']} / $jmlPcs pcs)";
            }

            if ($addedCount < 1) {
                go($redirect, 'Tidak ada bahan valid yang dipilih. Pastikan jumlah/ukuran per mahasiswa lebih dari 0.', 'bad');
            }
            save($d);
            $judulNotif = $jenisTransaksi === 'permintaan_pakai' ? 'Permintaan penggunaan bahan' : 'Usulan kebutuhan bahan 2 semester';
            go($redirect, "$judulNotif untuk $addedCount jenis bahan (" . implode(', ', $addedNames) . ") di $labTujuan berhasil dikirim untuk ditinjau PLP.");
        }
    }

    $title = 'Permintaan Penggunaan & Usulan Kebutuhan Bahan (Dosen)';
    $active = 'pengajuan_dosen';
    $milik = array_reverse(array_filter($d['pengajuan_dosen'], fn($r) => $r['oleh_email'] === $_SESSION['user']['email']));

    // Filter mata kuliah yang diampu oleh dosen ini (Dosen Biasa hanya melihat matkul yang diampunya)
    $emailDosen = $_SESSION['user']['email'] ?? '';
    $matkulDosen = array_values(array_filter($d['master_matkul'], fn($mk) => $isPejabat || ($mk['dosen_email'] ?? '') === $emailDosen));
    if (!$matkulDosen) $matkulDosen = $d['master_matkul'];

    require __DIR__ . '/partials/header.php';
    ?>

    <div class="box" style="margin-bottom:18px;background:var(--theme-soft);border-color:var(--theme-border)">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
            <div>
                <strong>Time Frame Usulan Kebutuhan Pengadaan 2 Semester: <?= e($br['bulan_mulai'] . ' – ' . $br['bulan_selesai'] . ' (' . $br['tahun_ajaran'] . ')') ?></strong>
                <div class="muted" style="font-size:12.5px;margin-top:2px">
                    Status Periode Usulan 2 Semester: <?= $periodeAktif ? '<span class="chip ok">DIBUKA</span>' : '<span class="chip bad">DITUTUP</span>' ?> &middot;
                    <?= $isPejabat ? 'Anda memiliki jabatan <strong>' . e(JABATAN_DOSEN[$jab] ?? '') . '</strong> (dapat mengakses seluruh bahan & lab).' : 'Sebagai <strong>Dosen Pengampu</strong>, informasi jumlah stok bahan laboratorium disembunyikan dan pilihan bahan mengikuti mata kuliah/laboratorium yang diampu.' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================================
         TAHAP 1: FILTER MATKUL/LAB & KATALOG KOTAK-KOTAK BAHAN + RINGKASAN
         ====================================================================== -->
    <div id="dsn_step_catalog">
        <div class="box form" style="margin-bottom:18px">
            <h3 style="margin:0 0 10px">Pilih Jenis Transaksi, Mata Kuliah yang Diampu &amp; Laboratorium Tujuan</h3>
            <div class="grid3">
                <div>
                    <label for="sel_jenis_transaksi">1. Jenis Transaksi Dosen *</label>
                    <select id="sel_jenis_transaksi" onchange="syncFilterKatalogDosen()">
                        <option value="permintaan_pakai">Permintaan Penggunaan Bahan (Praktikum / Penelitian)</option>
                        <option value="usulan_2smt"<?= !$periodeAktif ? ' disabled' : '' ?>>Usulan Kebutuhan Bahan (Pertimbangan Pengadaan 2 Semester)<?= !$periodeAktif ? ' [DITUTUP]' : '' ?></option>
                    </select>
                </div>
                <div>
                    <label for="sel_kategori_pakai">2. Keperluan Penggunaan *</label>
                    <select id="sel_kategori_pakai" onchange="syncFilterKatalogDosen()">
                        <option value="praktikum">Praktikum Rutin Mahasiswa (Sesuai Matkul Diampu)</option>
                        <option value="penelitian">Penelitian / Tugas Akhir / Non-Praktikum</option>
                    </select>
                </div>
                <div>
                    <label for="sel_matkul_ampu">3. Mata Kuliah &amp; Laboratorium Tujuan *</label>
                    <select id="sel_matkul_ampu" onchange="syncFilterKatalogDosen()">
                        <?php foreach ($matkulDosen as $mk): ?>
                        <option value="<?= e($mk['kode']) ?>"
                                data-nama="<?= e($mk['nama']) ?>"
                                data-prodi="<?= e($mk['prodi']) ?>"
                                data-lab="<?= e($mk['lab']) ?>"
                                data-jobsheet="<?= e($mk['jobsheet']) ?>"
                                data-bahan="<?= e(implode(',', $mk['bahan_kode'] ?? [])) ?>">
                            <?= e($mk['nama'] . ' (' . $mk['prodi'] . ') — ' . $mk['lab']) ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if ($isPejabat): ?>
                        <option value="__ALL__" data-nama="" data-prodi="" data-lab="Lab Permesinan" data-jobsheet="" data-bahan="">-- Semua Mata Kuliah &amp; Laboratorium (Akses Kalab/Kajur) --</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="dsn-catalog-layout">
            <div>
                <input type="search" id="dsn_search" class="dsn-search-input" placeholder="Cari nama bahan, kode, atau spesifikasi pada mata kuliah yang diampu ...">

                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px">
                    <h3 class="dsn-sec-title" style="margin:0">Katalog Bahan Habis Pakai (Kebutuhan per Mahasiswa / Sampel)</h3>
                    <button type="button" class="btn sec" style="padding:7px 14px;font-size:12.5px" onclick="bukaModalBahanBaru()">+ Usulkan Bahan Baru</button>
                </div>

                <div class="dsn-grid" id="dsn_bahan_grid">
                    <?php foreach ($d['barang'] as $b):
                        $ukPcs = max(1, (int)($b['ukuran_pcs'] ?? 1));
                        $stepVal = $b['satuan'] === 'cm' ? 5 : 1;
                    ?>
                    <article class="dsn-card dsn-bahan-card" id="card_<?= e($b['kode']) ?>" data-kode="<?= e($b['kode']) ?>" data-search="<?= e(strtolower($b['nama'] . ' ' . $b['kode'] . ' ' . $b['spesifikasi'])) ?>">
                        <div>
                            <h4><?= e($b['nama']) ?></h4>
                            <p class="dsn-card-spec"><?= e($b['kode']) ?> &middot; <?= e($b['spesifikasi']) ?></p>
                            <div class="dsn-card-stock">
                                <?php if ($isPejabat): ?>
                                    <span>Stok Lab: <?= e($b['stok'] . ' ' . $b['satuan']) ?></span>
                                    <span class="chip <?= low($b) ? 'warn' : 'ok' ?>"><?= e($b['lab'] ?? 'Lab Permesinan') ?></span>
                                <?php else: ?>
                                    <!-- Visibilitas stok disembunyikan untuk Dosen Biasa sesuai Notulensi Bagian 3 -->
                                    <span class="chip ok">Katalog Master Bahan</span>
                                    <span class="muted" style="font-size:11.5px">1 pcs = <?= e($ukPcs . ' ' . $b['satuan']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:4px">
                            <div class="dsn-qty-ctrl">
                                <button type="button" class="dsn-qty-btn" onclick="ubahQty('<?= e($b['kode']) ?>', -<?= $stepVal ?>)" aria-label="Kurangi">&minus;</button>
                                <input type="number" min="0" value="0" class="dsn-qty-input" id="qty_<?= e($b['kode']) ?>" oninput="setQtyManual('<?= e($b['kode']) ?>', this.value)" aria-label="Ukuran per mahasiswa <?= e($b['nama']) ?>">
                                <button type="button" class="dsn-qty-btn" onclick="ubahQty('<?= e($b['kode']) ?>', <?= $stepVal ?>)" aria-label="Tambah">+</button>
                            </div>
                            <small class="muted" style="font-size:11.5px;font-weight:600"><?= e($b['satuan']) ?> / org</small>
                        </div>
                    </article>
                    <?php endforeach; ?>

                    <!-- Kotak Tambah Usulan Bahan Baru -->
                    <article class="dsn-card dsn-card-new" data-kode="__NEW__" data-search="tambah usulan bahan baru custom penelitian praktikum">
                        <div>
                            <h4>+ Usulan Bahan Baru</h4>
                            <p class="dsn-card-spec">Bahan belum tersedia di daftar master? Tambahkan data kebutuhan bahan baru beserta spesifikasinya.</p>
                            <p class="dsn-card-highlight">Otomatis diajukan ke PLP</p>
                        </div>
                        <div class="dsn-card-actions">
                            <button type="button" class="btn" onclick="bukaModalBahanBaru()">+ Tambah Bahan Baru</button>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Panel Kanan: Ringkasan Pilihan -->
            <aside class="dsn-summary-panel" aria-label="Ringkasan Pilihan Bahan">
                <h3>Ringkasan Pilihan</h3>
                <div id="dsn_cart_list">
                    <div class="dsn-sum-empty">Belum ada bahan dipilih.</div>
                </div>
                <div id="dsn_cart_footer" style="display:none">
                    <div class="dsn-sum-total">
                        <span>Total Bahan Dipilih</span>
                        <span id="dsn_cart_total_label">0 jenis</span>
                    </div>
                    <button type="button" class="btn dsn-btn-submit-step1" onclick="lanjutKeFormPengajuan()">Lanjut Isi Form Detail</button>
                </div>
            </aside>
        </div>
    </div>

    <!-- ======================================================================
         TAHAP 2: RINGKASAN ITEM & FORM PERMINTAAN / USULAN KEBUTUHAN BAHAN
         ====================================================================== -->
    <div id="dsn_step_form" style="display:none;margin-bottom:28px">
        <button type="button" class="dsn-back-btn" onclick="kembaliKeKatalog()">&lt; Kembali ke Katalog</button>
        <h2 id="dsn_step2_heading" style="margin:0 0 14px;font-size:24px">Form Permintaan Penggunaan &amp; Usulan Kebutuhan Bahan</h2>

        <div class="box">
            <h3 style="margin:0 0 4px">Ringkasan Item Bahan</h3>
            <p class="muted" style="font-size:13px;margin:0 0 14px">Periksa kembali daftar bahan yang dipilih. Total kebutuhan dihitung otomatis dari ukuran per orang/sampel &times; jumlah mahasiswa/sampel.</p>
            <div class="tablewrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Bahan</th>
                            <th>Kategori</th>
                            <th>Spesifikasi &amp; Ukuran Pcs</th>
                            <th>Ukuran / Orang</th>
                            <th>Total Kebutuhan</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="dsn_review_tbody"></tbody>
                </table>
            </div>
            <div class="dsn-sum-total" style="margin-top:12px">
                <span>Total Keseluruhan Bahan</span>
                <span id="dsn_review_total_badge">0 jenis</span>
            </div>
        </div>

        <form class="box form" method="post" id="dsn_form_submit">
            <?= field_csrf() ?>
            <input type="hidden" name="items_json" id="dsn_items_json" value="">

            <h3 style="margin:0 0 12px">Detail Transaksi Dosen</h3>
            <div class="dsn-section-label">1. Jenis Transaksi, Keperluan &amp; Laboratorium Tujuan</div>

            <div class="grid3">
                <div>
                    <label for="f_jenis_transaksi">Jenis Transaksi *</label>
                    <select id="f_jenis_transaksi" name="jenis_transaksi">
                        <option value="permintaan_pakai">Permintaan Penggunaan Bahan di Lab</option>
                        <option value="usulan_2smt"<?= !$periodeAktif ? ' disabled' : '' ?>>Usulan Kebutuhan Bahan (Pengadaan 2 Semester)</option>
                    </select>
                </div>
                <div>
                    <label for="f_kategori_penggunaan">Kategori Keperluan *</label>
                    <select id="f_kategori_penggunaan" name="kategori_penggunaan">
                        <option value="praktikum">Praktikum Mahasiswa</option>
                        <option value="penelitian">Penelitian / Non-Praktikum</option>
                    </select>
                </div>
                <div>
                    <label for="f_lab_tujuan">Laboratorium Tujuan *</label>
                    <select id="f_lab_tujuan" name="lab_tujuan">
                        <?php foreach (LAB_LIST as $lb): ?>
                        <option value="<?= e($lb) ?>"><?= e($lb) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid2">
                <div>
                    <label for="f_nama_pengaju">Nama dosen pengaju *</label>
                    <input id="f_nama_pengaju" name="nama_pengaju" value="<?= e($_SESSION['user']['nama']) ?>" required>
                </div>
                <div>
                    <label for="f_identitas">NIP / Email institusi *</label>
                    <input id="f_identitas" value="<?= e($_SESSION['user']['email']) ?>" readonly style="background:#F8FAFC">
                </div>
            </div>

            <div class="grid2">
                <div>
                    <label for="f_kelas">Kelas / Program Studi / Tim Penelitian *</label>
                    <input id="f_kelas" name="kelas" placeholder="mis. 2B - D3 Teknik Mesin atau Tim Penelitian Material" required>
                </div>
                <div>
                    <label for="f_jml_mhs">Jumlah mahasiswa / sampel uji *</label>
                    <input id="f_jml_mhs" name="jml_mhs" type="number" min="1" value="24" placeholder="mis. 24" required oninput="renderReviewTable()">
                </div>
            </div>

            <div class="grid2">
                <div>
                    <label for="f_sub_praktikum">Mata kuliah / Jobsheet / Judul Penelitian *</label>
                    <input id="f_sub_praktikum" name="sub_praktikum" placeholder="mis. Praktik Pemesinan Bubut / Penelitian Uji Tarik" required>
                </div>
                <div>
                    <label for="f_semester_info">Time Frame &amp; Periode</label>
                    <input id="f_semester_info" value="<?= e($br['tahun_ajaran'] . ' (Time Frame: ' . $br['bulan_mulai'] . '–' . $br['bulan_selesai'] . ')') ?>" readonly style="background:#F8FAFC;font-weight:600">
                </div>
            </div>

            <p id="dsn_calc_summary" class="calc" style="margin-top:6px"></p>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px">
                <button type="button" class="btn sec" onclick="kembaliKeKatalog()">Ubah Pilihan Bahan</button>
                <button type="submit" class="btn">Kirim ke PLP Laboratorium</button>
            </div>
        </form>
    </div>

    <!-- Modal Tambah Bahan Baru -->
    <div class="dsn-modal-backdrop" id="modal_bahan_baru">
        <div class="dsn-modal form">
            <h3>Usulan Kebutuhan Bahan Baru</h3>
            <p class="muted" style="font-size:13px;margin:0 0 14px">Tambahkan data kebutuhan bahan baru yang belum tersedia di daftar master beserta jumlah kebutuhannya.</p>
            <label for="mb_nama">Nama Bahan Baru *</label>
            <input id="mb_nama" placeholder="Contoh: Aluminium Silinder 6061">
            <label for="mb_spek">Spesifikasi / Ukuran *</label>
            <input id="mb_spek" placeholder="Contoh: Ø25 mm, 1 pcs = 100 cm">
            <div class="grid3">
                <div>
                    <label for="mb_satuan">Satuan *</label>
                    <input id="mb_satuan" value="cm" placeholder="cm / lembar / pcs">
                </div>
                <div>
                    <label for="mb_uk_pcs">Ukuran per 1 Pcs *</label>
                    <input id="mb_uk_pcs" type="number" min="1" value="100">
                </div>
                <div>
                    <label for="mb_uk_mhs">Kebutuhan / Orang *</label>
                    <input id="mb_uk_mhs" type="number" min="1" value="15">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px">
                <button type="button" class="btn sec" onclick="tutupModal('modal_bahan_baru')">Batal</button>
                <button type="button" class="btn" onclick="simpanBahanBaruKeKatalog()">Tambahkan &amp; Pilih</button>
            </div>
        </div>
    </div>

    <h2>Riwayat Permintaan Penggunaan &amp; Usulan Kebutuhan Bahan Saya</h2>
    <?php dosen_table($milik); ?>

    <script>
    const MASTER_BAHAN = <?= json_encode(array_values($d['barang']), JSON_UNESCAPED_UNICODE) ?>;
    const IS_PEJABAT = <?= $isPejabat ? 'true' : 'false' ?>;

    const bahanMap = {};
    MASTER_BAHAN.forEach(b => {
        bahanMap[b.kode] = {
            kode: b.kode,
            nama: b.nama,
            spesifikasi: b.spesifikasi,
            satuan: b.satuan,
            ukuran_pcs: +(b.ukuran_pcs || 1),
            is_new: false
        };
    });

    const cart = {};

    function syncFilterKatalogDosen() {
        const selMk = document.getElementById('sel_matkul_ampu');
        const selKat = document.getElementById('sel_kategori_pakai');
        const selJns = document.getElementById('sel_jenis_transaksi');
        const opt = selMk ? selMk.selectedOptions[0] : null;
        const kat = selKat ? selKat.value : 'praktikum';
        const allowedStr = (opt && opt.dataset.bahan) ? opt.dataset.bahan : '';
        const allowedList = allowedStr ? allowedStr.split(',').map(s => s.trim()).filter(Boolean) : [];
        const q = (document.getElementById('dsn_search').value || '').trim().toLowerCase();

        document.querySelectorAll('#dsn_bahan_grid .dsn-card').forEach(card => {
            const kd = card.dataset.kode || '';
            const hay = card.dataset.search || '';
            const matchQ = (q === '' || hay.includes(q));
            // Untuk praktikum oleh Dosen Biasa, batasi hanya bahan yang digunakan pada mata kuliah yang diampunya (atau bahan baru)
            let matchMk = true;
            if (!IS_PEJABAT && kat === 'praktikum' && allowedList.length > 0 && kd !== '__NEW__' && !kd.startsWith('BARU-')) {
                matchMk = allowedList.includes(kd);
            }
            card.style.display = (matchQ && matchMk) ? '' : 'none';
        });

        if (opt) {
            if (opt.dataset.lab) document.getElementById('f_lab_tujuan').value = opt.dataset.lab;
            if (kat === 'praktikum') {
                if (opt.dataset.nama) document.getElementById('f_sub_praktikum').value = opt.dataset.nama + (opt.dataset.jobsheet ? ' (' + opt.dataset.jobsheet + ')' : '');
                if (opt.dataset.prodi && !document.getElementById('f_kelas').value) document.getElementById('f_kelas').value = '2B - ' + opt.dataset.prodi;
            } else {
                document.getElementById('f_sub_praktikum').placeholder = 'Masukkan judul kegiatan penelitian / pengujian';
                document.getElementById('f_kelas').placeholder = 'Masukkan nama tim penelitian / laboratorium';
            }
        }
        if (selJns) document.getElementById('f_jenis_transaksi').value = selJns.value;
        if (selKat) document.getElementById('f_kategori_penggunaan').value = selKat.value;
    }

    function ubahQty(kode, delta) {
        const cur = +(cart[kode] || 0);
        const next = Math.max(0, cur + delta);
        setQty(kode, next);
    }

    function setQtyManual(kode, val) {
        const num = Math.max(0, parseInt(val, 10) || 0);
        setQty(kode, num);
    }

    function setQty(kode, qty) {
        if (qty <= 0) {
            delete cart[kode];
            qty = 0;
        } else {
            cart[kode] = qty;
        }
        const inp = document.getElementById('qty_' + kode);
        if (inp && +inp.value !== qty) inp.value = qty;
        const card = document.getElementById('card_' + kode);
        if (card) card.classList.toggle('selected', qty > 0);
        renderCartSidebar();
    }

    function bukaModalBahanBaru() {
        document.getElementById('modal_bahan_baru').classList.add('open');
    }

    function tutupModal(id) {
        document.getElementById(id).classList.remove('open');
    }

    function simpanBahanBaruKeKatalog() {
        const nama = document.getElementById('mb_nama').value.trim();
        const spek = document.getElementById('mb_spek').value.trim();
        const satuan = document.getElementById('mb_satuan').value.trim() || 'cm';
        const ukPcs = Math.max(1, parseInt(document.getElementById('mb_uk_pcs').value, 10) || 100);
        const ukMhs = Math.max(1, parseInt(document.getElementById('mb_uk_mhs').value, 10) || 10);
        if (!nama) {
            alert('Masukkan nama bahan baru terlebih dahulu.');
            return;
        }
        const kode = 'BARU-' + (Object.keys(bahanMap).length + 1);
        const fullSpek = spek || (`1 pcs = ${ukPcs} ${satuan}`);
        bahanMap[kode] = {
            kode: kode,
            nama: nama,
            spesifikasi: fullSpek,
            satuan: satuan,
            ukuran_pcs: ukPcs,
            is_new: true
        };

        const grid = document.getElementById('dsn_bahan_grid');
        const stepVal = satuan === 'cm' ? 5 : 1;
        const art = document.createElement('article');
        art.className = 'dsn-card dsn-bahan-card selected';
        art.id = 'card_' + kode;
        art.dataset.kode = kode;
        art.dataset.search = (nama + ' ' + fullSpek).toLowerCase();
        art.innerHTML = `
            <div>
                <h4>${esc(nama)}</h4>
                <p class="dsn-card-spec">Usulan Bahan Baru &middot; ${esc(fullSpek)}</p>
                <div class="dsn-card-stock">
                    <span class="chip warn">Usulan Bahan Baru</span>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:4px">
                <div class="dsn-qty-ctrl">
                    <button type="button" class="dsn-qty-btn" onclick="ubahQty('${kode}', -${stepVal})">&minus;</button>
                    <input type="number" min="0" value="${ukMhs}" class="dsn-qty-input" id="qty_${kode}" oninput="setQtyManual('${kode}', this.value)">
                    <button type="button" class="dsn-qty-btn" onclick="ubahQty('${kode}', ${stepVal})">+</button>
                </div>
                <small class="muted" style="font-size:11.5px;font-weight:600">${esc(satuan)} / org</small>
            </div>
        `;
        grid.insertBefore(art, grid.lastElementChild);

        setQty(kode, ukMhs);
        document.getElementById('mb_nama').value = '';
        document.getElementById('mb_spek').value = '';
        tutupModal('modal_bahan_baru');
    }

    function renderCartSidebar() {
        const listEl = document.getElementById('dsn_cart_list');
        const footEl = document.getElementById('dsn_cart_footer');
        const totalLbl = document.getElementById('dsn_cart_total_label');
        const keys = Object.keys(cart);

        if (keys.length === 0) {
            listEl.innerHTML = '<div class="dsn-sum-empty">Belum ada bahan dipilih.</div>';
            footEl.style.display = 'none';
            return;
        }

        let html = '';
        keys.forEach(kode => {
            const b = bahanMap[kode];
            const qty = cart[kode];
            if (!b) return;
            const stepVal = b.satuan === 'cm' ? 5 : 1;
            const statusChip = b.is_new
                ? '<span class="chip warn">Bahan Baru</span>'
                : '<span class="chip ok">Dipilih</span>';
            html += `
                <div class="dsn-sum-item">
                    <div class="dsn-sum-head">
                        <strong>${esc(b.nama)}</strong>
                        ${statusChip}
                    </div>
                    <div class="dsn-sum-spec">${esc(b.spesifikasi)} (${qty} ${esc(b.satuan)}/org)</div>
                    <div class="dsn-sum-ctrl">
                        <div class="dsn-qty-ctrl">
                            <button type="button" class="dsn-qty-btn" onclick="ubahQty('${kode}', -${stepVal})">&minus;</button>
                            <input type="number" min="1" value="${qty}" class="dsn-qty-input" oninput="setQtyManual('${kode}', this.value)">
                            <button type="button" class="dsn-qty-btn" onclick="ubahQty('${kode}', ${stepVal})">+</button>
                        </div>
                        <button type="button" class="dsn-btn-del" onclick="setQty('${kode}', 0)">Hapus</button>
                    </div>
                </div>
            `;
        });

        listEl.innerHTML = html;
        totalLbl.textContent = `${keys.length} jenis bahan`;
        footEl.style.display = 'block';
    }

    function lanjutKeFormPengajuan() {
        if (Object.keys(cart).length === 0) return;
        syncFilterKatalogDosen();
        document.getElementById('dsn_step_catalog').style.display = 'none';
        document.getElementById('dsn_step_form').style.display = 'block';
        renderReviewTable();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function kembaliKeKatalog() {
        document.getElementById('dsn_step_form').style.display = 'none';
        document.getElementById('dsn_step_catalog').style.display = 'block';
    }

    function renderReviewTable() {
        const tbody = document.getElementById('dsn_review_tbody');
        const jmlMhs = Math.max(1, parseInt(document.getElementById('f_jml_mhs').value, 10) || 1);
        const keys = Object.keys(cart);
        tbody.innerHTML = '';

        const payload = [];
        let totalPcsAll = 0;

        keys.forEach((kode, idx) => {
            const b = bahanMap[kode];
            const ukMhs = cart[kode];
            if (!b) return;
            const total2Smt = ukMhs * jmlMhs;
            const pcs = Math.ceil(total2Smt / Math.max(1, b.ukuran_pcs));
            totalPcsAll += pcs;

            payload.push({
                kode: b.kode,
                nama: b.nama,
                spesifikasi: b.spesifikasi,
                satuan: b.satuan,
                ukuran_pcs: b.ukuran_pcs,
                uk_per_mhs: ukMhs,
                is_new: !!b.is_new
            });

            const chipHtml = b.is_new
                ? '<span class="chip warn">Usulan Bahan Baru</span>'
                : '<span class="chip ok">Sesuai Matkul Diampu</span>';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td><strong>${esc(b.nama)}</strong></td>
                <td>Bahan Habis Pakai</td>
                <td>${esc(b.spesifikasi)} (1 pcs = ${b.ukuran_pcs} ${esc(b.satuan)})</td>
                <td>${ukMhs} ${esc(b.satuan)} / org</td>
                <td><strong>${total2Smt} ${esc(b.satuan)}</strong> (≈ ${pcs} pcs)</td>
                <td>${chipHtml}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('dsn_review_total_badge').textContent = `${keys.length} jenis bahan (≈ ${totalPcsAll} pcs untuk ${jmlMhs} orang/sampel)`;
        document.getElementById('dsn_items_json').value = JSON.stringify(payload);
        document.getElementById('dsn_calc_summary').textContent =
            `Ringkasan Kebutuhan: ${keys.length} jenis bahan untuk ${jmlMhs} mahasiswa/sampel (Total setara ≈ ${totalPcsAll} pcs/batang). Setelah dikirim, PLP akan meninjau serta menggabungkannya dengan silabus/jobsheet untuk pengajuan ke Kepala Lab.`;
    }

    document.getElementById('dsn_search').addEventListener('input', syncFilterKatalogDosen);

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    syncFilterKatalogDosen();
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Mahasiswa (Revisi Notulensi Bagian 4):
// - Akun Individu & Tidak Dapat Diwakilkan: pengambilan bahan hanya dilakukan oleh 1 mahasiswa yang bersangkutan.
// - Penanganan Kelebihan Jatah (Over-Limit): mendukung pencatatan jatah minus dan kompensasi/denda ketika mahasiswa mengambil melebihi batas jatah praktikum.
function fitur_konfirmasi_mhs() {
    $redirect = role_url('permintaan');
    $d = db();
    $br = $d['business_rules'];
    $tarifDenda = (int)($br['denda_per_satuan'] ?? 5000);
    $izinkanOverLimit = ($br['kebijakan_overlimit'] ?? '') !== 'Batasi Ketat Sesuai Jatah Maksimum';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();

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
                'tanggal' => date('Y-m-d'),
                'nim' => $_SESSION['user']['nim'] ?? ($parent['nim'] ?? ''),
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
                'jatah_minus' => 0,
                'kompensasi' => '',
                'kelas' => $parent['kelas'],
                'sub_praktikum' => $parent['sub_praktikum'],
                'ket' => "Pengambilan Lanjutan Individu (Tidak Diwakilkan): {$parent['sub_praktikum']} | Diambil: $jmlSisa {$parent['satuan']} (Sisa: $sisaBaru {$parent['satuan']})",
                'status' => 'menunggu',
                'disetujui_oleh' => '',
                'catatan' => '',
            ];
            unset($parent);
            save($d);
            go($redirect, "Permintaan pengambilan sisa bahan ($jmlSisa) oleh akun individu Anda berhasil dikirim.");
        }

        if (empty($_POST['pernyataan_individu'])) {
            go($redirect, 'Wajib mencentang pernyataan bahwa pengambilan bahan dilakukan secara individu dan tidak diwakilkan.', 'bad');
        }

        $pdId = post_int('pengajuan_dosen_id');
        $n = null;
        $totalJatah = post_int('total_jatah');
        $jml = post_int('jumlah');
        $kelas = trim($_POST['kelas'] ?? '');
        $sub = trim($_POST['sub_praktikum'] ?? '');
        $alasanOver = trim($_POST['alasan_overlimit'] ?? '');
        $jenisKomp = trim($_POST['jenis_kompensasi'] ?? 'pengurangan_nilai');
        $tglOtomatis = date('Y-m-d');

        if ($pdId > 0) {
            $idxPd = find_idx($d['pengajuan_dosen'], 'id', $pdId);
            if ($idxPd !== null) {
                $pd = $d['pengajuan_dosen'][$idxPd];
                $n = find_idx($d['barang'], 'kode', $pd['kode']);
                if ($kelas === '') $kelas = $pd['kelas'];
                if ($sub === '') $sub = $pd['sub_praktikum'];
                if ($totalJatah < 1) $totalJatah = (int)($pd['ukuran_per_mhs'] ?? 0);
            }
        } else {
            $n = find_idx($d['barang'], 'kode', $_POST['kode'] ?? '');
        }

        if ($n === null || $jml < 1) {
            go($redirect, 'Silakan pilih praktikum yang valid dan masukkan jumlah bahan yang diambil (minimal 1).', 'bad');
        }

        if (!$izinkanOverLimit && $totalJatah > 0 && $jml > $totalJatah) {
            go($redirect, "Jumlah yang diambil ($jml) melebihi batas jatah ($totalJatah).", 'bad');
        }

        $selisih = $totalJatah - $jml;
        $sisaAmbil = max(0, $selisih);
        $jatahMinus = $selisih < 0 ? abs($selisih) : 0;
        $b = $d['barang'][$n];

        $kompensasi = '';
        if ($jatahMinus > 0) {
            if ($jenisKomp === 'pengurangan_nilai') {
                $poinNilai = $jatahMinus * 5;
                $kompensasi = "Jatah Minus -{$jatahMinus} {$b['satuan']} (Pengurangan Nilai Praktikum: -{$poinNilai} Poin" . ($alasanOver !== '' ? " | Alasan Gagal: $alasanOver" : '') . ")";
            } elseif ($jenisKomp === 'poin_pelanggaran') {
                $jamKomp = $jatahMinus * 2;
                $kompensasi = "Jatah Minus -{$jatahMinus} {$b['satuan']} (Kompensasi Kerja Lab: {$jamKomp} Jam / Poin Disiplin" . ($alasanOver !== '' ? " | Alasan Gagal: $alasanOver" : '') . ")";
            } else {
                $nominalDenda = $jatahMinus * $tarifDenda;
                $kompensasi = "Jatah Minus -{$jatahMinus} {$b['satuan']} (Denda Penggantian: Rp " . number_format($nominalDenda, 0, ',', '.') . ($alasanOver !== '' ? " | Alasan Gagal: $alasanOver" : '') . ")";
            }
            $tipeAmbil = 'over_limit';
            $ketAmbil = "Pengambilan Over-Limit ($jml/$totalJatah {$b['satuan']} | $kompensasi)";
        } elseif ($sisaAmbil > 0) {
            $tipeAmbil = 'sebagian';
            $ketAmbil = "Pengambilan Sebagian ($jml/$totalJatah {$b['satuan']}, sisa $sisaAmbil {$b['satuan']})";
        } else {
            $tipeAmbil = 'penuh';
            $ketAmbil = "Pengambilan Penuh ($jml {$b['satuan']})";
        }

        $userNim = $_SESSION['user']['nim'] ?? '';
        if (empty($userNim)) {
            $idxU = find_idx($d['users'], 'email', $_SESSION['user']['email'] ?? '');
            if ($idxU !== null) {
                $userNim = $d['users'][$idxU]['nim'] ?? '';
                $_SESSION['user']['nim'] = $userNim;
            }
        }

        $d['permintaan'][] = [
            'id' => count($d['permintaan']) + 1,
            'tanggal' => $tglOtomatis,
            'nim' => $userNim,
            'oleh' => $_SESSION['user']['nama'],
            'oleh_email' => $_SESSION['user']['email'],
            'pengajuan_dosen_id' => $pdId,
            'kode' => $b['kode'],
            'nama' => $b['nama'],
            'satuan' => $b['satuan'],
            'tipe_ambil' => $tipeAmbil,
            'total_jatah' => $totalJatah,
            'jumlah' => $jml,
            'sisa_ambil' => $sisaAmbil,
            'jatah_minus' => $jatahMinus,
            'kompensasi' => $kompensasi,
            'kelas' => $kelas,
            'sub_praktikum' => $sub,
            'ket' => "Pengambilan Individu (1 Mhs, Tidak Diwakilkan) - Praktikum: $sub | $ketAmbil",
            'status' => 'menunggu',
            'disetujui_oleh' => '',
            'catatan' => '',
        ];
        save($d);
        go($redirect, "Permintaan pengambilan bahan individu ($ketAmbil) berhasil dikirim pada tanggal $tglOtomatis.");
    }

    $title = 'Halo, ' . $_SESSION['user']['nama']; $active = 'permintaan';
    $jadwalDosen = array_filter($d['pengajuan_dosen'], fn($r) => $r['status'] === 'disetujui');
    if (!$jadwalDosen) $jadwalDosen = $d['pengajuan_dosen'];
    // Sesuai Notulensi Bagian 4: Setiap mahasiswa hanya melihat dan mengelola akun individunya sendiri
    $milik = array_reverse(array_filter($d['permintaan'], fn($r) => ($r['oleh_email'] ?? '') === ($_SESSION['user']['email'] ?? '')));
    $userNim = $_SESSION['user']['nim'] ?? '';
    if (empty($userNim)) {
        $idxU = find_idx($d['users'], 'email', $_SESSION['user']['email'] ?? '');
        if ($idxU !== null) {
            $userNim = $d['users'][$idxU]['nim'] ?? '';
            $_SESSION['user']['nim'] = $userNim;
        }
    }
    $mhsDisetujui = count(array_filter($milik, fn($r) => $r['status'] === 'disetujui'));
    $mhsMenunggu  = count(array_filter($milik, fn($r) => $r['status'] === 'menunggu'));
    $totalMinus   = array_sum(array_map(fn($r) => (int)($r['jatah_minus'] ?? 0), $milik));
    require __DIR__ . '/partials/header.php';
    ?>
    <div class="cards cards-3">
        <div>
            <span>Pengambilan individu disetujui</span>
            <strong><?= str_pad((string)$mhsDisetujui, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Menunggu pelayanan PLP/Teknisi</span>
            <strong><?= str_pad((string)$mhsMenunggu, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div>
            <span>Catatan jatah minus (Over-Limit)</span>
            <strong style="color:<?= $totalMinus > 0 ? 'var(--bad)' : 'var(--theme)' ?>"><?= $totalMinus > 0 ? '-' . e($totalMinus) : '00' ?></strong>
        </div>
    </div>
    <?php
    render_wf_steps('Alur pengambilan bahan praktikum (Akun Individu Mahasiswa)', [
        'Login akun individu (1 Mhs)',
        'Pilih praktikum & jumlah bahan',
        'Pencatatan jatah / jatah minus',
        'Dilayani Teknisi / PLP di Lab',
    ], $mhsDisetujui > 0 ? 3 : ($mhsMenunggu > 0 ? 2 : 1), 'Aturan Lab: Pengambilan bahan hanya boleh dilakukan oleh 1 mahasiswa pemilik akun (' . $_SESSION['user']['nama'] . ' - NIM ' . ($userNim ?: '-') . ') dan TIDAK DAPAT DIWAKILKAN.');
    ?>
    <form class="box form" method="post">
        <?= field_csrf() ?>
        <input type="hidden" id="kelas" name="kelas" value="">
        <input type="hidden" id="sub_praktikum" name="sub_praktikum" value="">

        <div class="grid3">
            <div>
                <label for="nim">NIM Individu (Otomatis &amp; Terkunci)</label>
                <input id="nim" name="nim" type="text" value="<?= e($userNim) ?>" readonly style="background:#f1f5f9;cursor:not-allowed;font-weight:700">
            </div>
            <div>
                <label for="nama_mhs">Nama Mahasiswa (1 Mahasiswa, Tidak Diwakilkan)</label>
                <input id="nama_mhs" type="text" value="<?= e($_SESSION['user']['nama']) ?>" readonly style="background:#f1f5f9;cursor:not-allowed;font-weight:700">
            </div>
            <div>
                <label for="tanggal_otomatis">Tanggal Pengambilan (Otomatis)</label>
                <input id="tanggal_otomatis" type="text" value="<?= date('Y-m-d') ?>" readonly style="background:#f1f5f9;cursor:not-allowed">
            </div>
        </div>

        <?php
        $barangMap = [];
        foreach ($d['barang'] as $b) {
            $barangMap[$b['kode']] = $b;
        }
        ?>
        <?php if ($jadwalDosen): ?>
        <label for="pengajuan_dosen_id">Pilih Kegiatan Praktikum</label>
        <select id="pengajuan_dosen_id" name="pengajuan_dosen_id" required>
            <option value="">-- Pilih praktikum --</option>
            <?php foreach ($jadwalDosen as $jd):
                $bInfo = $barangMap[$jd['kode']] ?? null;
                $namaBahanLengkap = $bInfo ? ($bInfo['nama'] . ' - ' . $bInfo['spesifikasi'] . ' (' . $bInfo['satuan'] . ')') : $jd['nama'];
            ?>
            <option value="<?= e($jd['id']) ?>"
                data-kode="<?= e($jd['kode']) ?>"
                data-nama="<?= e($namaBahanLengkap) ?>"
                data-kelas="<?= e($jd['kelas']) ?>"
                data-sub="<?= e($jd['sub_praktikum']) ?>"
                data-jatah="<?= e($jd['ukuran_per_mhs'] ?? 10) ?>"
                data-satuan="<?= e($jd['satuan']) ?>">
                <?= e($jd['sub_praktikum'] . ' | Kelas ' . $jd['kelas'] . ' | Bahan: ' . $jd['nama'] . ' (Jatah Standar: ' . ($jd['ukuran_per_mhs'] ?? '-') . ' ' . $jd['satuan'] . '/mhs)') ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <input type="hidden" id="kode" name="kode" value="">
        <label for="nama_bahan_tampil">Nama Bahan Praktikum</label>
        <input id="nama_bahan_tampil" type="text" readonly style="background:#f1f5f9;cursor:not-allowed;font-weight:600" placeholder="Otomatis terisi sesuai kegiatan praktikum yang dipilih" required>

        <div class="grid3">
            <div>
                <label for="total_jatah">Batas Jatah Standar per Mahasiswa</label>
                <input id="total_jatah" name="total_jatah" type="number" readonly style="background:#f1f5f9;cursor:not-allowed" placeholder="Otomatis dari praktikum" required>
            </div>
            <div>
                <label for="jumlah">Jumlah yang Diambil (Bisa Over-Limit jika gagal potong)</label>
                <input id="jumlah" name="jumlah" type="number" min="1" placeholder="Masukkan jumlah diambil" required>
            </div>
            <div>
                <label for="sisa_tampil">Status Sisa / Jatah Minus (Over-Limit)</label>
                <input id="sisa_tampil" type="text" readonly style="background:#f1f5f9;cursor:not-allowed;font-weight:700" placeholder="Status jatah">
            </div>
        </div>

        <div id="box_overlimit" style="display:none;margin:4px 0 14px;padding:12px 14px;background:var(--bad-bg);border:1px solid #FDA29B;border-radius:10px">
            <div style="font-weight:700;color:var(--bad);margin-bottom:8px">Pemberitahuan Over-Limit / Bahan Tambahan Akibat Benda Kerja Gagal:</div>
            <div style="margin-bottom:8px">
                <label for="jenis_kompensasi" style="color:var(--bad);font-weight:600;font-size:13px">Skema Kompensasi Kegagalan (Pilih salah satu):</label>
                <select id="jenis_kompensasi" name="jenis_kompensasi" style="background:#fff">
                    <option value="pengurangan_nilai">Pengurangan Nilai Praktikum (-5 Poin Praktikum per unit over-limit)</option>
                    <option value="poin_pelanggaran">Poin Pelanggaran / Jam Kompensasi Lab (2 Jam kompensasi per unit over-limit)</option>
                    <option value="denda_finansial">Penggantian Biaya Bahan / Denda Finansial (Rp <?= number_format($tarifDenda, 0, ',', '.') ?>/satuan)</option>
                </select>
            </div>
            <label for="alasan_overlimit" style="color:var(--bad);font-weight:600;font-size:13px">Keterangan / Alasan Kegagalan Benda Kerja:</label>
            <input id="alasan_overlimit" name="alasan_overlimit" placeholder="Contoh: Benda kerja gagal bubut / patah saat pengerjaan (bersedia kompensasi)" style="margin-bottom:4px;background:#fff">
            <small style="color:var(--bad);display:block;font-size:12px">Sesuai notulensi laboratorium: pengambilan melebihi jatah standar dicatat sebagai <strong>Jatah Minus</strong> dan dikenakan kompensasi pengurangan nilai, jam kerja lab, atau denda penggantian bahan.</small>
        </div>

        <label style="display:flex;align-items:center;gap:8px;margin:8px 0 14px;cursor:pointer;font-weight:600;color:var(--ink)">
            <input type="checkbox" name="pernyataan_individu" value="1" required style="width:18px;height:18px;margin:0">
            <span>Saya menyatakan pengambilan bahan ini dilakukan oleh saya sendiri (<strong><?= e($_SESSION['user']['nama']) ?></strong>) menggunakan akun individu dan <strong>tidak diwakilkan</strong> kepada orang lain.</span>
        </label>

        <p id="info_ambil" class="calc">Pilih praktikum di atas untuk mengisi batas jatah standar, lalu tentukan jumlah yang diambil.</p>
        <button class="btn" type="submit">Kirim Permintaan Pengambilan Individu</button>
    </form>
    <h2>Riwayat Pengambilan Bahan Praktikum Individu Saya</h2>
    <?php mhs_table($milik, false, true); ?>
    <script>
    const selPd = document.getElementById('pengajuan_dosen_id');
    const inKode = document.getElementById('kode');
    const inNamaBahan = document.getElementById('nama_bahan_tampil');
    const inJatah = document.getElementById('total_jatah');
    const inJml = document.getElementById('jumlah');
    const outSisa = document.getElementById('sisa_tampil');
    const inKelas = document.getElementById('kelas');
    const inSub = document.getElementById('sub_praktikum');
    const infoAmbil = document.getElementById('info_ambil');
    const boxOver = document.getElementById('box_overlimit');
    const TARIF_DENDA = <?= $tarifDenda ?>;
    const IZINKAN_OVER = <?= $izinkanOverLimit ? 'true' : 'false' ?>;

    function syncAmbil() {
        const jatah = +inJatah.value || 0;
        const ambil = +inJml.value || 0;
        let satuan = '';
        if (selPd && selPd.selectedOptions && selPd.selectedOptions[0]) {
            satuan = selPd.selectedOptions[0].dataset.satuan || '';
        }

        if (jatah <= 0) {
            outSisa.value = '';
            outSisa.style.color = '';
            if (boxOver) boxOver.style.display = 'none';
            infoAmbil.textContent = 'Pilih kegiatan praktikum untuk memuat bahan dan batas jatah secara otomatis.';
            return;
        }

        if (ambil <= 0) {
            outSisa.value = jatah + (satuan ? ' ' + satuan : '');
            outSisa.style.color = '';
            if (boxOver) boxOver.style.display = 'none';
            infoAmbil.textContent = 'Batas jatah individu: ' + jatah + ' ' + satuan + '. Silakan masukkan jumlah yang diambil.';
            return;
        }

        const sisa = jatah - ambil;
        if (sisa < 0) {
            const minus = Math.abs(sisa);
            const denda = minus * TARIF_DENDA;
            outSisa.value = 'Minus -' + minus + ' ' + satuan + ' (Over-Limit)';
            outSisa.style.color = '#dc2626';
            if (boxOver) boxOver.style.display = 'block';
            infoAmbil.textContent = `Over-Limit (Melebihi Jatah): Anda mengambil ${ambil} ${satuan} dari jatah ${jatah} ${satuan}. Tercatat Jatah Minus -${minus} ${satuan} (Dikenakan skema kompensasi: pengurangan -${minus * 5} poin nilai, ${minus * 2} jam kerja lab, atau denda penggantian Rp ${denda.toLocaleString('id-ID')}).`;
        } else if (sisa === 0) {
            outSisa.value = '0 (Tepat Jatah / Lunas)';
            outSisa.style.color = '#16a34a';
            if (boxOver) boxOver.style.display = 'none';
            infoAmbil.textContent = 'Pengambilan penuh sesuai jatah: seluruh jatah (' + ambil + ' ' + satuan + ') diambil sekaligus.';
        } else {
            outSisa.value = sisa + (satuan ? ' ' + satuan : '') + ' (Sisa Jatah)';
            outSisa.style.color = '#d97706';
            if (boxOver) boxOver.style.display = 'none';
            infoAmbil.textContent = 'Pengambilan sebagian: diambil ' + ambil + ' ' + satuan + ', sisa ' + sisa + ' ' + satuan + ' dapat diambil pada sesi berikutnya.';
        }
    }

    if (selPd) {
        selPd.addEventListener('change', () => {
            const opt = selPd.selectedOptions[0];
            if (opt && opt.value) {
                if (inKode) inKode.value = opt.dataset.kode || '';
                if (inNamaBahan) inNamaBahan.value = opt.dataset.nama || '';
                if (inKelas) inKelas.value = opt.dataset.kelas || '';
                if (inSub) inSub.value = opt.dataset.sub || '';
                const jatah = opt.dataset.jatah || 10;
                inJatah.value = jatah;
                if (!IZINKAN_OVER) inJml.max = jatah; else inJml.removeAttribute('max');
                syncAmbil();
            } else {
                if (inKode) inKode.value = '';
                if (inNamaBahan) inNamaBahan.value = '';
                if (inKelas) inKelas.value = '';
                if (inSub) inSub.value = '';
                if (inJatah) inJatah.value = '';
                if (inJml) inJml.value = '';
                const inAlasan = document.getElementById('alasan_overlimit');
                if (inAlasan) inAlasan.value = '';
                if (boxOver) boxOver.style.display = 'none';
                syncAmbil();
            }
        });
    }

    if (inJml) inJml.addEventListener('input', syncAmbil);
    if (inJatah) inJatah.addEventListener('input', syncAmbil);
    syncAmbil();
    </script>
    <?php
    require __DIR__ . '/partials/footer.php';
}

// Teknisi Point 2 & 4 + PLP:
// - Teknisi hanya bisa approval untuk pengajuan mahasiswa (dilengkapi fitur approval sekaligus untuk 1 praktikum)
// - PLP bisa meninjau pengajuan Dosen (sekaligus set stok minimum) dan konfirmasi mahasiswa
// ---- Helper filter halaman Tinjau ----
function tinjau_filter($rows, $f) {
    return array_values(array_filter($rows, function ($r) use ($f) {
        if ($f['status'] !== '' && ($r['status'] ?? '') !== $f['status']) return false;
        if ($f['kelas'] !== '' && ($r['kelas'] ?? '') !== $f['kelas']) return false;
        if ($f['sub'] !== '' && ($r['sub_praktikum'] ?? '') !== $f['sub']) return false;
        if ($f['q'] !== '') {
            $hay = implode(' ', [$r['oleh'] ?? '', $r['oleh_email'] ?? '', $r['nama'] ?? '', $r['kode'] ?? '', $r['kelas'] ?? '', $r['sub_praktikum'] ?? '']);
            if (mb_stripos($hay, $f['q']) === false) return false;
        }
        return true;
    }));
}

function tinjau_opsi($rows, $key) {
    $o = [];
    foreach ($rows as $r) {
        $v = trim((string)($r[$key] ?? ''));
        if ($v !== '') $o[$v] = $v;
    }
    natcasesort($o);
    return array_values($o);
}

function tinjau_filter_form($tab, $f, $kelasList, $subList, $withStatus, $withSub, $withQ, $statusDefault) {
    $aktif = ($withStatus && $f['status'] !== $statusDefault) || $f['kelas'] !== '' || $f['sub'] !== '' || $f['q'] !== '';
    ?>
    <form class="filter" method="get" action="">
        <input type="hidden" name="fitur" value="tinjau">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <?php if ($withStatus): ?>
        <div>
            <label for="f_status">Status</label>
            <select id="f_status" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <?php foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $v => $lb): ?>
                <option value="<?= e($v) ?>"<?= $f['status'] === $v ? ' selected' : '' ?>><?= e($lb) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label for="f_kelas">Kelas</label>
            <select id="f_kelas" name="kelas" onchange="this.form.submit()">
                <option value="">Semua kelas</option>
                <?php foreach ($kelasList as $o): ?>
                <option value="<?= e($o) ?>"<?= $f['kelas'] === $o ? ' selected' : '' ?>><?= e($o) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($withSub): ?>
        <div>
            <label for="f_sub">Sub Praktikum</label>
            <select id="f_sub" name="sub" onchange="this.form.submit()">
                <option value="">Semua sub praktikum</option>
                <?php foreach ($subList as $o): ?>
                <option value="<?= e($o) ?>"<?= $f['sub'] === $o ? ' selected' : '' ?>><?= e($o) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <?php if ($withQ): ?>
        <div>
            <label for="f_q">Cari</label>
            <input id="f_q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Nama, bahan, kode...">
        </div>
        <?php endif; ?>
        <button class="btn" type="submit">Terapkan</button>
        <?php if ($aktif): ?><a class="reset" href="?fitur=tinjau&amp;tab=<?= e($tab) ?>">Reset filter</a><?php endif; ?>
    </form>
    <?php
}

function fitur_tinjau() {
    $role = norm_role($_SESSION['user']['role']);

    // Tab & filter (dibaca dari URL; form POST tanpa action tetap membawa query ini)
    $tabIds = $role === 'plp' ? ['dosen', 'rekap', 'mhs'] : ['rekap', 'mhs'];
    $tab = in_array($_GET['tab'] ?? '', $tabIds, true) ? $_GET['tab'] : ($role === 'plp' ? 'dosen' : 'mhs');
    $statusDefault = 'menunggu';
    $st = array_key_exists('status', $_GET) ? trim((string)$_GET['status']) : $statusDefault;
    if (!in_array($st, ['', 'menunggu', 'disetujui', 'ditolak'], true)) $st = '';
    $f = [
        'status' => $st,
        'kelas' => trim((string)($_GET['kelas'] ?? '')),
        'sub' => trim((string)($_GET['sub'] ?? '')),
        'q' => trim((string)($_GET['q'] ?? '')),
    ];
    $keep = ['fitur' => 'tinjau', 'tab' => $tab];
    if (array_key_exists('status', $_GET)) $keep['status'] = $st;
    foreach (['kelas', 'sub', 'q'] as $k) if ($f[$k] !== '') $keep[$k] = $f[$k];
    $redirect = role_url() . '?' . http_build_query($keep); // kembali ke tab & filter yang sama

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $d = db();
        $oleh = $_SESSION['user']['nama'] . ' (' . (ROLES[$role] ?? $role) . ')';

        // Approval / penolakan banyak sekaligus lewat checkbox (konfirmasi mahasiswa)
        $bulk = $_POST['aksi_bulk'] ?? '';
        if (in_array($bulk, ['setujui_terpilih', 'tolak_terpilih'], true)) {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])))));
            if (!$ids) go($redirect, 'Pilih minimal satu konfirmasi mahasiswa terlebih dahulu.', 'warn');
            $setuju = $bulk === 'setujui_terpilih';
            $cat = trim($_POST['catatan'] ?? '');
            if ($cat === '') $cat = $setuju ? 'Disetujui bersama (pilih banyak)' : 'Ditolak bersama (pilih banyak)';
            $jumlah = 0;
            foreach ($d['permintaan'] as &$pm) {
                if ($pm['status'] === 'menunggu' && in_array((int)$pm['id'], $ids, true)) {
                    $pm['status'] = $setuju ? 'disetujui' : 'ditolak';
                    $pm['catatan'] = $cat;
                    $pm['disetujui_oleh'] = $oleh;
                    $jumlah++;
                }
            }
            unset($pm);
            if ($jumlah === 0) go($redirect, 'Tidak ada konfirmasi yang berstatus menunggu pada pilihan Anda.', 'warn');
            save($d);
            go($redirect, ($setuju ? "Berhasil menyetujui $jumlah" : "Berhasil menolak $jumlah") . ' konfirmasi mahasiswa.');
        }

        // Approval sekaligus untuk 1 praktikum (tanggal + kelas + sub praktikum)
        if ($bulk === 'setujui_praktikum') {
            $tgl = $_POST['tanggal'] ?? '';
            $kls = $_POST['kelas'] ?? '';
            $sub = $_POST['sub_praktikum'] ?? '';
            $cat = trim($_POST['catatan'] ?? '');
            $countApproved = 0;
            foreach ($d['permintaan'] as &$pm) {
                if ($pm['status'] === 'menunggu' && ($pm['tanggal'] ?? '') === $tgl && ($pm['kelas'] ?? '') === $kls && ($pm['sub_praktikum'] ?? '') === $sub) {
                    $pm['status'] = 'disetujui';
                    $pm['catatan'] = $cat !== '' ? $cat : 'Disetujui sekaligus (1 praktikum)';
                    $pm['disetujui_oleh'] = $oleh;
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
        $d[$tipe][$n]['disetujui_oleh'] = $oleh;

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

    // Hitung jumlah yang masih menunggu untuk badge tab
    $cDosen = count(array_filter($d['pengajuan_dosen'], function ($r) { return $r['status'] === 'menunggu'; }));
    $cMhs = count(array_filter($d['permintaan'], function ($r) { return $r['status'] === 'menunggu'; }));
    $grup = [];
    foreach ($d['permintaan'] as $r) {
        if ($r['status'] === 'menunggu') $grup[($r['tanggal'] ?? '') . '|' . ($r['kelas'] ?? '') . '|' . ($r['sub_praktikum'] ?? '')] = true;
    }
    $cRekap = count($grup);
    $tabs = ['dosen' => ['Pengajuan Dosen', $cDosen], 'rekap' => ['Rekap Praktikum', $cRekap], 'mhs' => ['Konfirmasi Mahasiswa', $cMhs]];

    require __DIR__ . '/partials/header.php';
    ?>
    <nav class="tabs" aria-label="Bagian tinjauan">
    <?php foreach ($tabs as $id => [$label, $cnt]): if (!in_array($id, $tabIds, true)) continue; ?>
        <a href="?fitur=tinjau&amp;tab=<?= e($id) ?>"<?= $id === $tab ? ' class="on" aria-current="page"' : '' ?>><?= e($label) ?><?php if ($cnt > 0): ?><span class="badge" title="<?= e($cnt) ?> menunggu"><?= e($cnt) ?></span><?php endif; ?></a>
    <?php endforeach; ?>
    </nav>
    <?php

    if ($tab === 'dosen') {
        $semua = array_reverse($d['pengajuan_dosen']);
        tinjau_filter_form('dosen', $f, tinjau_opsi($semua, 'kelas'), [], true, false, true, $statusDefault);
        $rows = tinjau_filter($semua, $f);
        echo '<p class="sum">Menampilkan ' . count($rows) . ' dari ' . count($semua) . ' pengajuan dosen.</p>';
        if (!$rows) {
            echo '<p class="empty">' . ($semua ? 'Tidak ada data yang cocok dengan filter. Coba pilih &quot;Semua status&quot;.' : 'Belum ada pengajuan bahan semester dari dosen.') . '</p>';
        } else {
            dosen_table($rows, true);
        }
    } elseif ($tab === 'rekap') {
        $semua = $d['permintaan'];
        $fRekap = ['status' => '', 'kelas' => $f['kelas'], 'sub' => $f['sub'], 'q' => ''];
        tinjau_filter_form('rekap', $f, tinjau_opsi($semua, 'kelas'), tinjau_opsi($semua, 'sub_praktikum'), false, true, false, '');
        $rows = tinjau_filter($semua, $fRekap);
        if (!$rows) {
            echo '<p class="empty">' . ($semua ? 'Tidak ada praktikum yang cocok dengan filter.' : 'Belum ada konfirmasi praktikum dari mahasiswa.') . '</p>';
        } else {
            render_rekap_kehadiran_praktikum($rows, true);
        }
    } else {
        $semua = array_reverse($d['permintaan']);
        tinjau_filter_form('mhs', $f, tinjau_opsi($semua, 'kelas'), tinjau_opsi($semua, 'sub_praktikum'), true, true, true, $statusDefault);
        $rows = tinjau_filter($semua, $f);
        $menunggu = count(array_filter($rows, function ($r) { return $r['status'] === 'menunggu'; }));
        echo '<h2>Daftar Konfirmasi Praktikum &amp; Pengambilan Bahan (Mahasiswa)</h2>';
        echo '<p class="sum">Menampilkan ' . count($rows) . ' dari ' . count($semua) . ' konfirmasi, ' . $menunggu . ' menunggu persetujuan.</p>';
        if (!$rows) {
            echo '<p class="empty">' . ($semua ? 'Tidak ada data yang cocok dengan filter. Coba pilih &quot;Semua status&quot;.' : 'Belum ada konfirmasi praktikum & pengambilan bahan dari mahasiswa.') . '</p>';
        } else {
            if ($menunggu > 0): ?>
            <form id="bulk" class="bulkbar" method="post" action="">
                <?= field_csrf() ?>
                <label class="pilihsemua"><input type="checkbox" class="cekall"> Pilih semua (<?= e($menunggu) ?> menunggu)</label>
                <strong><span id="jmlpilih">0</span> dipilih</strong>
                <input name="catatan" placeholder="Catatan (opsional)" aria-label="Catatan untuk semua yang dipilih" style="width:200px;padding:7px 10px;border:1px solid #AAB4C3;border-radius:6px">
                <button class="btn" type="submit" name="aksi_bulk" value="setujui_terpilih" disabled>Setujui Terpilih</button>
                <button class="btn sec" type="submit" name="aksi_bulk" value="tolak_terpilih" disabled onclick="return confirm('Tolak semua konfirmasi yang dipilih?')">Tolak Terpilih</button>
                <small class="muted">Tips: klik satu kotak, lalu tahan Shift dan klik kotak lain untuk memilih rentang.</small>
            </form>
            <?php endif;
            mhs_table($rows, true, false, $menunggu > 0);
            if ($menunggu > 0): ?>
            <script>
            (function () {
                var boxes = [].slice.call(document.querySelectorAll('.pilih'));
                var alls = [].slice.call(document.querySelectorAll('.cekall'));
                var cnt = document.getElementById('jmlpilih');
                var btns = [].slice.call(document.querySelectorAll('#bulk button[name=aksi_bulk]'));
                var last = null;
                function sync() {
                    var n = boxes.filter(function (b) { return b.checked; }).length;
                    cnt.textContent = n;
                    btns.forEach(function (b) { b.disabled = n === 0; });
                    alls.forEach(function (a) { a.checked = n > 0 && n === boxes.length; a.indeterminate = n > 0 && n < boxes.length; });
                }
                alls.forEach(function (a) {
                    a.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = a.checked; }); sync(); });
                });
                boxes.forEach(function (b, i) {
                    b.addEventListener('click', function (ev) {
                        if (ev.shiftKey && last !== null) {
                            var s = Math.min(last, i), t = Math.max(last, i);
                            for (var k = s; k <= t; k++) boxes[k].checked = b.checked;
                        }
                        last = i; sync();
                    });
                });
                sync();
            })();
            </script>
            <?php endif;
        }
    }
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
    $tab = $_GET['tab'] ?? $_GET['amp;tab'] ?? 'dosen';
    if (!in_array($tab, ['dosen', 'pengadaan', 'praktikum', 'pembelian'], true)) {
        $tab = 'dosen';
    }

    $rowsDosen = array_filter($d['pengajuan_dosen'], fn($r) => $st === '' || $r['status'] === $st);
    $rowsPengadaan = array_filter($d['pengajuan'], fn($r) => $st === '' || $r['status'] === $st);
    $rowsPembelian = array_filter($d['pembelian'], fn($r) => $st === '' || ($r['status'] ?? '') === $st);

    $rekapGrup = [];
    foreach ($d['permintaan'] as $r) {
        if ($r['status'] === 'ditolak') continue;
        $rekapGrup[($r['tanggal'] ?? '') . '|' . ($r['kelas'] ?? '-') . '|' . ($r['sub_praktikum'] ?? '-')] = true;
    }
    $cPraktikum = count($rekapGrup);

    $tabs = [
        'dosen' => ['Pengajuan Bahan Semester (Dosen ke PLP)', count($d['pengajuan_dosen'])],
        'pengadaan' => ['Pengajuan Kebutuhan Pengadaan (PLP ke Kepala Lab)', count($d['pengajuan'])],
        'praktikum' => ['Rekap Jumlah Mahasiswa Praktikum per Hari', $cPraktikum],
        'pembelian' => ['Permintaan Pembelian', count($d['pembelian'])],
    ];

    $buildUrl = function($tKey, $statusVal = '') {
        $params = [];
        if (isset($_GET['fitur'])) $params['fitur'] = $_GET['fitur'];
        $params['tab'] = $tKey;
        if ($statusVal !== '') $params['status'] = $statusVal;
        return '?' . http_build_query($params);
    };

    $resetUrl = $buildUrl($tab, '');

    require __DIR__ . '/partials/header.php';
    ?>
    <div class="cards">
        <div><strong><?= count($d['pengajuan_dosen']) ?></strong><span>Pengajuan Dosen</span></div>
        <div><strong><?= count($d['pengajuan']) ?></strong><span>Pengadaan PLP</span></div>
        <div><strong><?= $cPraktikum ?></strong><span>Praktikum Harian</span></div>
        <div><strong><?= count($d['pembelian']) ?></strong><span>Permintaan Pembelian</span></div>
    </div>

    <nav class="tabs" aria-label="Pilihan Rekap">
    <?php foreach ($tabs as $tKey => [$tLabel, $tCount]): ?>
        <a href="<?= e($buildUrl($tKey)) ?>"<?= $tKey === $tab ? ' class="on" aria-current="page"' : '' ?>>
            <?= e($tLabel) ?>
            <span class="badge" title="<?= e($tCount) ?> data"><?= e($tCount) ?></span>
        </a>
    <?php endforeach; ?>
    </nav>
    <?php

    if ($tab === 'dosen') {
        ?>
        <form class="filter" method="get">
            <?php if (isset($_GET['fitur'])): ?><input type="hidden" name="fitur" value="<?= e($_GET['fitur']) ?>"><?php endif; ?>
            <input type="hidden" name="tab" value="dosen">
            <div>
                <label for="status">Status Pengajuan Dosen</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    <?php foreach (['menunggu', 'disetujui', 'ditolak'] as $s): ?>
                    <option value="<?= $s ?>"<?= $st === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn" type="submit">Terapkan</button>
            <a class="reset" href="<?= e($resetUrl) ?>">Reset</a>
        </form>
        <h2>Pengajuan Bahan Semester (Dosen ke PLP)</h2>
        <?php
        if (!$rowsDosen) {
            echo '<p class="empty">' . ($st !== '' ? 'Tidak ada data pengajuan dosen dengan status &quot;' . e($st) . '&quot;.' : 'Belum ada pengajuan bahan semester dari dosen.') . '</p>';
        } else {
            dosen_table(array_reverse($rowsDosen));
        }
    } elseif ($tab === 'pengadaan') {
        ?>
        <form class="filter" method="get">
            <?php if (isset($_GET['fitur'])): ?><input type="hidden" name="fitur" value="<?= e($_GET['fitur']) ?>"><?php endif; ?>
            <input type="hidden" name="tab" value="pengadaan">
            <div>
                <label for="status">Status Pengadaan</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    <?php foreach (['menunggu', 'disetujui', 'ditolak', 'dipesan'] as $s): ?>
                    <option value="<?= $s ?>"<?= $st === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn" type="submit">Terapkan</button>
            <a class="reset" href="<?= e($resetUrl) ?>">Reset</a>
        </form>
        <h2>Pengajuan Kebutuhan Pengadaan (PLP ke Kepala Lab)</h2>
        <?php
        if (!$rowsPengadaan) {
            echo '<p class="empty">' . ($st !== '' ? 'Tidak ada data pengadaan dengan status &quot;' . e($st) . '&quot;.' : 'Belum ada pengajuan kebutuhan pengadaan.') . '</p>';
        } else {
            req_table(array_reverse($rowsPengadaan));
        }
    } elseif ($tab === 'praktikum') {
        if (empty($d['permintaan'])) {
            echo '<h2>Rekap Jumlah Mahasiswa Praktikum per Hari</h2>';
            echo '<p class="empty">Belum ada data konfirmasi praktikum mahasiswa.</p>';
        } else {
            render_rekap_kehadiran_praktikum($d['permintaan']);
        }
    } elseif ($tab === 'pembelian') {
        ?>
        <form class="filter" method="get">
            <?php if (isset($_GET['fitur'])): ?><input type="hidden" name="fitur" value="<?= e($_GET['fitur']) ?>"><?php endif; ?>
            <input type="hidden" name="tab" value="pembelian">
            <div>
                <label for="status">Status Pembelian</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    <?php foreach (['menunggu', 'disetujui', 'dipesan', 'selesai', 'ditolak'] as $s): ?>
                    <option value="<?= $s ?>"<?= $st === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn" type="submit">Terapkan</button>
            <a class="reset" href="<?= e($resetUrl) ?>">Reset</a>
        </form>
        <h2>Permintaan Pembelian</h2>
        <?php if (!$rowsPembelian): ?>
            <p class="empty"><?= $st !== '' ? 'Tidak ada permintaan pembelian dengan status &quot;' . e($st) . '&quot;.' : 'Belum ada permintaan pembelian.' ?></p>
        <?php else: ?>
        <div class="tablewrap">
            <table>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
                <?php foreach (array_reverse($rowsPembelian) as $b): ?>
                <tr>
                    <td><?= e($b['id']) ?></td>
                    <td><?= e($b['tanggal']) ?></td>
                    <td><?= e($b['nama']) ?></td>
                    <td><?= e($b['jumlah'] . ' ' . $b['satuan']) ?></td>
                    <td><?= chip($b['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif;
    }

    require __DIR__ . '/partials/footer.php';
}