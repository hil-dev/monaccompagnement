<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';

use App\Auth\AuthService;
use App\Database\Database;

AuthService::requireLogin();

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([AuthService::currentUserId()]);
$user = $stmt->fetch();

// On vérifie qu'il a bien un paiement réussi, sinon rien à finaliser
$stmtPaiement = $pdo->prepare("
    SELECT COUNT(*) FROM paiements WHERE user_id = ? AND statut = 'reussi'
");
$stmtPaiement->execute([$user['id']]);
$aPayeAuMoinsUneFois = (bool) $stmtPaiement->fetchColumn();

if (!$aPayeAuMoinsUneFois) {
    header('Location: /profil.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mention = $_POST['mention'] ?? '';
    $telephone = $_POST['numero_telephone'] ?? '';

    try {
        AuthService::completeProfile((int) $user['id'], $mention, $telephone);
        header('Location: /profil.php?complement=success');
        exit;
    } catch (\RuntimeException $e) {
        $erreur = $e->getMessage();
    }
}

$pageTitle = 'Finaliser mon profil — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/profil.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour à mon profil
        </a>

        <p class="eyebrow eyebrow-center">Presque fini</p>
        <h1 class="auth-title">Finalise ton profil</h1>
        <p class="auth-note">Ces infos permettent à ton conseiller de préparer ton accompagnement.</p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="mention">Mention obtenue au bac</label>
            <select id="mention" name="mention" required>
                <option value="">-- Choisis ta mention --</option>
                <?php foreach (AuthService::MENTIONS as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= (($_POST['mention'] ?? '') === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="numero_telephone">Numéro de téléphone</label>
            <input type="tel" id="numero_telephone" name="numero_telephone" required placeholder="+229 XX XX XX XX" value="<?= htmlspecialchars($_POST['numero_telephone'] ?? '') ?>">

            <button type="submit" class="btn btn-primary btn-block">Valider</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>