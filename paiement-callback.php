<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Payment/FedaPayService.php';
require_once __DIR__ . '/src/Auth/AuthService.php'; // conservé uniquement pour generateCodeAccompagnement()
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Auth\AuthService;
use App\Database\Database;
use App\Payment\FedaPayService;
use App\Mail\MailService;

if (!isset($_GET['id'])) {
    header('Location: /index.php?payment=error');
    exit;
}

$transactionId = (int) $_GET['id'];
$pdo = Database::getConnection();

// 1. Vérification de la transaction directement auprès de FedaPay (jamais faire confiance au paramètre GET seul)
try {
    $transaction = FedaPayService::fetchTransaction($transactionId);
} catch (\Throwable $e) {
    error_log('Erreur vérification transaction FedaPay : ' . $e->getMessage());
    header('Location: /index.php?payment=error');
    exit;
}

// 2. On retrouve le paiement correspondant en base
$stmt = $pdo->prepare('SELECT * FROM paiements WHERE fedapay_transaction_id = ?');
$stmt->execute([$transactionId]);
$paiement = $stmt->fetch();

if (!$paiement) {
    error_log("Paiement introuvable pour la transaction FedaPay {$transactionId}");
    header('Location: /index.php?payment=error');
    exit;
}

// 3. Idempotence : si déjà traité, on ne refait rien
if ($paiement['statut'] === 'reussi') {
    header('Location: https://chat.whatsapp.com/DGKN6QRai9ICpUfOCaHVZ6?s=cl&p=a&ilr=1');
    exit;
}

// 4. Le paiement n'a pas été approuvé côté FedaPay
if (($transaction['status'] ?? '') !== 'approved') {
    $pdo->prepare('UPDATE paiements SET statut = "echoue" WHERE id = ?')->execute([$paiement['id']]);
    header('Location: /index.php?payment=declined');
    exit;
}

// 5. Vérification anti-fraude : le montant payé doit correspondre exactement au montant attendu
$montantFeda = (int) $transaction['amount'];
$montantDb   = (int) $paiement['montant'];

if ($montantFeda !== $montantDb) {
    error_log("❌ Montant incohérent — DB: {$montantDb} | FedaPay: {$montantFeda} | transaction {$transactionId}");
    $pdo->prepare('UPDATE paiements SET statut = "echoue" WHERE id = ?')->execute([$paiement['id']]);
    header('Location: /index.php?payment=error');
    exit;
}

// 6. Tout est bon → on valide le paiement
$pdo->prepare('UPDATE paiements SET statut = "reussi" WHERE id = ?')->execute([$paiement['id']]);

$stmtFormuleNom = $pdo->prepare('SELECT nom FROM formules WHERE id = ?');
$stmtFormuleNom->execute([$paiement['formule_id']]);
$formuleNom = (string) $stmtFormuleNom->fetchColumn();

// 6bis. Mise à jour du compteur de places : on décrémente, et si ça tombe à 0, on remet à places_totales.
// On utilise une transaction + FOR UPDATE pour éviter que deux paiements confirmés en même temps
// ne lisent la même valeur de places_restantes avant que l'un des deux ne l'ait mise à jour.
$pdo->beginTransaction();
$stmtFormule = $pdo->prepare('SELECT places_restantes, places_totales FROM formules WHERE id = ? FOR UPDATE');
$stmtFormule->execute([$paiement['formule_id']]);
$formuleRow = $stmtFormule->fetch();

if ($formuleRow) {
    $nouvellesPlaces = (int) $formuleRow['places_restantes'] - 1;

    if ($nouvellesPlaces <= 0) {
        $nouvellesPlaces = (int) $formuleRow['places_totales'];
    }

    $pdo->prepare('UPDATE formules SET places_restantes = ? WHERE id = ?')
        ->execute([$nouvellesPlaces, $paiement['formule_id']]);
}

$pdo->commit();


// 7. Le profil d'orientation a déjà été rempli AVANT le paiement (accès libre, via guest_token).
$stmtProfil = $pdo->prepare('
    SELECT * FROM profils_orientation
    WHERE guest_token = ?
    ORDER BY created_at DESC
    LIMIT 1
');
$stmtProfil->execute([$paiement['guest_token']]);
$profil = $stmtProfil->fetch();

if (!$profil) {
    // Filet de sécurité : si aucun profil n'est trouvé (cas anormal), on renvoie au formulaire
    header('Location: /orientation-formulaire.php');
    exit;
}

// 8. Génération du code d'accompagnement, stocké directement sur le paiement (pas de compte utilisateur)
$codeAccompagnement = AuthService::generateCodeAccompagnement();
$pdo->prepare('UPDATE paiements SET code_accompagnement = ? WHERE id = ?')
    ->execute([$codeAccompagnement, $paiement['id']]);

// 9. Envoi des emails : le matricule à l'utilisateur, la notification à l'administrateur.
// Ces envois ne doivent jamais bloquer le flux si le SMTP échoue, d'où le try/catch.
try {
    $nomComplet = trim($profil['prenom'] . ' ' . $profil['nom']);

    MailService::sendMatriculeEmail(
        $profil['email'],
        $nomComplet,
        $codeAccompagnement
    );

    MailService::sendAdminPaymentNotification(
        $profil['email'],
        $nomComplet,
        $formuleNom,
        (float) $paiement['montant'],
        $paiement['reference']
    );
} catch (\Throwable $e) {
    error_log('Erreur envoi email post-paiement : ' . $e->getMessage());
}

unset($_SESSION['formule_choisie']);
unset($_SESSION['guest_token']);

// Le lien d'un GROUPE WhatsApp (chat.whatsapp.com/...) ne supporte pas le paramètre
// de pré-remplissage de texte (contrairement à wa.me/NUMERO?text=...). C'est une
// limitation de WhatsApp : impossible de préremplir un message avant que l'utilisateur
// rejoigne un groupe. Le récapitulatif complet (nom, matricule, série, mention...) est
// transmis à l'administrateur par email ci-dessus, pour ne rien perdre.
header('Location: https://chat.whatsapp.com/DGKN6QRai9ICpUfOCaHVZ6?s=cl&p=a&ilr=1');
exit;