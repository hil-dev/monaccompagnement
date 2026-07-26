<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';

use App\Auth\AdminAuthService;
use App\Database\Database;

AdminAuthService::requireAdmin();

$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: /admin/dashboard.php');
    exit;
}

$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /admin/dashboard.php');
    exit;
}

$stmtPaiements = $pdo->prepare('
    SELECT p.*, f.nom AS formule_nom
    FROM paiements p
    JOIN formules f ON f.id = p.formule_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
');
$stmtPaiements->execute([$userId]);
$paiements = $stmtPaiements->fetchAll();

// Champs sensibles qu'on n'affiche jamais tels quels dans l'interface admin
$champsMasques = ['password_hash', 'reset_code', 'reset_code_expires_at', 'verification_code', 'verification_code_expires_at'];

$labels = [
    'id' => 'ID',
    'nom_complet' => 'Nom complet',
    'email' => 'Email',
    'serie' => 'Série',
    'mention' => 'Mention',
    'numero_telephone' => 'Téléphone',
    'code_accompagnement' => 'Code accompagnement',
    'auth_provider' => 'Inscrit via',
    'google_id' => 'Compte Google lié',
    'email_verified' => 'Email vérifié',
    'created_at' => 'Créé le',
];

$pageTitle = ($user['nom_complet'] ?? $user['email']) . ' — Dashboard admin — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/admin-theme.php'; ?>

<div class="admin-wrap">
    <a href="/admin/dashboard.php" class="admin-retour">
        <span aria-hidden="true">←</span> Retour au dashboard
    </a>

    <div class="admin-header">
        <h1 class="admin-section-title" style="font-size:1.4rem;"><?= htmlspecialchars($user['nom_complet'] ?? $user['email']) ?></h1>
        <div class="admin-nav">
            <button type="button" class="theme-toggle" id="themeToggle" title="Changer de thème">🌙</button>
        </div>
    </div>

    <p class="admin-section-title">Informations du profil</p>
    <div class="admin-detail-grid">
        <?php foreach ($labels as $champ => $label): ?>
            <?php if (in_array($champ, $champsMasques, true)) { continue; } ?>
            <div class="admin-detail-item">
                <p class="admin-detail-label"><?= htmlspecialchars($label) ?></p>
                <p class="admin-detail-value">
                    <?php
                        $valeur = $user[$champ] ?? null;
                        if ($champ === 'email_verified') {
                            echo $valeur ? 'Oui' : 'Non';
                        } elseif ($valeur === null || $valeur === '') {
                            echo '—';
                        } else {
                            echo htmlspecialchars((string) $valeur);
                        }
                    ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="admin-section-title">Historique des paiements</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Formule</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php if (empty($paiements)): ?>
                <tr><td colspan="6">Aucun paiement pour cet utilisateur.</td></tr>
                <?php else: ?>
                    <?php foreach ($paiements as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['formule_nom']) ?></td>
                        <td><?= number_format((float) $p['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td><?= htmlspecialchars($p['statut']) ?></td>
                        <td><?= htmlspecialchars($p['reference']) ?></td>
                        <td><?= htmlspecialchars($p['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>