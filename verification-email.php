<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Mail/MailService.php';

use App\Auth\AuthService;
use App\Database\Database;
use App\Mail\MailService;

if (AuthService::isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$userId = $_SESSION['pending_verification_user_id'] ?? null;
if (!$userId) {
    header('Location: /connexion.php');
    exit;
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['pending_verification_user_id']);
    header('Location: /inscription.php');
    exit;
}

$erreur = null;
$succes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        $lastSent = $_SESSION['verification_last_sent'] ?? 0;
        if (time() - $lastSent < 60) {
            $erreur = 'Merci de patienter un peu avant de redemander un code.';
        } else {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 900);
            $update = $pdo->prepare('UPDATE users SET verification_code = ?, verification_code_expires_at = ? WHERE id = ?');
            $update->execute([$code, $expiresAt, $userId]);

            MailService::sendVerificationCode($user['email'], $user['nom_complet'] ?? '', $code);
            $_SESSION['verification_last_sent'] = time();
            $succes = 'Un nouveau code vient d’être envoyé à ' . htmlspecialchars($user['email']) . '.';
        }
    } else {
        $code = trim($_POST['code'] ?? '');

        if (!preg_match('/^\d{6}$/', $code)) {
            $erreur = 'Merci d’entrer les 6 chiffres du code.';
        } elseif (empty($user['verification_code']) || $user['verification_code'] !== $code) {
            $erreur = 'Code incorrect.';
        } elseif (empty($user['verification_code_expires_at']) || strtotime($user['verification_code_expires_at']) < time()) {
            $erreur = 'Ce code a expiré. Demande-en un nouveau.';
        } else {
            $update = $pdo->prepare('UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires_at = NULL WHERE id = ?');
            $update->execute([$userId]);

            $stmt->execute([$userId]);
            $freshUser = $stmt->fetch();

            unset($_SESSION['pending_verification_user_id'], $_SESSION['verification_last_sent']);
            AuthService::startSession($freshUser);

            MailService::sendWelcomeEmail($freshUser['email'], $freshUser['nom_complet'] ?? '');

            $redirect = isset($_SESSION['formule_choisie']) ? '/paiement.php' : '/index.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$emailMasque = preg_replace('/^(.{2}).*(@.*)$/', '$1***$2', $user['email']);

$pageTitle = 'Vérification de ton email — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card">
        <a href="/connexion.php" class="auth-retour">
            <span aria-hidden="true">←</span> Retour à la connexion
        </a>

        <p class="eyebrow eyebrow-center">Dernière étape</p>
        <h1 class="auth-title">Vérifie ton adresse email</h1>
        <p class="auth-note">Un code à 6 chiffres a été envoyé à <strong><?= htmlspecialchars($emailMasque) ?></strong>.</p>

        <?php if ($erreur): ?>
            <p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p>
        <?php endif; ?>
        <?php if ($succes): ?>
            <p class="auth-succes"><?= htmlspecialchars($succes) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="codeForm">
            <div class="code-inputs" id="codeInputs">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="code-box" autocomplete="one-time-code">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="code-box">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="code-box">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="code-box">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="code-box">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="code-box">
            </div>
            <input type="hidden" name="code" id="codeHidden">

            <button type="submit" class="btn btn-primary btn-block">Vérifier le code</button>
        </form>

        <form method="POST" class="auth-resend-form">
            <button type="submit" name="resend" value="1" class="btn btn-ghost btn-block">Renvoyer le code</button>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const boxes = Array.from(document.querySelectorAll('.code-box'));
    const hidden = document.getElementById('codeHidden');
    const form = document.getElementById('codeForm');

    const syncHidden = () => {
        hidden.value = boxes.map((b) => b.value).join('');
    };

    boxes.forEach((box, index) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/[^0-9]/g, '').slice(0, 1);
            if (box.value && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
            syncHidden();
        });

        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && index > 0) {
                boxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            pasted.slice(0, boxes.length).split('').forEach((char, i) => {
                if (boxes[i]) boxes[i].value = char;
            });
            syncHidden();
            const next = boxes[Math.min(pasted.length, boxes.length - 1)];
            if (next) next.focus();
        });
    });

    form.addEventListener('submit', syncHidden);
    if (boxes[0]) boxes[0].focus();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>