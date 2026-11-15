<?php
require 'config.php';
guard_role('kajur');

$fitur = $_GET['fitur'] ?? 'dashboard';
switch ($fitur) {
    case 'stok':
        fitur_stok();
        break;
    case 'rak':
        fitur_rak();
        break;
    case 'rekap':
        fitur_rekap();
        break;
    default:
        fitur_dashboard();
        break;
}

