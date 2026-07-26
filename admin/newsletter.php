<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Auth/AdminAuthService.php';
require_once __DIR__ . '/../src/Mail/MailService.php';

use App\Auth\AdminAuthService;
use App\Database\Database;
use App\Mail\MailService;

AdminAuthService::requireAdmin();

$pdo = Database::getConnection();
$erreur = null;
$succes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminAuthService::checkCsrf($_POST['csrf'] ?? null)) {
        $erreur = 'Session expirée, réessaie.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'envoyer') {
        $sujet = trim($_POST['sujet'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');

        if ($sujet === '' || $contenu === '') {
            $erreur = 'Le sujet et le contenu sont obligatoires.';
        } else {
            $abonnes = $pdo->query('SELECT email, token FROM newsletter_subscribers WHERE actif = 1')->fetchAll();

            if (count($abonnes) === 0) {
                $erreur = 'Aucun abonné actif pour le moment.';
            } else {
                set_time_limit(0);
                $nbEchecs = 0;

                foreach ($abonnes as $abonne) {
                    $unsubscribeUrl = rtrim(APP_URL, '/') . '/newsletter-unsubscribe.php?token=' . urlencode($abonne['token']);
                    $htmlBody = self_newsletter_template($sujet, $contenu, $unsubscribeUrl);

                    $ok = MailService::send($abonne['email'], '', $sujet, $htmlBody);
                    if (!$ok) {
                        $nbEchecs++;
                    }
                }

                $log = $pdo->prepare('INSERT INTO newsletters_envoyees (sujet, nb_destinataires, nb_echecs) VALUES (?, ?, ?)');
                $log->execute([$sujet, count($abonnes), $nbEchecs]);

                $succes = 'Newsletter envoyée à ' . (count($abonnes) - $nbEchecs) . ' abonné(s) sur ' . count($abonnes) . '.';
                if ($nbEchecs > 0) {
                    $succes .= ' (' . $nbEchecs . ' échec(s) d’envoi.)';
                }
            }
        }
    }
}

function self_newsletter_template(string $sujet, string $contenu, string $unsubscribeUrl): string
{
    $appName = htmlspecialchars(APP_NAME);
    $sujetHtml = htmlspecialchars($sujet);
    $contenuHtml = nl2br(htmlspecialchars($contenu));

    return <<<HTML
    <div style="font-family: Arial, Helvetica, sans-serif; max-width: 520px; margin: 0 auto; padding: 32px 24px; color: #2b2b2b;">
        <p style="font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #E8531F; font-weight: 700; margin: 0 0 20px;">{$appName}</p>
        <h2 style="margin: 0 0 20px; font-size: 20px;">{$sujetHtml}</h2>
        <div style="font-size: 15px; line-height: 1.7;">{$contenuHtml}</div>
        <hr style="border:none; border-top:1px solid #eee; margin: 32px 0 16px;">
        <p style="font-size: 12px; color:#999; margin:0;">
            Tu reçois cet email car tu es inscrit(e) à la newsletter {$appName}.
            <a href="{$unsubscribeUrl}" style="color:#999;">Se désabonner</a>
        </p>
    </div>
    HTML;
}

$totalAbonnes = (int) $pdo->query('SELECT COUNT(*) FROM newsletter_subscribers WHERE actif = 1')->fetchColumn();
$abonnesRecents = $pdo->query('
    SELECT email, subscribed_at FROM newsletter_subscribers
    WHERE actif = 1 ORDER BY subscribed_at DESC LIMIT 50
')->fetchAll();
$historique = $pdo->query('
    SELECT sujet, nb_destinataires, nb_echecs, envoyee_at FROM newsletters_envoyees
    ORDER BY envoyee_at DESC LIMIT 20
')->fetchAll();

$csrf = AdminAuthService::csrfToken();
$pageTitle = 'Newsletter — ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.admin-wrap { max-width: 900px; margin: 40px auto; padding: 0 20px; }
.admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
.admin-stat-card { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 16px 20px; }
.admin-stat-value { font-size: 1.6rem; font-weight: 700; }
.admin-stat-label { font-size: 0.85rem; color: #777; }
.admin-table-wrap { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow-x: auto; margin-bottom: 32px; }
.admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.admin-table th, .admin-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #f0f0f0; white-space: nowrap; }
.admin-table th { background: #fafafa; font-weight: 600; }
.admin-section-title { font-size: 1.1rem; margin-bottom: 12px; font-weight: 700; }
.newsletter-compose { background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 20px; margin-bottom: 32px; }
.newsletter-compose label { display: block; font-size: 0.85rem; margin-bottom: 6px; color: #555; font-weight: 600; }
.newsletter-compose input[type="text"],
.newsletter-compose textarea {
    width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;
    font-family: inherit; font-size: 0.95rem; margin-bottom: 16px;
}
.newsletter-compose textarea { min-height: 180px; resize: vertical; }
</style>

<div class="admin-wrap">
    <p><a href="/admin/dashboard.php">← Retour au dashboard</a></p>
    <h1 style="margin-bottom: 20px;">Newsletter</h1>

    <?php if ($erreur): ?><p class="auth-erreur"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <?php if ($succes): ?><p class="auth-succes"><?= htmlspecialchars($succes) ?></p><?php endif; ?>

    <div class="admin-stats">
        <div class="admin-stat-card">
            <p class="admin-stat-value"><?= $totalAbonnes ?></p>
            <p class="admin-stat-label">Abonnés actifs</p>
        </div>
    </div>

    <div class="newsletter-compose">
        <p class="admin-section-title">Composer une newsletter</p>
        <form method="POST" onsubmit="return confirm('Envoyer cette newsletter à <?= $totalAbonnes ?> abonné(s) ?');">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="envoyer">

            <label for="sujet">Sujet de l'email</label>
            <input type="text" id="sujet" name="sujet" required placeholder="Ex : Les nouvelles filières disponibles cette semaine">

            <label for="contenu">Contenu</label>
            <textarea id="contenu" name="contenu" required placeholder="Écris le contenu de ta newsletter ici..."></textarea>

            <button type="submit" class="btn btn-primary">Envoyer à <?= $totalAbonnes ?> abonné(s)</button>
        </form>
    </div>

    <p class="admin-section-title">Derniers abonnés</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Email</th><th>Inscrit le</th></tr></thead>
            <tbody>
                <?php foreach ($abonnesRecents as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= htmlspecialchars($a['subscribed_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($abonnesRecents) === 0): ?>
                <tr><td colspan="2">Aucun abonné pour le moment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p class="admin-section-title">Historique des envois</p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Sujet</th><th>Destinataires</th><th>Échecs</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($historique as $h): ?>
                <tr>
                    <td><?= htmlspecialchars($h['sujet']) ?></td>
                    <td><?= (int) $h['nb_destinataires'] ?></td>
                    <td><?= (int) $h['nb_echecs'] ?></td>
                    <td><?= htmlspecialchars($h['envoyee_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($historique) === 0): ?>
                <tr><td colspan="4">Aucun envoi pour le moment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>