<?php
declare(strict_types=1);

namespace App\Auth;

require_once __DIR__ . '/../Database/Database.php';
require_once __DIR__ . '/../Mail/MailService.php';

use App\Database\Database;
use App\Mail\MailService;
use PDO;

class AuthService
{
    /** Séries de bac acceptées à l'inscription */
    public const SERIES = ['A1', 'A2', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2', 'G3'];

    /** Mentions au bac acceptées */
    public const MENTIONS = ['Passable', 'Assez Bien', 'Bien', 'Très Bien'];

    /**
     * Inscription classique par nom + email + mot de passe + série.
     * @throws \RuntimeException si l'email existe déjà ou données invalides
     */
    public static function register(string $nomComplet, string $email, string $password, string $serie): int
    {
        $nomComplet = trim($nomComplet);
        $email = trim(strtolower($email));
        $serie = trim(strtoupper($serie));

        if ($nomComplet === '' || mb_strlen($nomComplet) < 2) {
            throw new \RuntimeException('Merci de renseigner ton nom complet.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException("L'adresse email n'est pas valide.");
        }
        if (strlen($password) < 8) {
            throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
        }
        if (!in_array($serie, self::SERIES, true)) {
            throw new \RuntimeException('Merci de sélectionner une série valide.');
        }

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('Un compte existe déjà avec cet email. Connecte-toi plutôt.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (nom_complet, email, password_hash, auth_provider, email_verified, serie) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nomComplet, $email, $hash, 'email', 0, $serie]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Complète le profil après paiement : mention + téléphone.
     * @throws \RuntimeException si données invalides
     */
    public static function completeProfile(int $userId, string $mention, string $telephone): void
    {
        $mention = trim($mention);
        $telephone = trim($telephone);

        if (!in_array($mention, self::MENTIONS, true)) {
            throw new \RuntimeException('Merci de sélectionner une mention valide.');
        }
        if (!preg_match('/^[0-9+\s]{8,20}$/', $telephone)) {
            throw new \RuntimeException('Merci de renseigner un numéro de téléphone valide (8 à 20 chiffres).');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET mention = ?, numero_telephone = ? WHERE id = ?');
        $stmt->execute([$mention, $telephone, $userId]);
    }

    /**
     * Génère un code d'accompagnement unique, appelé une seule fois, juste après un paiement réussi.
     */
    public static function generateCodeAccompagnement(): string
    {
        $pdo = Database::getConnection();
        do {
            $code = 'APB-' . strtoupper(bin2hex(random_bytes(3))); // ex: APB-9F3C1A
            $stmt = $pdo->prepare('SELECT id FROM users WHERE code_accompagnement = ?');
            $stmt->execute([$code]);
            $existe = $stmt->fetch();
        } while ($existe);

        return $code;
    }

    /**
     * Connexion par email + mot de passe.
     * Vérifie d'abord si l'utilisateur existe en base, puis compare le mot de passe.
     * @throws \RuntimeException si identifiants invalides ou compte inexistant
     */
    public static function login(string $email, string $password): array
    {
        $email = trim(strtolower($email));

        if ($email === '' || $password === '') {
            throw new \RuntimeException('Merci de renseigner ton email et ton mot de passe.');
        }

        $pdo = Database::getConnection();

        // 1. On cherche d'abord si un compte existe avec cet email
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Aucun compte trouvé en base → refus immédiat
        if (!$user) {
            throw new \RuntimeException('Aucun compte trouvé avec cet email.');
        }

        // Compte créé uniquement via Google (pas de mot de passe défini)
        if (empty($user['password_hash'])) {
            throw new \RuntimeException('Ce compte a été créé avec Google. Connecte-toi via le bouton "Continuer avec Google".');
        }

        // 2. On vérifie le mot de passe uniquement si le compte existe
        if (!password_verify($password, $user['password_hash'])) {
            throw new \RuntimeException('Mot de passe incorrect.');
        }

        return $user;
    }

    /** Durée de validité du code de réinitialisation, en minutes */
    public const RESET_CODE_TTL_MINUTES = 15;

    /**
     * Étape 1 : demande de réinitialisation. Génère un code à 6 chiffres,
     * le stocke avec une expiration, et l'envoie par email.
     * @throws \RuntimeException si l'email n'existe pas ou est un compte Google
     */
    public static function requestPasswordReset(string $email): void
    {
        $email = trim(strtolower($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException("L'adresse email n'est pas valide.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        error_log('RESET DEBUG — utilisateur trouvé : ' . ($user ? 'OUI (id=' . $user['id'] . ')' : 'NON'));

        if (!$user) {
            throw new \RuntimeException('Aucun compte trouvé avec cet email.');
        }
        if (empty($user['password_hash'])) {
            throw new \RuntimeException('Ce compte a été créé avec Google, il n\'y a pas de mot de passe à réinitialiser.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 900);

        $update = $pdo->prepare('UPDATE users SET reset_code = ?, reset_code_expires_at = ? WHERE id = ?');
        $update->execute([$code, $expiresAt, $user['id']]);

        error_log('RESET DEBUG — code enregistré en BDD : ' . $code);

        MailService::sendPasswordResetCode($user['email'], $user['nom_complet'] ?? '', $code);

        $_SESSION['pending_reset_user_id'] = $user['id'];
        $_SESSION['reset_last_sent'] = time();

        error_log('RESET DEBUG — session après écriture : ' . print_r($_SESSION, true) . ' | session_id=' . session_id());
    }

    /**
     * Étape 2 : vérifie le code saisi par l'utilisateur.
     * @throws \RuntimeException si le code est absent, expiré ou incorrect
     */
    public static function verifyResetCode(int $userId, string $code): bool
    {
        $code = trim($code);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT reset_code, reset_code_expires_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
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
     * Étape 3 : applique le nouveau mot de passe après re-vérification du code.
     * @throws \RuntimeException si le mot de passe est invalide ou le code n'est plus valable
     */
    public static function resetPassword(int $userId, string $code, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        self::verifyResetCode($userId, $code);

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, reset_code = NULL, reset_code_expires_at = NULL WHERE id = ?');
        $stmt->execute([$hash, $userId]);
    }

    /**
     * Trouve un utilisateur Google existant ou le crée (auto-inscription via Google).
     */
    public static function findOrCreateGoogleUser(string $googleId, string $email): array
    {
        $pdo = Database::getConnection();
        $email = trim(strtolower($email));

        $stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ?');
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }

        // Si un compte email existe déjà avec cette adresse, on le relie à Google.
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing) {
            $stmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ?');
            $stmt->execute([$googleId, $existing['id']]);
            $existing['google_id'] = $googleId;
            return $existing;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (google_id, email, auth_provider, email_verified) VALUES (?, ?, "google", 1)'
        );
        $stmt->execute([$googleId, $email]);

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$pdo->lastInsertId()]);
        return $stmt->fetch();
    }

    public static function startSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /connexion.php');
            exit;
        }
    }

    public static function currentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}