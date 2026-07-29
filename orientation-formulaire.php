<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php'; // conservé uniquement pour les constantes SERIES / MENTIONS

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
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $serie = trim($_POST['serie'] ?? '');
    $mention = trim($_POST['mention'] ?? '');
    $moyenne = trim($_POST['moyenne'] ?? '');
    $professionReve = trim($_POST['profession_reve'] ?? '');
    $ecoleReve = trim($_POST['ecole_reve'] ?? '');

    // Normalisation : accepte la virgule française (ex: "15,5") comme le point (ex: "15.5")
    $moyenne = str_replace(',', '.', $moyenne);

    if ($nom === '' || mb_strlen($nom) < 2) {
        $erreur = 'Merci de renseigner ton nom.';
    } elseif ($prenom === '' || mb_strlen($prenom) < 2) {
        $erreur = 'Merci de renseigner ton prénom.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Merci de renseigner une adresse email valide.';
    } elseif (!in_array($serie, \App\Auth\AuthService::SERIES, true)) {
        $erreur = 'Merci de sélectionner une série valide.';
    } elseif (!in_array($mention, \App\Auth\AuthService::MENTIONS, true)) {
        $erreur = 'Merci de sélectionner une mention valide.';
    } elseif (!is_numeric($moyenne) || (float) $moyenne < 0 || (float) $moyenne > 20) {
        $erreur = 'Merci de renseigner une moyenne valide (entre 0 et 20).';
    }

    if (!$erreur) {
        try {
            $stmtInsert = $pdo->prepare('
                INSERT INTO profils_orientation
                    (guest_token, nom, prenom, email, serie, mention, moyenne, profession_reve, ecole_reve)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmtInsert->execute([
                $guestToken,
                $nom,
                $prenom,
                $email,
                $serie,
                $mention,
                (float) $moyenne,
                $professionReve !== '' ? $professionReve : null,
                $ecoleReve !== '' ? $ecoleReve : null,
            ]);

            // Le profil est enregistré, place au paiement pour finaliser l'accompagnement
            header('Location: /paiement.php');
            exit;
        } catch (\Throwable $e) {
            error_log('Erreur enregistrement profil orientation : ' . $e->getMessage());
            $erreur = "Une erreur est survenue lors de l'enregistrement. Réessaie dans un instant.";
        }
    }
}

$pageTitle = 'Ton profil d’orientation — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <p class="eyebrow eyebrow-center">Avant le paiement</p>
        <h1 class="auth-title">Complète ton profil d'orientation</h1>
        <p class="auth-note" style="margin-bottom: 20px;">
            Ces informations seront transmises à ton conseiller d'orientation sur WhatsApp juste après ton paiement.
        </p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">

            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">

            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required placeholder="toi@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="serie">Série</label>
            <select id="serie" name="serie" required>
                <option value="">-- Choisis ta série --</option>
                <?php foreach (\App\Auth\AuthService::SERIES as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= (($_POST['serie'] ?? '') === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="mention">Mention</label>
            <select id="mention" name="mention" required>
                <option value="">-- Choisis ta mention --</option>
                <?php foreach (\App\Auth\AuthService::MENTIONS as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= (($_POST['mention'] ?? '') === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="moyenne">Moyenne (sur 20)</label>
            <input type="number" id="moyenne" name="moyenne" step="0.01" min="0" max="20" required value="<?= htmlspecialchars($_POST['moyenne'] ?? '') ?>">

            <label for="profession_reve">Profession envisagée <span style="font-weight:400;">(optionnel)</span></label>
            <input type="text" id="profession_reve" name="profession_reve" value="<?= htmlspecialchars($_POST['profession_reve'] ?? '') ?>">

            <label for="ecole_reve">Université envisagée <span style="font-weight:400;">(optionnel)</span></label>
            <input type="text" id="ecole_reve" name="ecole_reve" value="<?= htmlspecialchars($_POST['ecole_reve'] ?? '') ?>">

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 12px;">Continuer vers le paiement</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>