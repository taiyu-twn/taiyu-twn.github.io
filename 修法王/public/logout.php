<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';

startSecureSession();
logout();
header('Location: /修法王/public/index.php');
exit;
