<?php
require 'config.php';
header('Location: ' . (empty($_SESSION['user']) ? 'login.php' : home_for($_SESSION['user']['role'])));
