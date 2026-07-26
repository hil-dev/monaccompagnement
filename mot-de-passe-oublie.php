<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Auth\AuthService;

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    try {
        AuthService::requestPasswordReset($email);
        header('Location: /reinitialiser-mot-de-passe.php');
        exit;
    } catch (\RuntimeException $e) {
        $erreur = $e->getMessage();
    }
}

$pageTitle = 'Mot de passe oublié — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/connexion.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour
        </a>

        <p class="eyebrow eyebrow-center">Mot de passe oublié</p>
        <h1 class="auth-title">Réinitialise ton mot de passe</h1>
        <p class="hero-note" style="margin-bottom:20px;">Indique ton adresse email, on t'envoie un code de vérification par email.</p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autocomplete="email" placeholder="toi@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <button type="submit" class="btn btn-primary btn-block">Recevoir le code</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>