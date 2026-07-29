<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Payment/FedaPayService.php';

use App\Database\Database;
use App\Payment\FedaPayService;

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

// Sécurité : on n'autorise le paiement que si le profil d'orientation a bien été rempli avant
$profil = null;
if ($guestToken) {
    $stmtProfil = $pdo->prepare('SELECT * FROM profils_orientation WHERE guest_token = ? ORDER BY created_at DESC LIMIT 1');
    $stmtProfil->execute([$guestToken]);
    $profil = $stmtProfil->fetch();
}

if (!$profil) {
    header('Location: /orientation-formulaire.php?formule=' . urlencode($formuleCode));
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $reference = 'APB-' . strtoupper(bin2hex(random_bytes(6)));

        // 1. On enregistre le paiement en base AVANT de contacter FedaPay (statut "en_attente")
        $stmtInsert = $pdo->prepare('
            INSERT INTO paiements (guest_token, formule_id, montant, statut, reference)
            VALUES (?, ?, ?, "en_attente", ?)
        ');
        $stmtInsert->execute([$guestToken, $formule['id'], $formule['prix'], $reference]);
        $paiementId = (int) $pdo->lastInsertId();

        // 2. On crée la transaction FedaPay avec le montant exact du plan choisi
        $payload = [
            'description'     => 'Accompagnement ' . $formule['nom'] . ' — ' . APP_NAME,
            'amount'          => (int) $formule['prix'],
            'currency'        => ['iso' => 'XOF'],
            'callback_url'    => rtrim(APP_URL, '/') . '/paiement-callback.php',
            'customer'        => [
                'email' => $profil['email'],
            ],
            'custom_metadata' => [
                'reference'    => $reference,
                'paiement_id'  => $paiementId,
                'guest_token'  => $guestToken,
                'formule_id'   => $formule['id'],
                'formule_code' => $formule['code'],
            ],
        ];

        $transaction   = FedaPayService::createTransaction($payload);
        $transactionId = $transaction['id'];

        $pdo->prepare('UPDATE paiements SET fedapay_transaction_id = ? WHERE id = ?')
            ->execute([$transactionId, $paiementId]);

        // 3. On récupère le lien de paiement hébergé et on redirige l'utilisateur dessus
        $paymentUrl = FedaPayService::getPaymentUrl((int) $transactionId);

        header('Location: ' . $paymentUrl);
        exit;
    } catch (\Throwable $e) {
        error_log('Erreur initiation paiement FedaPay : ' . $e->getMessage());
        $erreur = "Une erreur est survenue lors de l'initialisation du paiement. Réessaie dans un instant.";
    }
}

$avantages = json_decode($formule['avantages'], true);

$pageTitle = 'Paiement — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/index.php" class="auth-retour auth-retour-top">
            <span aria-hidden="true">←</span> Retour à l'accueil
        </a>

        <p class="eyebrow eyebrow-center">Dernière étape</p>
        <h1 class="auth-title">Confirme ton paiement</h1>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <div class="recap-formule">
            <p class="recap-formule-nom"><?= htmlspecialchars($formule['nom']) ?></p>
            <p class="recap-formule-prix"><?= number_format((float) $formule['prix'], 0, ',', ' ') ?> <span>FCFA</span></p>
            <ul class="recap-formule-avantages">
                <?php foreach ($avantages as $avantage): ?>
                    <li><?= htmlspecialchars($avantage) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form method="POST">
            <button type="submit" class="btn btn-primary btn-block">Payer <?= number_format((float) $formule['prix'], 0, ',', ' ') ?> FCFA avec FedaPay</button>
        </form>

        <p class="auth-note" style="margin-top: 18px;">Paiement sécurisé : Mobile Money, carte bancaire, FedaPay.</p>
    </div>
</section>

<style>
    @media (max-width: 768px) {
        .auth-page {
            position: relative;
        }
        .auth-retour-top {
            position: absolute;
            top: 16px;
            left: 16px;
            margin: 0;
            z-index: 10;
        }
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>