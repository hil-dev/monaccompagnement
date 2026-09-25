<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';

use App\Auth\AdminAuthService;
use App\Database\Database;

AdminAuthService::requireAdmin();

$profilId = (int) ($_GET['id'] ?? 0);
if ($profilId <= 0) {
    header('Location: /admin/dashboard.php');
    exit;
}

$pdo = Database::getConnection();

$stmt = $pdo->prepare('SELECT * FROM profils_accompagnement WHERE id = ?');
$stmt->execute([$profilId]);
$profil = $stmt->fetch();

if (!$profil) {
    header('Location: /admin/dashboard.php');
    exit;
}

$stmtPaiements = $pdo->prepare('
    SELECT p.*, f.nom AS formule_nom
    FROM paiements p
    JOIN formules f ON f.id = p.formule_id
    WHERE p.guest_token = ?
    ORDER BY p.created_at DESC
');
$stmtPaiements->execute([$profil['guest_token']]);
$paiements = $stmtPaiements->fetchAll();

$labels = [
    'id' => 'ID',
    'nom_complet' => 'Nom et prénom',
    'universite' => 'Université',
    'email' => 'Email',
    'numero_whatsapp' => 'Numéro WhatsApp',
    'created_at' => 'Créé le',
];

$pageTitle = trim($profil['nom_complet']) . ' — Dashboard admin — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/admin-theme.php'; ?>

<div class="admin-wrap">
    <a href="/admin/dashboard.php" class="admin-retour">
        <span aria-hidden="true">←</span> Retour au dashboard
    </a>

    <div class="admin-header">
        <p class="admin-brand"><?= htmlspecialchars(trim($profil['nom_complet'])) ?> <span class="admin-brand-tag">Profil</span></p>
        <div class="admin-nav">
            <button type="button" class="theme-toggle" id="themeToggle" title="Changer de thème">🌙</button>
        </div>
    </div>

    <p class="admin-section-title">Informations du profil</p>
    <div class="admin-detail-grid">
        <?php foreach ($labels as $champ => $label): ?>
            <div class="admin-detail-item">
                <p class="admin-detail-label"><?= htmlspecialchars($label) ?></p>
                <p class="admin-detail-value">
                    <?php
                        $valeur = $profil[$champ] ?? null;
                        echo ($valeur === null || $valeur === '') ? '—' : htmlspecialchars((string) $valeur);
                    ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="admin-section-title">Historique des paiements</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Formule</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Code accompagnement</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php if (empty($paiements)): ?>
                <tr><td colspan="7">Aucun paiement pour ce profil.</td></tr>
                <?php else: ?>
                    <?php foreach ($paiements as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['formule_nom']) ?></td>
                        <td><?= number_format((float) $p['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td class="statut-<?= htmlspecialchars($p['statut']) ?>"><?= htmlspecialchars($p['statut']) ?></td>
                        <td><?= htmlspecialchars($p['reference']) ?></td>
                        <td><?= htmlspecialchars($p['code_accompagnement'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>