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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/assets/css/funnel-v2.css">

<section class="auth-page funnel-v2">
    <div class="funnel-wrap">
        <a href="/admin/login.php" class="funnel-back">
            <span aria-hidden="true">←</span> Retour
        </a>

        <div class="auth-card">
            <h1 class="auth-title">Mot de passe administrateur oublié</h1>
            <p class="auth-note">Indique ton adresse email, on t'envoie un code de vérification par email.</p>

            <?php if ($erreur): ?>
                <p class="auth-erreur" role="alert"><?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="username">

                <button type="submit" class="btn btn-primary btn-block">Recevoir le code</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>