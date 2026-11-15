<?php
require 'config.php';
guard_role('kalab');

$fitur = $_GET['fitur'] ?? 'peringatan';
switch ($fitur) {
    case 'pengajuan':
        fitur_pengajuan();
        break;
    default:
        fitur_peringatan();
        break;
}

