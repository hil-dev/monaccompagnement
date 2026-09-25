<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Payment/SasPayService.php';
require_once __DIR__ . '/src/Accompagnement/AccompagnementService.php';
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Accompagnement\AccompagnementService;
use App\Database\Database;
use App\Payment\SasPayService;
use App\Mail\MailService;

// Après un paiement confirmé, on affiche une page de remerciement sur notre propre site
// (plutôt qu'un lien de groupe WhatsApp) : elle contient le matricule et permet de
// contacter directement l'administrateur, avec message pré-rempli.
function urlSucces(string $reference): string
{
    return '/paiement-succes.php?ref=' . urlencode($reference);
}

// SasPay redirige le client vers notre return_url, où nous avons placé notre propre référence (?ref=APB-...)
$reference = $_GET['ref'] ?? '';
if (!is_string($reference) || !preg_match('/^APB-[A-F0-9]{12}$/', $reference)) {
    header('Location: /index.php?payment=error');
    exit;
}

$pdo = Database::getConnection();

// 1. On retrouve le paiement correspondant en base
$stmt = $pdo->prepare('SELECT * FROM paiements WHERE reference = ?');
$stmt->execute([$reference]);
$paiement = $stmt->fetch();

if (!$paiement || empty($paiement['saspay_session_id'])) {
    error_log("Paiement introuvable pour la référence {$reference}");
    header('Location: /index.php?payment=error');
    exit;
}

// 2. Idempotence : si déjà traité, on ne refait rien
if ($paiement['statut'] === 'reussi') {
    header('Location: ' . urlSucces($reference));
    exit;
}

// 3. Vérification de la session directement auprès de SasPay (jamais faire confiance à l'URL seule)
try {
    $session = SasPayService::fetchCheckoutSession($paiement['saspay_session_id']);
} catch (\Throwable $e) {
    error_log('Erreur vérification session SasPay : ' . $e->getMessage());
    header('Location: /index.php?payment=error');
    exit;
}

$statutSasPay = strtoupper((string) ($session['status'] ?? ''));

// 4. Le paiement n'est pas (encore) validé côté SasPay
if ($statutSasPay === 'PENDING') {
    // Le client est revenu avant la confirmation : on ne marque rien comme échoué
    header('Location: /index.php?payment=pending');
    exit;
}

if ($statutSasPay !== 'PAID') {
    // EXPIRED, CANCELLED ou statut inconnu
    $pdo->prepare('UPDATE paiements SET statut = "echoue" WHERE id = ?')->execute([$paiement['id']]);
    header('Location: /index.php?payment=declined');
    exit;
}

// 5. Vérification anti-fraude : montant et devise doivent correspondre exactement
$montantSasPay = (int) round((float) ($session['amount'] ?? 0));
$montantDb     = (int) $paiement['montant'];
$devise        = strtoupper((string) ($session['currency'] ?? ''));

if ($montantSasPay !== $montantDb || $devise !== 'XOF') {
    error_log("❌ Montant/devise incohérent — DB: {$montantDb} XOF | SasPay: {$montantSasPay} {$devise} | référence {$reference}");
    $pdo->prepare('UPDATE paiements SET statut = "echoue" WHERE id = ?')->execute([$paiement['id']]);
    header('Location: /index.php?payment=error');
    exit;
}

// 6. Tout est bon → on valide le paiement de façon ATOMIQUE.
// Si deux requêtes arrivent en même temps (double redirection, rafraîchissement), une seule
// passera ici : l'autre verra rowCount() = 0 et sera simplement redirigée.
$stmtValide = $pdo->prepare('UPDATE paiements SET statut = "reussi" WHERE id = ? AND statut <> "reussi"');
$stmtValide->execute([$paiement['id']]);

if ($stmtValide->rowCount() === 0) {
    header('Location: ' . urlSucces($reference));
    exit;
}

$stmtFormuleNom = $pdo->prepare('SELECT nom FROM formules WHERE id = ?');
$stmtFormuleNom->execute([$paiement['formule_id']]);
$formuleNom = (string) $stmtFormuleNom->fetchColumn();

// 6bis. Mise à jour du compteur de places : on décrémente, et si ça tombe à 0, on remet à places_totales.
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


// 7. Le profil d'accompagnement a déjà été rempli AVANT le paiement (accès libre, via guest_token).
$stmtProfil = $pdo->prepare('
    SELECT * FROM profils_accompagnement
    WHERE guest_token = ?
    ORDER BY created_at DESC
    LIMIT 1
');
$stmtProfil->execute([$paiement['guest_token']]);
$profil = $stmtProfil->fetch();

if (!$profil) {
    // Filet de sécurité : si aucun profil n'est trouvé (cas anormal), on renvoie au formulaire
    header('Location: /accompagnement-formulaire.php');
    exit;
}

// 8. Génération du code d'accompagnement, stocké directement sur le paiement
$codeAccompagnement = AccompagnementService::generateCodeAccompagnement();
$pdo->prepare('UPDATE paiements SET code_accompagnement = ? WHERE id = ?')
    ->execute([$codeAccompagnement, $paiement['id']]);

// 9. Envoi des emails : le matricule à l'utilisateur, la notification à l'administrateur.
// Ces envois ne doivent jamais bloquer le flux si le SMTP échoue, d'où le try/catch.
try {
    $nomComplet = trim($profil['nom_complet'] ?? '');

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

// La page de succès affiche le récapitulatif complet et permet de contacter
// directement l'administrateur sur WhatsApp, avec un message déjà pré-rempli.
header('Location: ' . urlSucces($reference));
exit;