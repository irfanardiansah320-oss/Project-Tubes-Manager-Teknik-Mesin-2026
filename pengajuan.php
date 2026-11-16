<?php
require 'config.php';
guard('pengajuan.php');
$role = norm_role($_SESSION['user']['role']);
if ($role === 'dosen') {
    fitur_pengajuan_dosen();
} elseif ($role === 'kalab') {
    fitur_approval_kalab();
} else {
    fitur_pengajuan();
}
