<?php
require 'config.php';
// Sesuai Notulensi: Akun Kajur digabung ke dalam entitas Dosen dengan atribut jabatan='kajur'
guard_role('dosen');
$fitur = $_GET['fitur'] ?? 'dashboard';
header('Location: dosen.php?fitur=' . urlencode($fitur));
exit;
