<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Payment/FedaPayService.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Auth\AuthService;

use App\Database\Database;
use App\Payment\FedaPayService;
use App\Mail\MailService;

if (!isset($_GET['id'])) {
    header('Location: /profil.php?payment=error');
    exit;
}

$transactionId = (int) $_GET['id'];
$pdo = Database::getConnection();

// 1. Vérification de la transaction directement auprès de FedaPay (jamais faire confiance au paramètre GET seul)
try {
    $transaction = FedaPayService::fetchTransaction($transactionId);
} catch (\Throwable $e) {
    error_log('Erreur vérification transaction FedaPay : ' . $e->getMessage());
    header('Location: /profil.php?payment=error');
    exit;
}

// 2. On retrouve le paiement correspondant en base
$stmt = $pdo->prepare('SELECT * FROM paiements WHERE fedapay_transaction_id = ?');
$stmt->execute([$transactionId]);
$paiement = $stmt->fetch();

if (!$paiement) {
    error_log("Paiement introuvable pour la transaction FedaPay {$transactionId}");
    header('Location: /profil.php?payment=error');
    exit;
}

// 3. Idempotence : si déjà traité, on ne refait rien
if ($paiement['statut'] === 'reussi') {
    header('Location: /profil.php?payment=already_processed');
    exit;
}

// 4. Le paiement n'a pas été approuvé côté FedaPay
if (($transaction['status'] ?? '') !== 'approved') {
    $pdo->prepare('UPDATE paiements SET statut = "echoue" WHERE id = ?')->execute([$paiement['id']]);
    header('Location: /profil.php?payment=declined');
    exit;
}

// 5. Vérification anti-fraude : le montant payé doit correspondre exactement au montant attendu
$montantFeda = (int) $transaction['amount'];
$montantDb   = (int) $paiement['montant'];

if ($montantFeda !== $montantDb) {
    error_log("❌ Montant incohérent — DB: {$montantDb} | FedaPay: {$montantFeda} | transaction {$transactionId}");
    $pdo->prepare('UPDATE paiements SET statut = "echoue" WHERE id = ?')->execute([$paiement['id']]);
    header('Location: /profil.php?payment=error');
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

// 7. On génère le code d'accompagnement s'il n'en a pas déjà un
$stmtUser = $pdo->prepare('SELECT id, code_accompagnement FROM users WHERE id = ?');
$stmtUser->execute([$paiement['user_id']]);
$userPaiement = $stmtUser->fetch();

if ($userPaiement && empty($userPaiement['code_accompagnement'])) {
    $code = AuthService::generateCodeAccompagnement();
    $pdo->prepare('UPDATE users SET code_accompagnement = ? WHERE id = ?')->execute([$code, $userPaiement['id']]);
    $userPaiement['code_accompagnement'] = $code;
}

// 8. Envoi des emails : le matricule à l'utilisateur, la notification à l'administrateur.
// Ces envois ne doivent jamais bloquer le flux si le SMTP échoue, d'où le try/catch.
try {
    $stmtUserComplet = $pdo->prepare('SELECT nom_complet, email FROM users WHERE id = ?');
    $stmtUserComplet->execute([$paiement['user_id']]);
    $userComplet = $stmtUserComplet->fetch();

    if ($userComplet) {
        MailService::sendMatriculeEmail(
            $userComplet['email'],
            $userComplet['nom_complet'] ?? '',
            $userPaiement['code_accompagnement'] ?? ''
        );

        MailService::sendAdminPaymentNotification(
            $userComplet['email'],
            $userComplet['nom_complet'] ?? '',
            $formuleNom,
            (float) $paiement['montant'],
            $paiement['reference']
        );
    }
} catch (\Throwable $e) {
    error_log('Erreur envoi email post-paiement : ' . $e->getMessage());
}

unset($_SESSION['formule_choisie']);

// 9. Le profil d'orientation a déjà été rempli AVANT le paiement (nouveau flux).
// On récupère les dernières informations saisies pour construire le message WhatsApp.
$stmtProfil = $pdo->prepare('
    SELECT * FROM profils_orientation
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
');
$stmtProfil->execute([$paiement['user_id']]);
$profil = $stmtProfil->fetch();

if (!$profil) {
    // Filet de sécurité : si aucun profil n'est trouvé (cas anormal), on renvoie au formulaire
    header('Location: /orientation-formulaire.php');
    exit;
}

$lignes = [
    "Bonjour, je suis {$profil['prenom']} {$profil['nom']}.",
    "Mon matricule d'accompagnement : {$userPaiement['code_accompagnement']}",
    "Série : {$profil['serie']}",
    "Mention : {$profil['mention']}",
    "Moyenne : {$profil['moyenne']}/20",
];
if (!empty($profil['profession_reve'])) {
    $lignes[] = "Profession envisagée : {$profil['profession_reve']}";
}
if (!empty($profil['ecole_reve'])) {
    $lignes[] = "Université envisagée : {$profil['ecole_reve']}";
}
$lignes[] = "Je souhaite être accompagné(e) dans le choix de ma filière.";

$message = implode("\n", $lignes);
$numeroWhatsapp = '22953096255'; // +229 53 09 62 55, sans le "+" ni espaces
$whatsappUrl = 'https://wa.me/' . $numeroWhatsapp . '?text=' . rawurlencode($message);

header('Location: ' . $whatsappUrl);
exit;