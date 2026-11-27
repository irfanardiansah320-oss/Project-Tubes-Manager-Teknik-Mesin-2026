<?php
require 'config.php';
guard_role('admin');

$fitur = $_GET['fitur'] ?? 'admin_dashboard';
switch ($fitur) {
    case 'admin_users':
        fitur_admin_users();
        break;
    case 'admin_akademik':
        fitur_admin_akademik();
        break;
    case 'admin_aturan':
        fitur_admin_aturan();
        break;
    default:
        fitur_admin_dashboard();
        break;
}
