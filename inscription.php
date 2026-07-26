<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Auth\AuthService;
use App\Mail\MailService;
use App\Database\Database;

// On mémorise la formule choisie AVANT toute redirection, pour ne jamais la perdre
if (isset($_GET['formule'])) {
    $_SESSION['formule_choisie'] = $_GET['formule'];
}

// Si déjà connecté : on va direct au paiement si une formule est en attente, sinon à l'accueil
if (AuthService::isLoggedIn()) {
    header('Location: ' . (isset($_SESSION['formule_choisie']) ? '/paiement.php' : '/index.php'));
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomComplet = trim($_POST['nom_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $serie = trim($_POST['serie'] ?? '');

    if ($password !== $passwordConfirm) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } else {
        try {
            $userId = AuthService::register($nomComplet, $email, $password, $serie);
            $pdo = Database::getConnection();

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            $update = $pdo->prepare('UPDATE users SET verification_code = ?, verification_code_expires_at = ? WHERE id = ?');
            $update->execute([$code, $expiresAt, $userId]);

            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $newUser = $stmt->fetch();

            MailService::sendVerificationCode($newUser['email'], $newUser['nom_complet'] ?? '', $code);

            $_SESSION['pending_verification_user_id'] = $userId;
            $_SESSION['verification_last_sent'] = time();

            header('Location: /verification-email.php');
            exit;
        } catch (\RuntimeException $e) {
            $erreur = $e->getMessage();
        }
    }
}

$pageTitle = 'Créer un compte — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/index.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour à l'accueil
        </a>

        <p class="eyebrow eyebrow-center">Bienvenue</p>
        <h1 class="auth-title">Crée ton compte</h1>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="nom_complet">Nom complet</label>
            <input type="text" id="nom_complet" name="nom_complet" required autocomplete="name" placeholder="Ton nom et prénom" value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>">

            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autocomplete="email" placeholder="toi@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="serie">Série du bac</label>
            <select id="serie" name="serie" required>
                <option value="">-- Choisis ta série --</option>
                <?php foreach (AuthService::SERIES as $s): ?>
                    <option value="<?= $s ?>" <?= (($_POST['serie'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>

            <label for="password">Mot de passe</label>
            <div class="password-field">
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="8 caractères minimum">
                <button type="button" class="password-toggle" data-target="password" aria-label="Afficher le mot de passe">
                    <svg class="eye-icon-visible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-icon-hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-6.06M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-3.22 4.44M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>

            <label for="password_confirm">Confirme le mot de passe</label>
            <div class="password-field">
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="••••••••">
                <button type="button" class="password-toggle" data-target="password_confirm" aria-label="Afficher le mot de passe">
                    <svg class="eye-icon-visible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg class="eye-icon-hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-6.06M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-3.22 4.44M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <p class="auth-switch">Déjà un compte ? <a href="/connexion.php">Connecte-toi</a></p>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.password-toggle').forEach((toggle) => {
        const input = document.getElementById(toggle.dataset.target);
        const eyeVisible = toggle.querySelector('.eye-icon-visible');
        const eyeHidden = toggle.querySelector('.eye-icon-hidden');

        toggle.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            eyeVisible.style.display = isHidden ? 'none' : 'block';
            eyeHidden.style.display = isHidden ? 'block' : 'none';
            toggle.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>