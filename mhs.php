<?php
require 'config.php';
guard_role('mhs');

$fitur = $_GET['fitur'] ?? 'permintaan';
switch ($fitur) {
    default:
        fitur_konfirmasi_mhs();
        break;
}

