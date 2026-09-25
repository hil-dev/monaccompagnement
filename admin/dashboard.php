<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';
require_once __DIR__ . '/../src/Analytics/VisiteService.php';

use App\Auth\AdminAuthService;
use App\Database\Database;
use App\Analytics\VisiteService;

AdminAuthService::requireAdmin();

$pdo = Database::getConnection();

$totalVisites = VisiteService::totalVisites();
$visitesAujourdhui = VisiteService::visitesAujourdhui();

// Stats globales
$totalProfils = (int) $pdo->query('SELECT COUNT(*) FROM profils_accompagnement')->fetchColumn();

$stmtRevenu = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'reussi'");
$revenuTotal = (float) $stmtRevenu->fetchColumn();

$stmtParStatut = $pdo->query("SELECT statut, COUNT(*) AS nb FROM paiements GROUP BY statut");
$parStatut = $stmtParStatut->fetchAll(\PDO::FETCH_KEY_PAIR);

// Derniers profils d'accompagnement (visiteurs, sans compte)
$profils = $pdo->query('
    SELECT id, nom_complet, universite, email, numero_whatsapp, created_at
    FROM profils_accompagnement ORDER BY created_at DESC LIMIT 200
')->fetchAll();

// Derniers paiements finalisés (réussis), reliés au profil via guest_token
$paiements = $pdo->query("
    SELECT p.*, pa.email AS profil_email, pa.nom_complet AS profil_nom_complet, f.nom AS formule_nom
    FROM paiements p
    LEFT JOIN profils_accompagnement pa ON pa.guest_token = p.guest_token
    JOIN formules f ON f.id = p.formule_id
    WHERE p.statut = 'reussi'
    ORDER BY p.created_at DESC
    LIMIT 200
")->fetchAll();

$pageTitle = 'Dashboard admin — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/admin-theme.php'; ?>

<style>
    .admin-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-wrap:wrap;
        gap:.75rem;
        position:relative;
    }
    .admin-header .theme-toggle{ order:2; }
    .admin-nav{ display:flex; align-items:center; gap:.5rem; order:1; }

    @media (max-width: 640px){
        .admin-header{
            display:flex !important;
            flex-direction:row !important;
            flex-wrap:nowrap !important;
            align-items:center !important;
            justify-content:space-between !important;
            position:relative !important;
            width:100% !important;
        }
        .admin-header .admin-brand{
            margin:0 !important;
            flex:0 1 auto !important;
            order:0 !important;
            white-space:nowrap;
        }
        .admin-header .theme-toggle{
            position:absolute !important;
            left:50% !important;
            top:50% !important;
            right:auto !important;
            transform:translate(-50%, -50%) !important;
            margin:0 !important;
            flex:none !important;
        }
        .admin-header .drawer-toggle{
            order:0 !important;
            margin-left:auto !important;
            flex:none !important;
        }
    }
</style>

<div class="admin-wrap">
    <div class="admin-header">
        <p class="admin-brand">Dashboard <span class="admin-brand-tag">Admin</span></p>

        <button type="button" class="theme-toggle" id="themeToggle" title="Changer de thème">🌙</button>

        <button type="button" class="drawer-toggle" id="drawerToggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="adminDrawer">
            <span class="drawer-toggle-bar"></span>
            <span class="drawer-toggle-bar"></span>
            <span class="drawer-toggle-bar"></span>
        </button>

        <nav class="admin-nav" id="adminDrawer">
            <button type="button" class="drawer-close" id="drawerClose" aria-label="Fermer le menu">✕</button>
            <a href="/admin/formules.php" class="btn btn-outline">Gérer les formules</a>
            <a href="/admin/newsletter.php" class="btn btn-outline">Newsletter</a>
            <a href="/admin/logout.php" class="btn btn-outline">Déconnexion</a>
        </nav>
    </div>

    <div class="admin-drawer-overlay" id="adminDrawerOverlay"></div>

    <div class="admin-stats">
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= $totalProfils ?></p>
            <p class="admin-stat-label">Profils d'accompagnement</p>
        </div>
        <div class="admin-stat-card">
            <p class="admin-stat-value is-accent"><?= number_format($revenuTotal, 0, ',', ' ') ?> FCFA</p>
            <p class="admin-stat-label">Revenu total (paiements réussis)</p>
        </div>
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= (int) ($parStatut['reussi'] ?? 0) ?></p>
            <p class="admin-stat-label">Paiements réussis</p>
        </div>
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= (int) ($parStatut['en_attente'] ?? 0) + (int) ($parStatut['echoue'] ?? 0) ?></p>
            <p class="admin-stat-label">Paiements non finalisés</p>
        </div>
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= $totalVisites ?></p>
            <p class="admin-stat-label">Visites totales</p>
        </div>
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= $visitesAujourdhui ?></p>
            <p class="admin-stat-label">Visites aujourd'hui</p>
        </div>
    </div>

    <p class="admin-section-title">Profils d'accompagnement</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nom et prénom</th><th>Université</th><th>Email</th><th>Numéro WhatsApp</th><th>Créé le</th>
                </tr>
            </thead>
            <tbody id="profilsTableBody">
                <?php foreach ($profils as $i => $p): ?>
                <tr class="admin-row-link<?= $i >= 10 ? ' admin-row-hidden' : '' ?>" tabindex="0" data-href="/admin/profil.php?id=<?= $p['id'] ?>">
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nom_complet']) ?></td>
                    <td><?= htmlspecialchars($p['universite'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['numero_whatsapp'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($profils) > 10): ?>
        <div class="admin-voir-plus-wrap">
            <button type="button" class="btn btn-outline admin-voir-plus" data-target="profilsTableBody">Voir plus</button>
        </div>
    <?php endif; ?>

    <p class="admin-section-title">Paiements finalisés</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Profil</th><th>Formule</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Code accompagnement</th><th>Date</th></tr>
            </thead>
            <tbody id="paiementsTableBody">
                <?php foreach ($paiements as $i => $p): ?>
                <tr class="<?= $i >= 10 ? 'admin-row-hidden' : '' ?>">
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars(($p['profil_nom_complet'] ?? '') ?: ($p['profil_email'] ?? '—')) ?></td>
                    <td><?= htmlspecialchars($p['formule_nom']) ?></td>
                    <td><?= number_format((float) $p['montant'], 0, ',', ' ') ?> FCFA</td>
                    <td class="statut-<?= htmlspecialchars($p['statut']) ?>"><?= htmlspecialchars($p['statut']) ?></td>
                    <td><?= htmlspecialchars($p['reference']) ?></td>
                    <td><?= htmlspecialchars($p['code_accompagnement'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($paiements) > 10): ?>
        <div class="admin-voir-plus-wrap">
            <button type="button" class="btn btn-outline admin-voir-plus" data-target="paiementsTableBody">Voir plus</button>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>