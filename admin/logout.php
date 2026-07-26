<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';

use App\Auth\AdminAuthService;

AdminAuthService::logout();
header('Location: /admin/login.php');
exit;