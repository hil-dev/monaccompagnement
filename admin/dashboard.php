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
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$stmtRevenu = $pdo->query("SELECT COALESCE(SUM(montant), 0) FROM paiements WHERE statut = 'reussi'");
$revenuTotal = (float) $stmtRevenu->fetchColumn();

$stmtParStatut = $pdo->query("SELECT statut, COUNT(*) AS nb FROM paiements GROUP BY statut");
$parStatut = $stmtParStatut->fetchAll(\PDO::FETCH_KEY_PAIR);

// Derniers utilisateurs
$users = $pdo->query('
    SELECT id, nom_complet, prenom, email, serie, mention, age, moyenne, profession_reve, ecole_reve,
           numero_telephone, code_accompagnement, auth_provider, created_at
    FROM users ORDER BY created_at DESC LIMIT 200
')->fetchAll();

// Derniers paiements finalisés (réussis)
$paiements = $pdo->query("
    SELECT p.*, u.email AS user_email, u.nom_complet, f.nom AS formule_nom
    FROM paiements p
    JOIN users u ON u.id = p.user_id
    JOIN formules f ON f.id = p.formule_id
    WHERE p.statut = 'reussi'
    ORDER BY p.created_at DESC
    LIMIT 200
")->fetchAll();

$pageTitle = 'Dashboard admin — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/admin-theme.php'; ?>

<div class="admin-wrap">
    <div class="admin-header">
        <h1 class="admin-section-title" style="font-size:1.4rem;">Dashboard</h1>

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

    <style>
        .admin-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            flex-wrap:wrap;
            gap:.75rem;
            position:relative;
        }
        .admin-header .theme-toggle{
            order:2;
        }
        .admin-nav{
            display:flex;
            align-items:center;
            gap:.5rem;
            order:1;
        }
        .drawer-toggle{
            display:none;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            gap:4px;
            width:40px;
            height:40px;
            border-radius:50%;
            border:1.5px solid currentColor;
            background:transparent;
            color:var(--admin-text, #1a1a1a);
            cursor:pointer;
        }
        .drawer-toggle-bar{
            display:block;
            width:18px;
            height:2px;
            background:currentColor;
            border-radius:2px;
        }
        .drawer-close{
            display:none;
            align-self:flex-end;
            width:32px;
            height:32px;
            border-radius:50%;
            border:1.5px solid currentColor;
            background:transparent;
            color:var(--admin-text, #1a1a1a);
            cursor:pointer;
            font-size:.95rem;
            line-height:1;
            margin-bottom:.5rem;
        }
        .admin-drawer-overlay{
            display:none;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.35);
            z-index:1000;
        }
        .admin-drawer-overlay.open{ display:block; }

        @media (max-width: 640px){
            .admin-nav{
                position:fixed;
                top:0;
                right:0;
                height:100vh;
                width:min(78vw, 300px);
                background:var(--admin-bg, #f4f1ea);
                color:var(--admin-text, inherit);
                flex-direction:column;
                align-items:stretch;
                justify-content:flex-start;
                padding:1.25rem;
                gap:.75rem;
                transform:translateX(100%);
                transition:transform .28s ease;
                box-shadow:-8px 0 24px rgba(0,0,0,.18);
                z-index:1001;
                overflow-y:auto;
            }
            .admin-nav.open{ transform:translateX(0); }
            .admin-nav .btn{ width:100%; text-align:center; }
            .drawer-toggle{ display:flex; }
            .drawer-close{ display:inline-flex; }

            /* Dashboard à l'extrémité gauche, thème au centre absolu, drawer à l'extrémité droite — tout sur une seule ligne en haut */
            .admin-header{
                display:flex !important;
                flex-direction:row !important;
                flex-wrap:nowrap !important;
                align-items:center !important;
                justify-content:space-between !important;
                position:relative !important;
                width:100% !important;
            }
            .admin-header .admin-section-title{
                margin:0 !important;
                padding:0 !important;
                text-align:left !important;
                white-space:nowrap;
                flex:0 1 auto !important;
                order:0 !important;
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

    <div class="admin-stats">
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= $totalUsers ?></p>
            <p class="admin-stat-label">Utilisateurs inscrits</p>
        </div>
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= number_format($revenuTotal, 0, ',', ' ') ?> FCFA</p>
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

    <p class="admin-section-title">Utilisateurs</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nom</th><th>Email</th><th>Série</th><th>Mention</th>
                    <th>Âge</th><th>Moyenne</th><th>Profession rêvée</th><th>École rêvée</th>
                    <th>Téléphone</th><th>Code accompagnement</th><th>Inscrit via</th><th>Créé le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="admin-row-link" tabindex="0" data-href="/admin/utilisateur.php?id=<?= $u['id'] ?>">
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['nom_complet'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['serie'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['mention'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['age'] !== null ? (string) $u['age'] : '—') ?></td>
                    <td><?= htmlspecialchars($u['moyenne'] !== null ? (string) $u['moyenne'] : '—') ?></td>
                    <td><?= htmlspecialchars($u['profession_reve'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['ecole_reve'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['numero_telephone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['code_accompagnement'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['auth_provider']) ?></td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="admin-section-title">Paiements finalisés</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Utilisateur</th><th>Formule</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($paiements as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['nom_complet'] ?? $p['user_email']) ?></td>
                    <td><?= htmlspecialchars($p['formule_nom']) ?></td>
                    <td><?= number_format((float) $p['montant'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= htmlspecialchars($p['statut']) ?></td>
                    <td><?= htmlspecialchars($p['reference']) ?></td>
                    <td><?= htmlspecialchars($p['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var toggleBtn = document.getElementById('drawerToggle');
    var closeBtn = document.getElementById('drawerClose');
    var drawer = document.getElementById('adminDrawer');
    var overlay = document.getElementById('adminDrawerOverlay');
    if (!toggleBtn || !drawer || !overlay) return;

    function openDrawer() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }
    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }

    toggleBtn.addEventListener('click', function () {
        drawer.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });
    // Ferme le drawer si on repasse en desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth > 640) closeDrawer();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>