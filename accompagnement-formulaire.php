<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';

use App\Database\Database;

$pdo = Database::getConnection();

// On mémorise la formule choisie AVANT toute redirection, pour ne jamais la perdre
if (isset($_GET['formule'])) {
    $_SESSION['formule_choisie'] = $_GET['formule'];
}

// Sécurité : ce formulaire n'a de sens que si une formule est en attente de paiement
if (!isset($_SESSION['formule_choisie'])) {
    header('Location: /index.php#formules');
    exit;
}

// Accès libre, sans compte : on identifie le visiteur par un jeton unique stocké en session,
// qui permettra de relier son profil et son paiement plus loin dans le parcours.
if (!isset($_SESSION['guest_token'])) {
    $_SESSION['guest_token'] = bin2hex(random_bytes(16));
}
$guestToken = $_SESSION['guest_token'];

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomComplet = trim($_POST['nom_complet'] ?? '');
    $universite = trim($_POST['universite'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $numeroWhatsapp = trim($_POST['numero_whatsapp'] ?? '');
    // On ne garde que les chiffres et le + initial pour la validation
    $numeroNettoye = preg_replace('/[^0-9+]/', '', $numeroWhatsapp);

    if ($nomComplet === '' || mb_strlen($nomComplet) < 4 || !str_contains(trim($nomComplet), ' ')) {
        $erreur = 'Merci de renseigner ton nom et prénom.';
    } elseif ($universite === '' || mb_strlen($universite) < 2) {
        $erreur = 'Merci de renseigner ton université.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Merci de renseigner une adresse email valide.';
    } elseif (!preg_match('/^\+?[0-9]{8,15}$/', $numeroNettoye)) {
        $erreur = 'Merci de renseigner un numéro WhatsApp valide (ex. +229 XX XX XX XX).';
    }

    if (!$erreur) {
        try {
            $stmtInsert = $pdo->prepare('
                INSERT INTO profils_accompagnement
                    (guest_token, nom_complet, universite, email, numero_whatsapp)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmtInsert->execute([
                $guestToken,
                $nomComplet,
                $universite,
                $email,
                $numeroNettoye,
            ]);

            // Le profil est enregistré, place au paiement pour finaliser l'accompagnement
            header('Location: /paiement.php');
            exit;
        } catch (\Throwable $e) {
            error_log('Erreur enregistrement profil accompagnement : ' . $e->getMessage());
            $erreur = "Une erreur est survenue lors de l'enregistrement. Réessaie dans un instant.";
        }
    }
}

$pageTitle = 'Ton profil d’accompagnement — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="/assets/css/funnel-v2.css">

<section class="auth-page funnel-v2">
    <div class="funnel-wrap">
        <a href="/index.php#formules" class="funnel-back">
            <span aria-hidden="true">←</span> Retour à l'accueil
        </a>

        <ol class="funnel-steps" aria-label="Progression">
            <li class="is-current" aria-current="step"><span class="funnel-step-num">1</span> Ton profil</li>
            <li><span class="funnel-step-num">2</span> Paiement</li>
        </ol>

        <div class="auth-card">
            <h1 class="auth-title">Complète ton profil</h1>
            <p class="auth-note">
                Ces informations seront transmises à ton conseiller sur WhatsApp juste après ton paiement.
            </p>

            <?php if ($erreur): ?>
                <p class="auth-erreur" role="alert"><?= htmlspecialchars($erreur) ?></p>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <label for="nom_complet">Nom et prénom</label>
                <input type="text" id="nom_complet" name="nom_complet" required autocomplete="name" placeholder="Ex. AGOSSOU Marie" value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>">

                <label for="universite">Université</label>
                <input type="text" id="universite" name="universite" required autocomplete="organization" placeholder="Ex. Université d'Abomey-Calavi" value="<?= htmlspecialchars($_POST['universite'] ?? '') ?>">

                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="toi@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                <label for="numero_whatsapp">Numéro WhatsApp</label>
                <input type="tel" id="numero_whatsapp" name="numero_whatsapp" required autocomplete="tel" placeholder="+229 XX XX XX XX" value="<?= htmlspecialchars($_POST['numero_whatsapp'] ?? '') ?>">

                <button type="submit" class="btn btn-primary btn-block">Continuer vers le paiement</button>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>