<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';

use App\Database\Database;

// ⚠️ Remplace ce numéro par le vrai numéro WhatsApp de l'administrateur (format international, sans espaces).
// Idéalement, déplace cette constante dans config/config.php pour ne la définir qu'à un seul endroit.
if (!defined('ADMIN_WHATSAPP_NUMBER')) {
    define('ADMIN_WHATSAPP_NUMBER', '+229XXXXXXXX');
}

$reference = $_GET['ref'] ?? '';
if (!is_string($reference) || !preg_match('/^APB-[A-F0-9]{12}$/', $reference)) {
    header('Location: /index.php');
    exit;
}

$pdo = Database::getConnection();

// On n'affiche cette page que pour un paiement RÉELLEMENT confirmé : jamais pour
// un paiement en attente, échoué ou inexistant, même si quelqu'un devine une référence.
$stmt = $pdo->prepare('
    SELECT p.*, f.nom AS formule_nom
    FROM paiements p
    JOIN formules f ON f.id = p.formule_id
    WHERE p.reference = ? AND p.statut = "reussi"
');
$stmt->execute([$reference]);
$paiement = $stmt->fetch();

if (!$paiement) {
    header('Location: /index.php');
    exit;
}

$stmtProfil = $pdo->prepare('SELECT * FROM profils_accompagnement WHERE guest_token = ? ORDER BY created_at DESC LIMIT 1');
$stmtProfil->execute([$paiement['guest_token']]);
$profil = $stmtProfil->fetch();

$nomComplet = $profil ? trim($profil['nom_complet']) : '';
$prenomAffiche = $nomComplet !== '' ? trim(explode(' ', $nomComplet)[0]) : '';
$universite = $profil ? trim($profil['universite'] ?? '') : '';
$code = $paiement['code_accompagnement'] ?? '—';

// Numéro au format international sans le "+" ni espaces, tel qu'attendu par wa.me
$numeroWa = preg_replace('/[^0-9]/', '', ADMIN_WHATSAPP_NUMBER);

$lignesMessage = ["Bonjour, je viens de finaliser mon paiement pour l'accompagnement {$paiement['formule_nom']}."];
if ($nomComplet !== '') {
    $lignesMessage[] = "Nom et prénom : {$nomComplet}";
}
if ($universite !== '') {
    $lignesMessage[] = "Université : {$universite}";
}
$lignesMessage[] = "Code d'accompagnement : {$code}";

$messagePreRempli = implode("\n", $lignesMessage);

$lienWhatsapp = 'https://wa.me/' . $numeroWa . '?text=' . rawurlencode($messagePreRempli);

$pageTitle = 'Paiement confirmé — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">

<section class="auth-page funnel-v2">
    <div class="funnel-wrap">
        <div class="auth-card succes-card">
            <div class="succes-icon" aria-hidden="true">✓</div>

            <h1 class="auth-title">Paiement confirmé <?= $prenomAffiche !== '' ? ', ' . htmlspecialchars($prenomAffiche) : '' ?> !</h1>
            <p class="auth-note">Ton accompagnement <strong><?= htmlspecialchars($paiement['formule_nom']) ?></strong> est activé. Voici ton code, garde-le précieusement.</p>

            <div class="code-box">
                <p class="code-box-label">Ton code d'accompagnement</p>
                <p class="code-box-value"><?= htmlspecialchars($code) ?></p>
            </div>

            <a href="<?= htmlspecialchars($lienWhatsapp) ?>" class="btn btn-primary btn-block" target="_blank" rel="noopener">
                Contacter mon conseiller sur WhatsApp
            </a>

            <button type="button" class="btn btn-ghost btn-block" id="copierNumero" data-numero="<?= htmlspecialchars(ADMIN_WHATSAPP_NUMBER) ?>">
                Copier le numéro (<?= htmlspecialchars(ADMIN_WHATSAPP_NUMBER) ?>)
            </button>
            <p class="copy-feedback" id="copieFeedback" role="status">Numéro copié !</p>

            <p class="funnel-secure">Un email récapitulatif t'a aussi été envoyé à <?= htmlspecialchars($profil['email'] ?? '') ?>.</p>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var bouton = document.getElementById('copierNumero');
    var feedback = document.getElementById('copieFeedback');
    if (!bouton || !feedback) return;

    bouton.addEventListener('click', function () {
        var numero = bouton.dataset.numero || '';

        function afficherConfirmation() {
            feedback.classList.add('is-visible');
            setTimeout(function () { feedback.classList.remove('is-visible'); }, 2200);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(numero).then(afficherConfirmation).catch(function () {
                window.prompt('Copie ce numéro :', numero);
            });
        } else {
            // Repli pour les navigateurs/contextes sans API Clipboard (ex. HTTP non sécurisé)
            window.prompt('Copie ce numéro :', numero);
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>