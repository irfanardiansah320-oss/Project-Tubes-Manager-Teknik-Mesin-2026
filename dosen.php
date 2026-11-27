<?php
require 'config.php';
guard_role('dosen');

$jab = current_jabatan();
$defaultFitur = $jab === 'kalab' ? 'approval_kalab' : ($jab === 'kajur' ? 'dashboard' : 'pengajuan_dosen');
$fitur = $_GET['fitur'] ?? $defaultFitur;

switch ($fitur) {
    case 'approval_kalab':
        if ($jab !== 'kalab') go('dosen.php', 'Fitur Approval Pengajuan hanya untuk Dosen dengan jabatan Kepala Lab.', 'bad');
        fitur_approval_kalab();
        break;
    case 'semua_pengajuan':
        if (!in_array($jab, ['kalab', 'kajur'], true)) go('dosen.php', 'Fitur ini khusus jabatan Kepala Lab / Ketua Jurusan.', 'bad');
        fitur_semua_pengajuan();
        break;
    case 'peringatan':
        if (!in_array($jab, ['kalab', 'kajur'], true)) go('dosen.php', 'Visibilitas stok hanya untuk PLP, Staf Lab, atau Pejabat Lab.', 'bad');
        fitur_peringatan();
        break;
    case 'dashboard':
        if ($jab !== 'kajur') go('dosen.php');
        fitur_dashboard();
        break;
    case 'stok':
        if ($jab !== 'kajur') go('dosen.php', 'Dosen biasa tidak diperlihatkan informasi jumlah ketersediaan stok bahan di laboratorium.', 'bad');
        fitur_stok();
        break;
    case 'rak':
        if ($jab !== 'kajur') go('dosen.php');
        fitur_rak();
        break;
    case 'rekap':
        if (!in_array($jab, ['kalab', 'kajur'], true)) go('dosen.php');
        fitur_rekap();
        break;
    default:
        fitur_pengajuan_dosen();
        break;
}
