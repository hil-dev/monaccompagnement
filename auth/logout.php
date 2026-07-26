<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/AuthService.php';

use App\Auth\AuthService;

AuthService::logout();
header('Location: /');
exit;
