<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';

use App\Auth\AuthService;
use App\Database\Database;

AuthService::requireLogin();

$pdo = Database::getConnection();

$stmtUser = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmtUser->execute([AuthService::currentUserId()]);
$user = $stmtUser->fetch();

// Sécurité : cette page n'a de sens que pour un utilisateur ayant déjà payé (et donc ayant un matricule)
if (!$user || empty($user['code_accompagnement'])) {
    header('Location: /index.php#formules');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $serie = trim($_POST['serie'] ?? '');
    $age = trim($_POST['age'] ?? '');
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
    } elseif (!in_array($serie, \App\Auth\AuthService::SERIES, true)) {
        $erreur = 'Merci de sélectionner une série valide.';
    } elseif (!ctype_digit($age) || (int) $age < 10 || (int) $age > 60) {
        $erreur = 'Merci de renseigner un âge valide.';
    } elseif (!in_array($mention, \App\Auth\AuthService::MENTIONS, true)) {
        $erreur = 'Merci de sélectionner une mention valide.';
    } elseif (!is_numeric($moyenne) || (float) $moyenne < 0 || (float) $moyenne > 20) {
        $erreur = 'Merci de renseigner une moyenne valide (entre 0 et 20).';
    }

    if (!$erreur) {
        try {
            $pdo->beginTransaction();

            $stmtInsert = $pdo->prepare('
                INSERT INTO profils_orientation
                    (user_id, nom, prenom, serie, age, mention, moyenne, profession_reve, ecole_reve)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmtInsert->execute([
                $user['id'],
                $nom,
                $prenom,
                $serie,
                (int) $age,
                $mention,
                (float) $moyenne,
                $professionReve !== '' ? $professionReve : null,
                $ecoleReve !== '' ? $ecoleReve : null,
            ]);

            // On complète également la ligne de l'utilisateur avec les dernières infos saisies
            $stmtUpdateUser = $pdo->prepare('
                UPDATE users
                SET nom_complet = ?, prenom = ?, serie = ?, age = ?, mention = ?, moyenne = ?, profession_reve = ?, ecole_reve = ?
                WHERE id = ?
            ');
            $stmtUpdateUser->execute([
                trim($prenom . ' ' . $nom),
                $prenom,
                $serie,
                (int) $age,
                $mention,
                (float) $moyenne,
                $professionReve !== '' ? $professionReve : null,
                $ecoleReve !== '' ? $ecoleReve : null,
                $user['id'],
            ]);

            $pdo->commit();

            // Construction du message WhatsApp pré-rempli
            $lignes = [
                "Bonjour, je suis {$prenom} {$nom}.",
                "Mon matricule d'accompagnement : {$user['code_accompagnement']}",
                "Série : {$serie}",
                "Âge : {$age} ans",
                "Mention : {$mention}",
                "Moyenne : {$moyenne}/20",
            ];
            if ($professionReve !== '') {
                $lignes[] = "Profession de rêve : {$professionReve}";
            }
            if ($ecoleReve !== '') {
                $lignes[] = "École de rêve : {$ecoleReve}";
            }
            $lignes[] = "Je souhaite être accompagné(e) dans le choix de ma filière.";

            $message = implode("\n", $lignes);
            $numeroWhatsapp = '22953096255'; // +229 53 09 62 55, sans le "+" ni espaces
            $whatsappUrl = 'https://wa.me/' . $numeroWhatsapp . '?text=' . rawurlencode($message);

            header('Location: ' . $whatsappUrl);
            exit;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
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
        <p class="eyebrow eyebrow-center">Dernière étape</p>
        <h1 class="auth-title">Complète ton profil d'orientation</h1>
        <p class="auth-note" style="margin-bottom: 20px;">
            Ces informations seront transmises à ton conseiller d'orientation sur WhatsApp, avec ton matricule
            <strong><?= htmlspecialchars($user['code_accompagnement']) ?></strong>.
        </p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">

            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">

            <label for="serie">Série</label>
            <select id="serie" name="serie" required>
                <option value="">-- Choisis ta série --</option>
                <?php foreach (\App\Auth\AuthService::SERIES as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= (($_POST['serie'] ?? '') === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="age">Âge</label>
            <input type="number" id="age" name="age" min="10" max="60" required value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">

            <label for="mention">Mention</label>
            <select id="mention" name="mention" required>
                <option value="">-- Choisis ta mention --</option>
                <?php foreach (\App\Auth\AuthService::MENTIONS as $m): ?>
                    <option value="<?= htmlspecialchars($m) ?>" <?= (($_POST['mention'] ?? '') === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="moyenne">Moyenne (sur 20)</label>
            <input type="number" id="moyenne" name="moyenne" step="0.01" min="0" max="20" required value="<?= htmlspecialchars($_POST['moyenne'] ?? '') ?>">

            <label for="profession_reve">Profession de rêve <span style="font-weight:400;">(optionnel)</span></label>
            <input type="text" id="profession_reve" name="profession_reve" value="<?= htmlspecialchars($_POST['profession_reve'] ?? '') ?>">

            <label for="ecole_reve">École de rêve <span style="font-weight:400;">(optionnel)</span></label>
            <input type="text" id="ecole_reve" name="ecole_reve" value="<?= htmlspecialchars($_POST['ecole_reve'] ?? '') ?>">

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 12px;">Envoyer</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>