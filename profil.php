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

// Dernier paiement de l'utilisateur (le plus récent)
$stmtPaiement = $pdo->prepare('
    SELECT p.*, f.nom AS formule_nom, f.code AS formule_code
    FROM paiements p
    JOIN formules f ON f.id = p.formule_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 1
');
$stmtPaiement->execute([$user['id']]);
$dernierPaiement = $stmtPaiement->fetch();

$formuleChoisie = $_SESSION['formule_choisie'] ?? null;
$paymentStatus = $_GET['payment'] ?? null;

$pageTitle = 'Mon profil — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/index.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour
        </a>

        <p class="eyebrow eyebrow-center">Connecté</p>
        <h1 class="auth-title">Bienvenue , <?= htmlspecialchars($user['nom_complet'] ?? $user['email']) ?></h1>

        <?php if ($paymentStatus === 'success'): ?>
            <p class="auth-succes">✅ Paiement confirmé ! Ton accompagnement est activé.</p>
        <?php elseif ($paymentStatus === 'declined'): ?>
            <p class="auth-erreur">Le paiement a été refusé ou annulé. Tu peux réessayer ci-dessous.</p>
        <?php elseif ($paymentStatus === 'error'): ?>
            <p class="auth-erreur">Une erreur est survenue lors du paiement. Réessaie ou contacte le support.</p>
        <?php elseif ($paymentStatus === 'already_processed'): ?>
            <p class="auth-note">Ce paiement a déjà été validé précédemment.</p>
        <?php elseif (($_GET['complement'] ?? null) === 'success'): ?>
            <p class="auth-succes">✅ Profil complété avec succès !</p>
        <?php endif; ?>

        <p class="auth-note">
            Compte créé via <strong><?= htmlspecialchars($user['auth_provider']) ?></strong>.
        </p>

        <?php if (!empty($user['serie'])): ?>
            <p class="auth-note">Série : <strong><?= htmlspecialchars($user['serie']) ?></strong></p>
        <?php endif; ?>

        <?php if (!empty($user['code_accompagnement'])): ?>
            <p class="auth-note">Code d'accompagnement : <strong><?= htmlspecialchars($user['code_accompagnement']) ?></strong></p>
            <a href="/orientation-formulaire.php" class="btn btn-primary btn-block" style="margin-top: 10px;">
                Remplir / renvoyer mon profil d'orientation
            </a>
        <?php endif; ?>

        <?php
        // Le profil est-il complet (mention + téléphone) ?
        $profilIncomplet = empty($user['mention']) || empty($user['numero_telephone']);

        // A-t-il au moins un paiement réussi ?
        $stmtPaye = $pdo->prepare("SELECT COUNT(*) FROM paiements WHERE user_id = ? AND statut = 'reussi'");
        $stmtPaye->execute([$user['id']]);
        $aPayeAuMoinsUneFois = (bool) $stmtPaye->fetchColumn();
        ?>

        <?php if ($aPayeAuMoinsUneFois && $profilIncomplet): ?>
            <div class="recap-formule" style="margin-top: 20px; border-color:#f0c14b;">
                <p class="auth-note">Il te reste une dernière étape pour finaliser ton accompagnement.</p>
                <a href="/finaliser-profil.php" class="btn btn-primary btn-block" style="margin-top:10px;">
                    Compléter mon profil
                </a>
            </div>
        <?php endif; ?>

        <?php if ($dernierPaiement): ?>
            <div class="recap-formule" style="margin-top: 20px;">
                <p class="recap-formule-nom"><?= htmlspecialchars($dernierPaiement['formule_nom']) ?></p>
                <p class="recap-formule-prix"><?= number_format((float) $dernierPaiement['montant'], 0, ',', ' ') ?> <span>FCFA</span></p>
                <p class="statut-badge statut-<?= htmlspecialchars($dernierPaiement['statut']) ?>">
                    <?php
                        $labels = [
                            'reussi' => '✅ Payé',
                            'en_attente' => '⏳ En attente',
                            'echoue' => '❌ Échoué',
                            'annule' => '⛔ Annulé',
                        ];
                        echo $labels[$dernierPaiement['statut']] ?? $dernierPaiement['statut'];
                    ?>
                </p>
            </div>

            <?php if ($dernierPaiement['statut'] !== 'reussi'): ?>
                <a href="/paiement.php?formule=<?= htmlspecialchars($dernierPaiement['formule_code']) ?>" class="btn btn-primary btn-block" style="margin-top: 16px;">
                    Finaliser le paiement
                </a>
            <?php endif; ?>
        <?php elseif ($formuleChoisie): ?>
            <p class="auth-note" style="margin-top: 20px;">
                Formule sélectionnée : <strong><?= htmlspecialchars(ucfirst($formuleChoisie)) ?></strong>
            </p>
            <a href="/paiement.php?formule=<?= htmlspecialchars($formuleChoisie) ?>" class="btn btn-primary btn-block">
                Procéder au paiement
            </a>
        <?php else: ?>
            <p class="auth-note" style="margin-top: 20px;">
                Tu n'as pas encore choisi de formule d'accompagnement.
            </p>
            <a href="/index.php#formules" class="btn btn-outline btn-block">
                Voir les formules
            </a>
        <?php endif; ?>

        <a href="/auth/logout.php" class="btn btn-outline btn-block" style="margin-top: 12px;">Se déconnecter</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>