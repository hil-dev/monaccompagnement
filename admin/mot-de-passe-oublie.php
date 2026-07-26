<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';
require_once __DIR__ . '/../src/Mail/MailService.php';

use App\Auth\AdminAuthService;

if (AdminAuthService::isAdmin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    try {
        $result = AdminAuthService::requestPasswordReset($email);
        $_SESSION['pending_admin_reset_id'] = $result['admin_id'];
        $_SESSION['admin_reset_last_sent'] = time();
        header('Location: /admin/reinitialiser-mot-de-passe.php');
        exit;
    } catch (\RuntimeException $e) {
        $erreur = $e->getMessage();
    }
}

$pageTitle = 'Mot de passe oublié — Administration — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/admin/login.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour
        </a>

        <p class="eyebrow eyebrow-center">Espace privé</p>
        <h1 class="auth-title">Mot de passe administrateur oublié</h1>
        <p class="hero-note" style="margin-bottom:20px;">Indique ton adresse email, on t'envoie un code de vérification par email.</p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="username">

            <button type="submit" class="btn btn-primary btn-block">Recevoir le code</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>