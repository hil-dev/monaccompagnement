<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';

use App\Auth\AdminAuthService;

if (empty($_SESSION['pending_admin_reset_id'])) {
    header('Location: /admin/mot-de-passe-oublie.php');
    exit;
}

$adminId = (int) $_SESSION['pending_admin_reset_id'];
$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($password !== $passwordConfirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            AdminAuthService::resetPassword($adminId, $code, $password);
            unset($_SESSION['pending_admin_reset_id'], $_SESSION['admin_reset_last_sent']);
            header('Location: /admin/login.php?password_reset=1');
            exit;
        } catch (\RuntimeException $e) {
            $erreur = $e->getMessage();
        }
    }
}

$pageTitle = 'Nouveau mot de passe admin — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <p class="eyebrow eyebrow-center">Dernière étape</p>
        <h1 class="auth-title">Choisis un nouveau mot de passe</h1>
        <p class="hero-note" style="margin-bottom:20px;">Vérifie ta boîte mail, saisis le code reçu et ton nouveau mot de passe.</p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="code">Code reçu par email</label>
            <input type="text" id="code" name="code" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="123456">

            <label for="password">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="••••••••">

            <label for="password_confirm">Confirme le mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" placeholder="••••••••">

            <button type="submit" class="btn btn-primary btn-block">Réinitialiser le mot de passe</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>