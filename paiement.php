<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Payment/SasPayService.php';
require_once __DIR__ . '/src/Accompagnement/AccompagnementService.php';

use App\Database\Database;
use App\Payment\SasPayService;
use App\Accompagnement\AccompagnementService;

$pdo = Database::getConnection();

$formuleCode = $_GET['formule'] ?? ($_SESSION['formule_choisie'] ?? null);
if (!$formuleCode) {
    header('Location: /index.php#formules');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM formules WHERE code = ? AND actif = 1');
$stmt->execute([$formuleCode]);
$formule = $stmt->fetch();

if (!$formule) {
    header('Location: /index.php#formules');
    exit;
}

$_SESSION['formule_choisie'] = $formuleCode;

// Accès libre, sans compte : on retrouve le visiteur via son jeton de session
$guestToken = $_SESSION['guest_token'] ?? null;

// Sécurité : on n'autorise le paiement que si le profil a bien été rempli avant
$profil = null;
if ($guestToken) {
    $stmtProfil = $pdo->prepare('SELECT * FROM profils_accompagnement WHERE guest_token = ? ORDER BY created_at DESC LIMIT 1');
    $stmtProfil->execute([$guestToken]);
    $profil = $stmtProfil->fetch();
}

if (!$profil) {
    header('Location: /accompagnement-formulaire.php?formule=' . urlencode($formuleCode));
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $reference = 'APB-' . strtoupper(bin2hex(random_bytes(6)));

        // 1. On enregistre le paiement en base AVANT de contacter SasPay (statut "en_attente")
        $stmtInsert = $pdo->prepare('
            INSERT INTO paiements (guest_token, formule_id, montant, statut, reference)
            VALUES (?, ?, ?, "en_attente", ?)
        ');
        $stmtInsert->execute([$guestToken, $formule['id'], $formule['prix'], $reference]);
        $paiementId = (int) $pdo->lastInsertId();

        // 2. On crée la session de checkout SasPay avec le montant exact du plan choisi.
        //    Le return_url contient NOTRE référence : c'est elle qui nous permet de retrouver
        //    le paiement au retour du client (SasPay ne renvoie aucun paramètre lui-même).
        $nomComplet = trim($profil['nom_complet'] ?? '');

        $payload = [
            'amount'         => number_format((float) $formule['prix'], 2, '.', ''), // "5000.00"
            'currency'       => 'XOF',
            'description'    => 'Accompagnement ' . $formule['nom'] . ' — ' . APP_NAME,
            'customer_email' => $profil['email'],
            'customer_name'  => $nomComplet !== '' ? $nomComplet : $profil['email'],
            'return_url'     => rtrim(APP_URL, '/') . '/paiement-callback.php?ref=' . urlencode($reference),
            'metadata'       => [
                'reference'    => $reference,
                'paiement_id'  => $paiementId,
                'formule_id'   => $formule['id'],
                'formule_code' => $formule['code'],
            ],
        ];

        $session = SasPayService::createCheckoutSession($payload);

        $pdo->prepare('UPDATE paiements SET saspay_session_id = ? WHERE id = ?')
            ->execute([$session['id'], $paiementId]);

        // 3. On redirige l'utilisateur vers la page de paiement hébergée SasPay
        header('Location: ' . $session['checkout_url']);
        exit;
    } catch (\Throwable $e) {
        error_log('Erreur initiation paiement SasPay : ' . $e->getMessage());
        $erreur = "Une erreur est survenue lors de l'initialisation du paiement. Réessaie dans un instant.";
    }
}

$avantages = json_decode($formule['avantages'], true);

$pageTitle = 'Paiement — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/assets/css/funnel-v2.css">

<section class="auth-page funnel-v2">
    <div class="funnel-wrap">
        <a href="/accompagnement-formulaire.php?formule=<?= urlencode($formuleCode) ?>" class="funnel-back">
            <span aria-hidden="true">←</span> Modifier mon profil
        </a>

        <ol class="funnel-steps" aria-label="Progression">
            <li class="is-done"><span class="funnel-step-num" aria-hidden="true">✓</span> Ton profil</li>
            <li class="is-current" aria-current="step"><span class="funnel-step-num">2</span> Paiement</li>
        </ol>

        <div class="auth-card">
            <h1 class="auth-title">Confirme ton paiement</h1>
            <p class="auth-note">Un seul paiement, et ton accompagnement démarre tout de suite.</p>

            <?php if ($erreur): ?>
                <p class="auth-erreur" role="alert"><?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>

            <div class="recap-formule">
                <p class="recap-formule-nom">Accompagnement <?= htmlspecialchars($formule['nom']) ?></p>
                <p class="recap-formule-prix"><?= number_format((float) $formule['prix'], 0, ',', ' ') ?> <span>FCFA</span></p>
                <ul class="recap-formule-avantages">
                    <?php foreach ($avantages as $avantage): ?>
                        <li><?= htmlspecialchars($avantage) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" class="btn btn-primary btn-block">Payer <?= number_format((float) $formule['prix'], 0, ',', ' ') ?> FCFA</button>
            </form>

            <p class="funnel-secure">Paiement sécurisé par Mobile Money ou carte bancaire.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>