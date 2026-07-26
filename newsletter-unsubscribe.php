<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';

use App\Database\Database;

$token = trim($_GET['token'] ?? '');
$statut = 'invalide';

if ($token !== '') {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT id, actif FROM newsletter_subscribers WHERE token = ?');
    $stmt->execute([$token]);
    $subscriber = $stmt->fetch();

    if ($subscriber) {
        if ((int) $subscriber['actif'] === 1) {
            $update = $pdo->prepare('UPDATE newsletter_subscribers SET actif = 0, unsubscribed_at = NOW() WHERE id = ?');
            $update->execute([$subscriber['id']]);
        }
        $statut = 'ok';
    }
}

$pageTitle = 'Désabonnement — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card" style="text-align:center;">
        <p class="eyebrow eyebrow-center">Newsletter</p>
        <?php if ($statut === 'ok'): ?>
            <h1 class="auth-title">Désabonnement confirmé</h1>
            <p class="auth-note">Tu ne recevras plus nos newsletters. Tu peux te réinscrire à tout moment depuis le site.</p>
        <?php else: ?>
            <h1 class="auth-title">Lien invalide</h1>
            <p class="auth-note">Ce lien de désabonnement n'est plus valide.</p>
        <?php endif; ?>
        <a href="/index.php" class="btn btn-primary" style="margin-top: 20px;">Retour à l'accueil</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>