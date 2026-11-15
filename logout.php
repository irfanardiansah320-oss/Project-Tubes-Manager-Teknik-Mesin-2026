<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $_SESSION = []; session_destroy(); }
header('Location: login.php');
