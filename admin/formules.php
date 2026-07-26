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

        if ($id > 0 && $prix >= 0 && $placesRestantes >= 0 && $placesTotales >= 0) {
            $stmt = $pdo->prepare('
                UPDATE formules
                SET prix = ?, places_restantes = ?, places_totales = ?, actif = ?
                WHERE id = ?
            ');
            $stmt->execute([$prix, $placesRestantes, $placesTotales, $actif, $id]);
            $succes = 'Formule mise à jour.';
        } else {
            $erreur = 'Valeurs invalides.';
        }
    }
}

$formules = $pdo->query('SELECT * FROM formules ORDER BY prix ASC')->fetchAll();
$csrf = AdminAuthService::csrfToken();

$pageTitle = 'Gérer les formules — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-wrap { max-width: 900px; margin: 40px auto; padding: 0 20px; }
.formule-edit-card { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.formule-edit-card h3 { margin-bottom: 12px; }
.formule-edit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 12px; }
.formule-edit-grid label { display: block; font-size: 0.85rem; margin-bottom: 4px; color: #555; }
.formule-edit-grid input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 8px; }
</style>

<div class="admin-wrap">
    <p><a href="/admin/dashboard.php">← Retour au dashboard</a></p>
    <h1 style="margin-bottom: 20px;">Gérer les formules</h1>

    <?php if ($erreur): ?><p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <?php if ($succes): ?><p class="auth-succes"><?= htmlspecialchars($succes) ?></p><?php endif; ?>

    <?php foreach ($formules as $f): ?>
    <div class="formule-edit-card">
        <h3><?= htmlspecialchars($f['nom']) ?> (<?= htmlspecialchars($f['code']) ?>)</h3>
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= $f['id'] ?>">
            <div class="formule-edit-grid">
                <div>
                    <label>Prix (FCFA)</label>
                    <input type="number" name="prix" min="0" value="<?= (int) $f['prix'] ?>" required>
                </div>
                <div>
                    <label>Places restantes</label>
                    <input type="number" name="places_restantes" min="0" value="<?= (int) $f['places_restantes'] ?>" required>
                </div>
                <div>
                    <label>Places totales</label>
                    <input type="number" name="places_totales" min="0" value="<?= (int) $f['places_totales'] ?>" required>
                </div>
                <div>
                    <label>Statut</label>
                    <label style="display:flex; align-items:center; gap:6px; margin-top: 8px;">
                        <input type="checkbox" name="actif" <?= $f['actif'] ? 'checked' : '' ?> style="width:auto;">
                        Active
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>