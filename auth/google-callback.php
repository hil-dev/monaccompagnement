<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Database/Database.php';
require_once __DIR__ . '/../../src/Auth/AuthService.php';
require_once __DIR__ . '/../../src/Auth/GoogleAuth.php';

use App\Auth\AuthService;
use App\Auth\GoogleAuth;

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state) {
    header('Location: /connexion.php');
    exit;
}

try {
    $profile = GoogleAuth::handleCallback($code, $state);
    $user = AuthService::findOrCreateGoogleUser($profile['sub'], $profile['email']);
    AuthService::startSession($user);

    $redirect = $_SESSION['redirect_after_login'] ?? '/profil.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
} catch (\RuntimeException $e) {
    error_log('Erreur Google OAuth : ' . $e->getMessage());
    $_SESSION['auth_error'] = 'La connexion avec Google a échoué. Réessaie ou utilise ton email.';
    header('Location: /connexion.php');
    exit;
}
