<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Auth\AuthService;
use App\Database\Database;
use App\Mail\MailService;

if (AuthService::isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $user = AuthService::login($email, $password);

        AuthService::startSession($user);
        $redirect = $_SESSION['redirect_after_login']
        ?? (isset($_SESSION['formule_choisie']) ? '/paiement.php' : '/index.php');
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    } catch (\RuntimeException $e) {
        $erreur = $e->getMessage();
    }
}

$pageTitle = 'Connexion — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/index.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour
        </a>

        <p class="eyebrow eyebrow-center">Bon retour</p>
        <h1 class="auth-title">Connecte-toi à ton espace</h1>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php elseif (($_GET['inscription'] ?? null) === 'success'): ?>
            <p class="auth-succes">✅ Compte créé avec succès ! Connecte-toi ci-dessous.</p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autocomplete="email" placeholder="toi@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="password">Mot de passe</label>
            <div class="password-field">
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="password-toggle" id="passwordToggle" aria-label="Afficher le mot de passe">
                    <svg id="eyeIconVisible" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg id="eyeIconHidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-6.06M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-3.22 4.44M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>

            <label class="auth-remember">
                <input type="checkbox" id="rememberMe" name="remember_me">
                Se souvenir de moi
            </label>

            <p class="auth-switch" style="margin: -8px 0 4px; text-align:right;"><a href="/mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <p class="auth-switch">Pas encore de compte ? <a href="/inscription.php">Crée-en un</a></p>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('passwordToggle');
    const passwordInput = document.getElementById('password');
    const eyeVisible = document.getElementById('eyeIconVisible');
    const eyeHidden = document.getElementById('eyeIconHidden');

    toggle.addEventListener('click', () => {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        eyeVisible.style.display = isHidden ? 'none' : 'block';
        eyeHidden.style.display = isHidden ? 'block' : 'none';
        toggle.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>