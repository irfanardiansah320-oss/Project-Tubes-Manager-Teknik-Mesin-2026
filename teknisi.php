<?php
require 'config.php';
guard_role('teknisi');

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
    case 'masuk':
        fitur_masuk();
        break;
    case 'keluar':
        fitur_keluar();
        break;
    case 'tinjau':
        fitur_tinjau();
        break;
    case 'stockopname':
        fitur_stockopname();
        break;
    default:
        fitur_dashboard();
        break;
}
