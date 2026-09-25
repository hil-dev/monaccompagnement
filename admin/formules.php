<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';

use App\Auth\AdminAuthService;
use App\Database\Database;

AdminAuthService::requireAdmin();

$pdo = Database::getConnection();
$erreur = null;
$succes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminAuthService::checkCsrf($_POST['csrf'] ?? null)) {
        $erreur = 'Session expirée, réessaie.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $prix = (int) ($_POST['prix'] ?? 0);
        $placesRestantes = (int) ($_POST['places_restantes'] ?? 0);
        $placesTotales = (int) ($_POST['places_totales'] ?? 0);
        $actif = isset($_POST['actif']) ? 1 : 0;

        if ($id > 0 && $prix >= 0 && $placesRestantes >= 0 && $placesTotales >= 0 && $placesRestantes <= $placesTotales) {
            $stmt = $pdo->prepare('
                UPDATE formules
                SET prix = ?, places_restantes = ?, places_totales = ?, actif = ?
                WHERE id = ?
            ');
            $stmt->execute([$prix, $placesRestantes, $placesTotales, $actif, $id]);
            $succes = 'Formule mise à jour.';
        } else {
            $erreur = 'Valeurs invalides : les places restantes ne peuvent pas dépasser les places totales.';
        }
    }
}

$formules = $pdo->query('SELECT * FROM formules ORDER BY prix ASC')->fetchAll();
$csrf = AdminAuthService::csrfToken();

$pageTitle = 'Gérer les formules — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<?php require_once __DIR__ . '/../includes/admin-theme.php'; ?>

<style>
    .admin-places-occupees {
        display: inline-block;
        margin-top: 6px;
        font-size: 0.82rem;
        color: var(--admin-text-soft, #6b6055);
    }
    .admin-places-jauge {
        margin-top: 8px;
        height: 6px;
        border-radius: 999px;
        background: rgba(0,0,0,0.08);
        overflow: hidden;
        max-width: 260px;
    }
    .admin-places-jauge-remplie {
        height: 100%;
        border-radius: inherit;
        transition: width 0.3s ease, background-color 0.3s ease;
    }
</style>

<div class="admin-wrap">
    <a href="/admin/dashboard.php" class="admin-retour">
        <span aria-hidden="true">←</span> Retour au dashboard
    </a>

    <div class="admin-header">
        <p class="admin-brand">Gérer les formules <span class="admin-brand-tag">Admin</span></p>
    </div>

    <?php if ($erreur): ?><p class="admin-alert admin-alert-erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <?php if ($succes): ?><p class="admin-alert admin-alert-succes"><?= htmlspecialchars($succes) ?></p><?php endif; ?>

    <?php foreach ($formules as $f):
        $placesOccupees = (int) $f['places_totales'] - (int) $f['places_restantes'];
        $pourcentageOccupe = $f['places_totales'] > 0
            ? round(($placesOccupees / $f['places_totales']) * 100)
            : 0;
        if ($pourcentageOccupe >= 80) {
            $couleurJauge = '#a32d2d';
        } elseif ($pourcentageOccupe >= 50) {
            $couleurJauge = '#c98f00';
        } else {
            $couleurJauge = '#2e9e52';
        }
    ?>
    <div class="admin-card">
        <h3><?= htmlspecialchars($f['nom']) ?> <span class="admin-detail-label" style="display:inline; text-transform:none;">(<?= htmlspecialchars($f['code']) ?>)</span></h3>

        <p class="admin-places-occupees">
            <?= $placesOccupees ?> / <?= (int) $f['places_totales'] ?> places occupées (<?= $pourcentageOccupe ?>%)
        </p>
        <div class="admin-places-jauge">
            <div class="admin-places-jauge-remplie" style="width: <?= $pourcentageOccupe ?>%; background: <?= $couleurJauge ?>;"></div>
        </div>

        <form method="POST" style="margin-top: 16px;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= $f['id'] ?>">
            <div class="admin-form-grid">
                <div>
                    <label>Prix (FCFA)</label>
                    <input type="number" name="prix" min="0" value="<?= (int) $f['prix'] ?>" required>
                </div>
                <div>
                    <label>Places restantes</label>
                    <input type="number" name="places_restantes" min="0" max="<?= (int) $f['places_totales'] ?>" value="<?= (int) $f['places_restantes'] ?>" required>
                </div>
                <div>
                    <label>Places totales</label>
                    <input type="number" name="places_totales" min="0" value="<?= (int) $f['places_totales'] ?>" required>
                </div>
                <div>
                    <label>Statut</label>
                    <label class="admin-checkbox-row">
                        <input type="checkbox" name="actif" <?= $f['actif'] ? 'checked' : '' ?>>
                        Active
                    </label>
                </div>
            </div>
            <button type="submit" class="admin-btn-primary">Enregistrer</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>