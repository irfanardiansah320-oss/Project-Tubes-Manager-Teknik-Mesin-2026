<?php
require 'config.php';
guard_role('kalab');

$fitur = $_GET['fitur'] ?? 'approval_kalab';
switch ($fitur) {
    case 'semua_pengajuan':
        fitur_semua_pengajuan();
        break;
    case 'peringatan':
        fitur_peringatan();
        break;
    default:
        fitur_approval_kalab();
        break;
}
