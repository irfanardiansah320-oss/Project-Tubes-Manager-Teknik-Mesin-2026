<?php
require 'config.php';
guard('tinjau.php');
if (norm_role($_SESSION['user']['role']) === 'kalab') {
    fitur_approval_kalab();
} else {
    fitur_tinjau();
}
