<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';

use App\Auth\AdminAuthService;

if (AdminAuthService::isAdmin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (AdminAuthService::login($email, $password)) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    $erreur = 'Email ou mot de passe incorrect.';
}

$pageTitle = 'Administration — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/assets/css/funnel-v2.css">

<section class="auth-page funnel-v2">
    <div class="funnel-wrap">
        <a href="/index.php" class="funnel-back">
            <span aria-hidden="true">←</span> Retour au site
        </a>

        <div class="auth-card">
            <h1 class="auth-title">Connexion administrateur</h1>
            <p class="auth-note">Réservé à l'équipe <?= htmlspecialchars(APP_NAME) ?>.</p>

            <?php if ($erreur): ?>
                <p class="auth-erreur" role="alert"><?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="username">

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <p style="margin: -6px 0 6px; text-align:right;">
                    <a href="/admin/mot-de-passe-oublie.php" style="color: var(--red); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Mot de passe oublié ?</a>
                </p>

                <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>