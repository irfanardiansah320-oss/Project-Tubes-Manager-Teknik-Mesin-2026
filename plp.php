<?php
require 'config.php';
guard_role('plp');

$fitur = $_GET['fitur'] ?? 'dashboard';
switch ($fitur) {
    case 'stok':
        fitur_stok();
        break;
    case 'rak':
        fitur_rak();
        break;
    case 'peringatan':
        fitur_peringatan();
        break;
    case 'tinjau':
        fitur_tinjau();
        break;
    case 'pembelian':
        fitur_pembelian();
        break;
    case 'rekap':
        fitur_rekap();
        break;
    default:
        fitur_dashboard();
        break;
}

