<?php
declare(strict_types=1);
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Configuration principale de l'application
 * Charge les variables d'environnement depuis .env
 */

// --- Chargement basique du .env (sans dépendance externe) ---
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value !== false && $value !== null ? $value : $default;
}

// --- Constantes de base ---
define('APP_NAME', 'APRESBAC');
define('APP_URL', env('APP_URL', 'http://localhost:8000'));
define('APP_ENV', env('APP_ENV', 'development'));

// --- Base de données ---
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'apresbac'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'DouDou@1234'));

// --- FedaPay ---
define('FEDAPAY_PUBLIC_KEY', env('FEDAPAY_PUBLIC_KEY', ''));
define('FEDAPAY_SECRET_KEY', env('FEDAPAY_SECRET_KEY', ''));
define('FEDAPAY_ENV', env('FEDAPAY_ENV', 'sandbox')); // sandbox | live

// --- Google OAuth ---
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', APP_URL . '/auth/google-callback.php'));

// --- Envoi d'emails (SMTP) ---
define('SMTP_HOST', env('SMTP_HOST', 'smtp.hostinger.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', 465));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'contact@orientasup.online'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', 'DouDou@1234'));
define('SMTP_SECURE', env('SMTP_SECURE', 'ssl')); // ssl | tls
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'contact@orientasup.online'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', APP_NAME));
define('SMTP_REPLY_TO', env('SMTP_REPLY_TO', 'contact@orientasup.online'));

// --- Sessions sécurisées ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

error_log('SESSION ID: ' . session_id() . ' | SAVE PATH: ' . session_save_path() . ' | writable: ' . (is_writable(session_save_path()) ? 'oui' : 'NON') . ' | contenu: ' . print_r($_SESSION, true));

// --- Erreurs (visibles en dev seulement) ---
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

define('ADMIN_EMAIL', 'elvisapovo04@gmail.com');
define('ADMIN_PASSWORD_HASH', '$2b$10$J7THcPWMJYeWFKOe1jj7e.eaSUpNVlG0Sr5rM1WnNgPsVF.TjMiVe'); // = "DouDou@1234""