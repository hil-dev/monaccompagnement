<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth/GoogleAuth.php';

use App\Auth\GoogleAuth;

if (empty(GOOGLE_CLIENT_ID)) {
    http_response_code(500);
    die('La connexion Google n\'est pas encore configurée (GOOGLE_CLIENT_ID manquant dans .env).');
}

header('Location: ' . GoogleAuth::getAuthUrl());
exit;
