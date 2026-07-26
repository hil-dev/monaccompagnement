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

<section class="auth-page">
    <div class="auth-card">
        <p class="eyebrow eyebrow-center">Espace privé</p>
        <h1 class="auth-title">Connexion administrateur</h1>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="username">

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <p class="auth-switch" style="margin: -8px 0 4px; text-align:right;"><a href="/admin/mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>