<?php
require 'config.php';
guard('pengajuan.php');
if (norm_role($_SESSION['user']['role']) === 'dosen') {
    fitur_pengajuan_dosen();
} else {
    fitur_pengajuan();
}
