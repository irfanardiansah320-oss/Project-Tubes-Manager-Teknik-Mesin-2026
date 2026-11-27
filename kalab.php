<?php
require 'config.php';
// Sesuai Notulensi: Akun Kalab digabung ke dalam entitas Dosen dengan atribut jabatan='kalab'
guard_role('dosen');
$fitur = $_GET['fitur'] ?? 'approval_kalab';
header('Location: dosen.php?fitur=' . urlencode($fitur));
exit;
