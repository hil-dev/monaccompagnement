<?php
declare(strict_types=1);

namespace App\Auth;

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Mail/MailService.php';

use App\Database\Database;
use App\Mail\MailService;

class AdminAuthService
{
    /** Durée de validité du code de réinitialisation, en minutes */
    public const RESET_CODE_TTL_MINUTES = 15;

    /**
     * Connexion admin, désormais basée sur la table `admins`
     * (et non plus sur les constantes ADMIN_EMAIL / ADMIN_PASSWORD_HASH).
     */
    public static function login(string $email, string $password): bool
    {
        $email = trim(strtolower($email));

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_email'] = $admin['email'];
        return true;
    }

    public static function isAdmin(): bool
    {
        return $_SESSION['is_admin'] ?? false;
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function logout(): void
    {
        unset($_SESSION['is_admin'], $_SESSION['admin_id'], $_SESSION['admin_email']);
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf'];
    }

    public static function checkCsrf(?string $token): bool
    {
        return !empty($token) && !empty($_SESSION['admin_csrf']) && hash_equals($_SESSION['admin_csrf'], $token);
    }

    /**
     * Étape 1 : demande de réinitialisation pour un admin.
     * @throws \RuntimeException si aucun admin n'a cet email
     */
    public static function requestPasswordReset(string $email): array
    {
        $email = trim(strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException("L'adresse email n'est pas valide.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, nom, email FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) {
            throw new \RuntimeException('Aucun compte administrateur trouvé avec cet email.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_CODE_TTL_MINUTES * 60);

        $update = $pdo->prepare('UPDATE admins SET reset_code = ?, reset_code_expires_at = ? WHERE id = ?');
        $update->execute([$code, $expiresAt, $admin['id']]);

        MailService::sendPasswordResetCode($admin['email'], $admin['nom'] ?? 'Admin', $code);

        return ['admin_id' => (int) $admin['id'], 'email' => $admin['email']];
    }

    /**
     * Étape 2 : vérifie le code saisi par l'admin.
     * @throws \RuntimeException si le code est absent, expiré ou incorrect
     */
    public static function verifyResetCode(int $adminId, string $code): bool
    {
        $code = trim($code);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT reset_code, reset_code_expires_at FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();

        if (!$row || empty($row['reset_code'])) {
            throw new \RuntimeException('Aucune demande de réinitialisation en cours. Recommence depuis le début.');
        }
        if (strtotime($row['reset_code_expires_at']) < time()) {
            throw new \RuntimeException('Ce code a expiré. Demande un nouveau code.');
        }
        if (!hash_equals((string) $row['reset_code'], $code)) {
            throw new \RuntimeException('Code incorrect.');
        }

        return true;
    }

    /**
     * Étape 3 : applique le nouveau mot de passe admin après re-vérification du code.
     * @throws \RuntimeException si le mot de passe est invalide ou le code n'est plus valable
     */
    public static function resetPassword(int $adminId, string $code, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        self::verifyResetCode($adminId, $code);

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE admins SET password_hash = ?, reset_code = NULL, reset_code_expires_at = NULL WHERE id = ?');
        $stmt->execute([$hash, $adminId]);
    }
}